<?php

namespace LegendDevelopment\Theme\Support\Access;

use App\Enums\SubuserPermission;
use App\Models\Role;
use App\Models\Server;
use Illuminate\Support\Facades\Storage;
use LegendDevelopment\Theme\Support\Features;
use RuntimeException;
use Throwable;

/**
 * Which servers a role gets, and what its members may do on them.
 *
 * The list only. Making it true is Access\Sync's job, and the two are separate
 * because this one is a setting an administrator edits and that one writes to
 * Pelican's own tables.
 *
 * **Pelican has no role-to-server link, and this does not invent one.** What it
 * has is subusers - a row per person per server carrying a list of permissions -
 * and that row is what every part of the panel reads: the server list is a query
 * over owners and subusers, the policy that decides whether a page opens checks
 * the same row, and the token Wings is handed is built from those permissions.
 * A separate access list of this plugin's own would be a server that appears in
 * nobody's list, or one that appears and will not open.
 *
 * So a mapping here is an instruction, and Sync keeps Pelican's own rows
 * matching it.
 *
 * The one thing Pelican does have is roles tied to *nodes* - see
 * User::accessibleNodes() - and it is worth knowing why that is not this. Node
 * scoping only applies to somebody who already passes `viewAny` on servers,
 * which is to say an administrator. It is a way to divide a panel between
 * administrators, not a way to give an ordinary user a server.
 */
class RoleServers
{
    /**
     * The mappings, in a file rather than in .env.
     *
     * A list with a list inside it, like the monitors and the announcements -
     * one .env value holding a role, twenty server ids and fifteen permissions
     * would be a parser nobody wants to maintain.
     */
    private const PATH = 'legend-theme/role-servers.json';

    /** How many mappings, and how many servers in one. */
    public const MAX = 50;

    public const MAX_SERVERS = 200;

    /**
     * What a mapping grants when nobody has said otherwise.
     *
     * Enough to use a server and not enough to change what it is: the console
     * and the power buttons, the files, the backups and the activity log, and
     * nothing that edits the server, its users, its databases or its
     * allocations. Somebody who wants more says so; a default that handed over
     * user management would be a default that quietly made everybody an
     * administrator of that server.
     *
     * WebsocketConnect is in it because without it the console page connects to
     * nothing, and every other permission is read through that socket.
     */
    public const PRESET = [
        'websocket.connect',
        'control.console',
        'control.start',
        'control.stop',
        'control.restart',
        'file.read',
        'file.read-content',
        'file.create',
        'file.update',
        'file.archive',
        'backup.read',
        'backup.create',
        'backup.download',
        'activity.read',
        'startup.read',
    ];

    /** @var array<int, array{role: int, servers: array<int, int>, permissions: array<int, string>}>|null */
    private static ?array $cached = null;

    public static function enabled(): bool
    {
        return Features::enabled(Features::ACCESS);
    }

    public static function forget(): void
    {
        self::$cached = null;
    }

    /**
     * Every mapping, cleaned.
     *
     * @return array<int, array{role: int, servers: array<int, int>, permissions: array<int, string>}>
     */
    public static function rows(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        self::$cached = [];

        try {
            $disk = Storage::disk('local');

            if (!$disk->exists(self::PATH)) {
                return self::$cached;
            }

            $decoded = json_decode((string) $disk->get(self::PATH), true);

            return self::$cached = self::clean(is_array($decoded) ? $decoded : []);
        } catch (Throwable) {
            // Unreadable storage is a panel with no mappings, which grants
            // nothing - the safe direction for a file that hands out access.
            return self::$cached = [];
        }
    }

