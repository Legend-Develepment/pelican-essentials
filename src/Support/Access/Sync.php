<?php

namespace LegendDevelopment\Theme\Support\Access;

use App\Jobs\RevokeSftpAccessJob;
use App\Models\Role;
use App\Models\Server;
use App\Models\Subuser;
use Illuminate\Console\Scheduling\Schedule as Scheduler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Makes the role mappings true, in Pelican's own subuser table.
 *
 * The whole feature is here, and so is all of the risk: this is the one place
 * in the plugin that writes to a table the panel itself owns. Four rules keep
 * that safe, and each of them is a decision rather than a precaution.
 *
 * **1. It only ever touches rows it made.** A separate index records every
 * (person, server) pair this created. A row somebody added by hand on Pelican's
 * own subuser page is never changed and never removed, even when a mapping
 * happens to want the same pair - in that case the hand-made row is simply left
 * to stand, permissions and all.
 *
 * **2. It never writes for an owner or a root admin.** The owner of a server
 * already has every permission on it, and Pelican deletes an owner's subuser row
 * on every save of that server anyway - so a row for one would be written and
 * removed for ever. A root admin already reaches everything.
 *
 * **3. It sends nothing.** Pelican mails somebody when they are added to a
 * server, which is right when a person did it and wrong when a timer did: one
 * saved mapping would be a hundred emails at once, and a role that flapped would
 * send them again. The server simply appears in their list.
 *
 * **4. Removing access revokes SFTP with it.** Deleting the row alone would
 * leave somebody able to reach the files over SFTP after the panel had stopped
 * showing them the server. Pelican's own removal dispatches RevokeSftpAccessJob
 * and so does this - which does mean it needs the queue worker Pelican already
 * requires.
 */
class Sync
{
    /**
     * The pairs this created, so nothing else is ever touched.
     *
     * Its own file rather than a column, because there is no column: Pelican's
     * subusers table has a user, a server, a permission list and two timestamps,
     * and nothing that says who wrote the row.
     */
    private const MANAGED = 'legend-theme/access-managed.json';

    /** What the last run did, for the settings page to show. */
    private const REPORT = 'legend-theme.access.report';

    /**
     * One reconciliation at a time.
     *
     * Three things start one - saving the page, the quarter-hour timer, and
     * somebody signing in - so two of them overlapping is not a rare case, it
     * is a Tuesday. Both would read the same rows as missing and both would
     * insert them, and Pelican's subusers table has no unique index on
     * (user_id, server_id) to catch it.
     *
     * Not held for long: a run is a handful of queries. The timeout is there
     * for a run whose process died holding it, not for a run that is slow.
     */
    private const LOCK = 'legend-theme.access.running';

    /**
     * A ceiling on one run.
     *
     * Roles times servers times people multiplies quickly - fifty people in a
     * role and twenty servers is a thousand rows from one mapping - and a
     * mapping made by accident should stop at a number rather than at whatever
     * the database gives up at. A run that hits this does nothing at all and
     * says so, rather than granting half of it.
     */
    public const MAX_PAIRS = 20000;

    /** @var array<string, bool>|null */
    private static ?array $managed = null;

    /**
     * On Pelican's cron, every quarter hour.
     *
     * A quarter hour and not a minute, because two of the three things that
     * change the answer already reconcile on their own: saving a mapping does
     * it at once, and signing in does that person. What is left for the timer
     * is somebody being given a role, and finding that out within fifteen
     * minutes is soon enough for a server appearing in a list.
     *
     * Registered only when there is something to do. A panel with no mappings
     * puts no entry on the scheduler at all.
     */
    public static function schedule(Scheduler $schedule): void
    {
        if (!RoleServers::enabled() || RoleServers::rows() === []) {
            return;
        }

        $schedule
            ->call(static function (): void {
                try {
                    self::all();
                } catch (Throwable $exception) {
                    report($exception);
                }
            })
            ->name('legend-theme:access')
            /*
             * A run that overran must not have the next one queue behind it.
             * Two of these at once on the same table is how a pair gets
             * inserted twice, and the unique constraint that would stop it is
             * not one Pelican's schema has.
             */
            ->withoutOverlapping(10)
            ->everyFifteenMinutes();
    }

    /**
     * Reconcile everything.
     *
     * @return array{added: int, removed: int, left: int, pairs: int, capped: bool}
     */
    public static function all(): array
    {
        return self::run(null);
    }

    /**
     * Reconcile one person, on their way in.
     *
     * Signing in is when somebody finds out whether they have a server, so it
     * is the moment worth being right at - and it is cheap, because it asks
     * about one person rather than about everybody.
     *
     * @return array{added: int, removed: int, left: int, pairs: int, capped: bool}
     */
    public static function one(int $userId): array
    {
        return self::run($userId > 0 ? $userId : null, $userId > 0);
    }

    /**
     * @return array{added: int, removed: int, left: int, pairs: int, capped: bool}
     */
    private static function run(?int $onlyUser, bool $scoped = false): array
    {
        $none = ['added' => 0, 'removed' => 0, 'left' => 0, 'pairs' => 0, 'capped' => false];

        if (!RoleServers::enabled()) {
            return $none;
        }

        try {
            $desired = self::desired($onlyUser);
        } catch (Throwable $exception) {
            report($exception);

            return $none;
        }

        if (count($desired) > self::MAX_PAIRS) {
            $result = array_merge($none, ['pairs' => count($desired), 'capped' => true]);

            self::note($result, $scoped);

            return $result;
        }

        try {
            $result = self::locked(fn (): array => self::apply($desired, $scoped ? $onlyUser : null));
        } catch (Throwable $exception) {
            report($exception);

            return $none;
        }

        // Somebody else was already doing it. For a login that is the right
        // answer - the run in flight covers this person too.
        if ($result === null) {
            return $none;
        }

        self::note($result, $scoped);

        return $result;
    }

    /**
     * Run something with the lock, or not at all.
     *
     * Returns null when the lock was not free. A cache driver that cannot lock
     * runs it anyway rather than never running: a duplicated row is a smaller
     * fault than a feature that silently stops on a panel using an array cache.
     *
     * @param  callable(): array{added: int, removed: int, left: int, pairs: int, capped: bool}  $work
     * @return array{added: int, removed: int, left: int, pairs: int, capped: bool}|null
     */
    private static function locked(callable $work): ?array
    {
        try {
            $lock = Cache::lock(self::LOCK, 120);
        } catch (Throwable) {
            return $work();
        }

        $result = $lock->get($work);

        return $result === false ? null : $result;
    }