    /**
     * Returns whether the list actually reached the disk.
     *
     * @param  array<int|string, mixed>  $rows
     */
    public static function save(array $rows): bool
    {
        $clean = self::clean($rows);

        try {
            if (Storage::disk('local')->put(self::PATH, (string) json_encode($clean, JSON_PRETTY_PRINT)) === false) {
                report(new RuntimeException(
                    'Could not write ' . self::PATH . ' to the local disk. Check that '
                    . storage_path('app') . ' belongs to the user the panel runs as.',
                ));

                return false;
            }
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        self::$cached = $clean;

        return true;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return array<int, array{role: int, servers: array<int, int>, permissions: array<int, string>}>
     */
    public static function clean(array $rows): array
    {
        $out = [];

        foreach (array_slice(array_values($rows), 0, self::MAX) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $role = (int) ($row['role'] ?? 0);

            if ($role <= 0) {
                continue;
            }

            $servers = self::ids($row['servers'] ?? []);

            // A mapping with no servers grants nothing, and keeping it would
            // only be a row somebody has to look at twice.
            if ($servers === []) {
                continue;
            }

            /*
             * Two mappings for one role are merged rather than both kept.
             *
             * They would both be applied anyway - Sync unions what a person
             * gets from everything they hold - so keeping them apart would show
             * a list that does not say what is actually granted.
             */
            if (array_key_exists($role, $out)) {
                $servers = array_values(array_unique(array_merge($out[$role]['servers'], $servers)));
                $permissions = array_merge($out[$role]['permissions'], self::permissions($row['permissions'] ?? null));
            } else {
                $permissions = self::permissions($row['permissions'] ?? null);
            }

            $out[$role] = [
                'role' => $role,
                'servers' => array_slice($servers, 0, self::MAX_SERVERS),
                'permissions' => array_values(array_unique($permissions)),
            ];
        }

        return array_values($out);
    }

    /**
     * A list of ids, as ids.
     *
     * @return array<int, int>
     */
    private static function ids(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', $value),
            static fn (int $id): bool => $id > 0,
        )));

        sort($ids);

        return $ids;
    }

    /**
     * Only permissions Pelican actually has, and never an empty list.
     *
     * An empty one would be a subuser row that grants nothing, which is worse
     * than no row: the server appears in their list and every page inside it
     * refuses them.
     *
     * @return array<int, string>
     */
    public static function permissions(mixed $value): array
    {
        $known = self::known();
        $out = [];

        foreach (is_array($value) ? $value : [] as $permission) {
            $permission = is_scalar($permission) ? (string) $permission : '';

            if (in_array($permission, $known, true)) {
                $out[$permission] = true;
            }
        }

        if ($out === []) {
            return self::PRESET;
        }

        // Without it the console connects to nothing, and everything else here
        // is read through that socket. Pelican's own form does the same.
        $out['websocket.connect'] = true;

        $permissions = array_keys($out);
        sort($permissions);

        return $permissions;
    }

    /**
     * Every permission Pelican defines, as its stored string.
     *
     * Read from the enum rather than listed here, so a permission Pelican adds
     * is offered the release it appears in rather than the release somebody
     * notices.
     *
     * @return array<int, string>
     */
    public static function known(): array
    {
        try {
            return array_map(
                static fn (SubuserPermission $case): string => $case->value,
                SubuserPermission::cases(),
            );
        } catch (Throwable) {
            return self::PRESET;
        }
    }

    /**
     * The permissions grouped the way Pelican groups them, for the form.
     *
     * `control.console` and `control.start` become "Control", which is how they
     * read on Pelican's own subuser dialog - forty checkboxes in one flat list
     * is a form nobody finishes.
     *
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        $groups = [];

        foreach (self::known() as $permission) {
            $at = strpos($permission, '.');
            $group = $at === false ? 'other' : substr($permission, 0, $at);

            $groups[$group][$permission] = self::label($permission);
        }

        ksort($groups);

        return $groups;
    }

    /** The part after the dot, as words. */
    public static function label(string $permission): string
    {
        $at = strpos($permission, '.');
        $short = $at === false ? $permission : substr($permission, $at + 1);

        return ucfirst(str_replace('-', ' ', $short));
    }

    /** @return array<int, string> */
    public static function roleOptions(): array
    {
        try {
            return Role::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
                ->mapWithKeys(static fn (Role $role): array => [(int) $role->id => (string) $role->name])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Every server, for the picker.
     *
     * Capped, and the cap is not decoration: this fills a multiple select, and
     * a panel with four thousand servers would send four thousand options to
     * the browser on a page nobody opened for that.
     *
     * @return array<int, string>
     */
    public static function serverOptions(): array
    {
        try {
            return Server::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->limit(1000)
                ->get()
                ->mapWithKeys(static fn (Server $server): array => [
                    (int) $server->id => (string) $server->name,
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

}