    /**
     * Every (person, server) pair the mappings ask for, and what it grants.
     *
     * Keyed "person:server". A person in two roles that both reach the same
     * server gets the union of what the two grant, which is the only answer
     * that does not make the order of the list matter.
     *
     * @return array<string, array<int, string>>
     */
    private static function desired(?int $onlyUser): array
    {
        $rows = RoleServers::rows();

        if ($rows === []) {
            return [];
        }

        // Owners first, in one query rather than one per server: a pair whose
        // person owns the server is skipped, and Pelican would delete such a
        // row on that server's next save anyway.
        $serverIds = [];

        foreach ($rows as $row) {
            foreach ($row['servers'] as $id) {
                $serverIds[$id] = true;
            }
        }

        $owners = Server::query()
            ->whereIn('id', array_keys($serverIds))
            ->pluck('owner_id', 'id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $roots = self::rootAdmins();
        $desired = [];

        foreach ($rows as $row) {
            $members = self::membersOf($row['role'], $onlyUser);

            if ($members === []) {
                continue;
            }

            foreach ($row['servers'] as $serverId) {
                // A server that has since been deleted is not in the owners
                // map, and a mapping naming it grants nothing.
                if (!array_key_exists($serverId, $owners)) {
                    continue;
                }

                foreach ($members as $userId) {
                    if ($owners[$serverId] === $userId || in_array($userId, $roots, true)) {
                        continue;
                    }

                    $key = $userId . ':' . $serverId;

                    $desired[$key] = array_key_exists($key, $desired)
                        ? array_values(array_unique(array_merge($desired[$key], $row['permissions'])))
                        : $row['permissions'];
                }
            }
        }

        foreach ($desired as $key => $permissions) {
            sort($permissions);
            $desired[$key] = $permissions;
        }

        return $desired;
    }

    /**
     * Who holds a role, or just the one person being reconciled.
     *
     * @return array<int, int>
     */
    private static function membersOf(int $roleId, ?int $onlyUser): array
    {
        try {
            $role = Role::query()->find($roleId);

            if ($role === null) {
                return [];
            }

            $query = $role->users();

            // On a login only that person is asked about, which turns a query
            // over every member of the role into a query over one row.
            if ($onlyUser !== null) {
                $query->where('users.id', $onlyUser);
            }

            return array_values(array_unique(array_map(
                'intval',
                $query->pluck('users.id')->all(),
            )));
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, int>
     */
    private static function rootAdmins(): array
    {
        try {
            $role = Role::query()->where('name', Role::ROOT_ADMIN)->first();

            if ($role === null) {
                return [];
            }

            return array_map('intval', $role->users()->pluck('users.id')->all());
        } catch (Throwable) {
            // Not knowing who the root admins are is a reason to write nothing
            // for anybody, not a reason to write rows for them.
            return [];
        }
    }

    /**
     * Write the difference.
     *
     * @param  array<string, array<int, string>>  $desired
     * @param  int|null  $onlyUser  Reconcile only this person's rows, so a login
     *                              never removes somebody else's.
     * @return array{added: int, removed: int, left: int, pairs: int, capped: bool}
     */
    private static function apply(array $desired, ?int $onlyUser): array
    {
        $managed = self::managed();

        if ($onlyUser !== null) {
            $managed = array_filter(
                $managed,
                static fn (string $key): bool => str_starts_with($key, $onlyUser . ':'),
                ARRAY_FILTER_USE_KEY,
            );
        }

        // Every pair either side of the comparison cares about, so the existing
        // rows are read in one query rather than one per pair.
        $userIds = [];
        $serverIds = [];

        foreach (array_merge(array_keys($desired), array_keys($managed)) as $key) {
            [$userId, $serverId] = array_pad(explode(':', $key, 2), 2, null);

            $userIds[(int) $userId] = true;
            $serverIds[(int) $serverId] = true;
        }

        if ($userIds === [] || $serverIds === []) {
            return ['added' => 0, 'removed' => 0, 'left' => 0, 'pairs' => count($desired), 'capped' => false];
        }

        $existing = [];

        foreach (
            Subuser::query()
                ->whereIn('user_id', array_keys($userIds))
                ->whereIn('server_id', array_keys($serverIds))
                ->get(['id', 'user_id', 'server_id', 'permissions']) as $subuser
        ) {
            $existing[$subuser->user_id . ':' . $subuser->server_id] = $subuser;
        }

        $now = now();
        $insert = [];
        $left = 0;

        foreach ($desired as $key => $permissions) {
            $row = $existing[$key] ?? null;

            if ($row === null) {
                [$userId, $serverId] = explode(':', $key, 2);

                $insert[] = [
                    'user_id' => (int) $userId,
                    'server_id' => (int) $serverId,
                    'permissions' => (string) json_encode($permissions),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $managed[$key] = true;

                continue;
            }

            /*
             * A row that already exists and was not made here is left alone
             * entirely - not changed, and not adopted.
             *
             * That is the conservative half of rule one, and it has a visible
             * consequence worth knowing: somebody given three permissions by
             * hand does not gain the mapping's fifteen. Taking over a row an
             * administrator wrote would be a worse surprise than not.
             */
            if (!array_key_exists($key, $managed)) {
                $left++;

                continue;
            }

            $held = is_array($row->permissions) ? $row->permissions : [];

            sort($held);

            if ($held !== $permissions) {
                $row->permissions = $permissions;
                $row->save();
            }
        }

        $added = 0;

        if ($insert !== []) {
            /*
             * insert() rather than create(), and that is rule three: create()
             * would fire SubUserAdded, and its listener sends a panel
             * notification and an email per row.
             */
            foreach (array_chunk($insert, 200) as $chunk) {
                Subuser::query()->insert($chunk);
                $added += count($chunk);
            }
        }

        $removed = 0;

        foreach (array_keys($managed) as $key) {
            if (array_key_exists($key, $desired)) {
                continue;
            }

            unset($managed[$key]);

            $row = $existing[$key] ?? null;

            if ($row === null) {
                // Gone already - removed by hand on Pelican's page, most
                // likely. Dropping it from the index is the whole job.
                continue;
            }

            self::revoke($row);
            $removed++;
        }

        self::remember($managed, $onlyUser);

        return [
            'added' => $added,
            'removed' => $removed,
            'left' => $left,
            'pairs' => count($desired),
            'capped' => false,
        ];
    }

    /**
     * Take one row away, and the SFTP access with it.
     *
     * The event is not fired - see rule three - but the SFTP revocation is not
     * part of the event, it is part of Pelican's deletion service, and leaving
     * it out would mean somebody who can no longer see a server in the panel
     * can still reach its files.
     */
    private static function revoke(Subuser $subuser): void
    {
        $server = null;
        $uuid = null;

        try {
            $server = Server::query()->find($subuser->server_id);
            $uuid = $server === null ? null : $subuser->user?->uuid;
        } catch (Throwable) {
            // Worked out below: without both of these the row still goes, and
            // the SFTP session ages out on its own.
        }

        try {
            $subuser->delete();
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        if ($server === null || !is_string($uuid) || $uuid === '') {
            return;
        }

        try {
            RevokeSftpAccessJob::dispatch($uuid, $server);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Everything this has granted, taken back.
     *
     * The way out, and the reason it exists: switching the feature off stops
     * the sync rather than revoking anything, which is right - an off switch
     * that silently took a hundred people's servers away would be a worse
     * switch. This is the deliberate version of that, and it is a button
     * somebody presses.
     *
     * @return int How many rows went.
     */
    public static function revokeAll(): int
    {
        $managed = self::managed();

        if ($managed === []) {
            return 0;
        }

        $removed = 0;

        /*
         * Under the same lock as a reconciliation. Without it a timer run in
         * flight would put back what this is taking away, one row at a time,
         * and the counts would both be honest and both be wrong.
         */
        try {
            $lock = Cache::lock(self::LOCK, 120);
        } catch (Throwable) {
            $lock = null;
        }

        try {
            if ($lock !== null && !$lock->get()) {
                return 0;
            }

            foreach (array_chunk(array_keys($managed), 200) as $chunk) {
                foreach ($chunk as $key) {
                    [$userId, $serverId] = array_pad(explode(':', $key, 2), 2, null);

                    $row = Subuser::query()
                        ->where('user_id', (int) $userId)
                        ->where('server_id', (int) $serverId)
                        ->first();

                    if ($row === null) {
                        continue;
                    }

                    self::revoke($row);
                    $removed++;
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        } finally {
            try {
                $lock?->release();
            } catch (Throwable) {
                // It times out on its own within two minutes.
            }
        }

        self::remember([], null);

        return $removed;
    }

    /**
     * @return array<string, bool>
     */
    private static function managed(): array
    {
        if (self::$managed !== null) {
            return self::$managed;
        }

        self::$managed = [];

        try {
            $disk = Storage::disk('local');

            if (!$disk->exists(self::MANAGED)) {
                return self::$managed;
            }

            $decoded = json_decode((string) $disk->get(self::MANAGED), true);

            foreach (is_array($decoded) ? $decoded : [] as $key) {
                if (is_string($key) && preg_match('/^[1-9][0-9]{0,9}:[1-9][0-9]{0,9}$/D', $key) === 1) {
                    self::$managed[$key] = true;
                }
            }

            return self::$managed;
        } catch (Throwable) {
            /*
             * An unreadable index is the one failure that has to be loud.
             *
             * Treating it as empty would mean the next run adopts nothing and
             * removes nothing - which is survivable - but it would also mean
             * every row this ever made is now unowned and can never be taken
             * back. So it is reported rather than shrugged at.
             */
            report(new RuntimeException('Could not read ' . self::MANAGED . '. Role server access will not be reconciled until it can be.'));

            return self::$managed = [];
        }
    }

    /**
     * @param  array<string, bool>  $managed
     * @param  int|null  $onlyUser  When set, only this person's keys were
     *                              recomputed, so the rest are kept as they were.
     */
    private static function remember(array $managed, ?int $onlyUser): void
    {
        if ($onlyUser !== null) {
            $others = array_filter(
                self::managed(),
                static fn (string $key): bool => !str_starts_with($key, $onlyUser . ':'),
                ARRAY_FILTER_USE_KEY,
            );

            $managed = array_merge($others, $managed);
        }

        $keys = array_keys($managed);

        sort($keys);

        try {
            Storage::disk('local')->put(self::MANAGED, (string) json_encode($keys));
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        self::$managed = $managed;
    }

    /**
     * What the last run did.
     *
     * In the cache rather than in a setting: it changes on every run, and a
     * .env write per minute is not what .env is for.
     *
     * @param  array{added: int, removed: int, left: int, pairs: int, capped: bool}  $result
     */
    private static function note(array $result, bool $scoped): void
    {
        // A login reconciles one person, so its counts say nothing about the
        // panel and would overwrite the ones that do.
        if ($scoped) {
            return;
        }

        try {
            Cache::put(self::REPORT, $result + ['at' => time()], 86400);
        } catch (Throwable) {
            // The page then says nothing about the last run, which is a page
            // with less on it rather than a sync that did not happen.
        }
    }

    /**
     * @return array{added: int, removed: int, left: int, pairs: int, capped: bool, at: int}|null
     */
    public static function lastRun(): ?array
    {
        try {
            $report = Cache::get(self::REPORT);

            return is_array($report) && isset($report['at']) ? $report : null;
        } catch (Throwable) {
            return null;
        }
    }
}
