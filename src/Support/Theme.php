<?php

namespace LegendDevelopment\Theme\Support;

/**
 * The plugin's own identity, derived from where it is installed.
 *
 * Pelican ties three things to the plugin id: the folder under plugins/, the
 * config namespace, and the translation namespace. Reading the id off the
 * install path instead of hard coding it means renaming the folder (and the
 * matching id in plugin.json plus the config filename) cannot leave a stale
 * literal behind somewhere in the code.
 */
class Theme
{
    /**
     * Permission names are stored in the database against roles, so unlike the
     * id they are a fixed literal: renaming the plugin folder must not silently
     * revoke what an administrator already granted.
     */
    public const PERMISSION_MODEL = 'legendTheme';

    public const PERMISSION_VIEW = 'view ' . self::PERMISSION_MODEL;

    public const PERMISSION_UPDATE = 'update ' . self::PERMISSION_MODEL;

    /**
     * Rearranging pages is its own permission: it changes what everyone sees,
     * without giving away the theme's colours and settings.
     */
    public const PERMISSION_ARRANGE = 'arrange ' . self::PERMISSION_MODEL;

    /**
     * Whether the page arranger is available at all, regardless of who is asking.
     */
    public static function arrangerEnabled(): bool
    {
        return (bool) self::config('arranger', true);
    }

    /**
     * Whether anyone signed in may arrange their own pages, or only the roles
     * that hold the permission.
     */
    public static function arrangerForEveryone(): bool
    {
        return self::arrangerEnabled() && (bool) self::config('arranger_users', false);
    }

    /**
     * May this person arrange - their own pages, at least.
     *
     * Two ways in. The permission is the one that was always here, and it now
     * also carries the right to set the arrangement everyone starts from. The
     * setting is the other: switched on, anybody signed in may rearrange the
     * pages they can see, for themselves only.
     */
    public static function canArrange(): bool
    {
        if (!self::arrangerEnabled()) {
            return false;
        }

        $user = user();

        if ($user === null) {
            return false;
        }

        return self::arrangerForEveryone() || $user->can(self::PERMISSION_ARRANGE);
    }

    /**
     * And may they set the one everyone else starts from.
     *
     * That stays the permission's, always. "Everyone may arrange their own" is
     * not the same sentence as "everyone may arrange yours".
     */
    public static function canArrangeForEveryone(): bool
    {
        return self::arrangerEnabled() && (user()?->can(self::PERMISSION_ARRANGE) ?? false);
    }

    /**
     * Both are worked out from where this file sits, which cannot change while
     * the process runs - and config(), trans() and every settings read go
     * through them, so it is worth not walking the path a few hundred times a
     * request.
     */
    private static ?string $directory = null;

    private static ?string $id = null;

    /**
     * The id as Pelican registers it - lowercased, matching config() and trans().
     */
    public static function id(): string
    {
        return self::$id ??= strtolower(self::directory());
    }

    /**
     * The folder name as it is on disk, which is what Vite resolves against.
     */
    public static function directory(): string
    {
        return self::$directory ??= basename(dirname(__DIR__, 2));
    }

    /**
     * The heading this plugin's pages sit under, taken from plugin.json rather
     * than written out.
     *
     * Renaming the plugin renames the heading, which is the point: the id, the
     * folder and the config namespace are already read off disk for the same
     * reason, and a name typed into four page classes is four places to forget.
     *
     * Read once per request. It is asked for on every page render, to put a
     * word above four links.
     */
    private static ?string $name = null;

    public static function name(): string
    {
        if (self::$name !== null) {
            return self::$name;
        }

        try {
            $path = plugin_path(self::directory(), 'plugin.json');

            if (is_file($path)) {
                $manifest = json_decode((string) file_get_contents($path), true);
                $name = is_array($manifest) ? trim((string) ($manifest['name'] ?? '')) : '';

                if ($name !== '') {
                    return self::$name = mb_substr($name, 0, 40);
                }
            }
        } catch (\Throwable) {
            // No manifest to read. The folder name is the next best thing, and
            // it is the same name with the dashes still in it.
        }

        return self::$name = ucwords(str_replace('-', ' ', self::directory()));
    }

    /**
     * Values that stand in front of the stored ones, while they are set.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $override = null;

    public static function config(string $key, mixed $default = null): mixed
    {
        if (self::$override !== null && array_key_exists($key, self::$override)) {
            return self::$override[$key];
        }

        return config(self::id() . '.' . $key, $default);
    }

    /**
     * Build something as if these settings were the panel's.
     *
     * This exists for one job: the stylesheet is built from a few dozen classes
     * that all read config(), and a person who has chosen their own style needs
     * that same stylesheet built from their values instead. Handing every one of
     * those classes an argument would be a change to all of them for the sake of
     * one caller.
     *
     * It is deliberately awkward to reach - a closure, released in a finally -
     * because a global override left standing would make the settings form show
     * somebody's personal style as though it were the panel's, and saving that
     * form would then write it there.
     *
     * @param  array<string, mixed>  $values
     * @param  callable(): string  $build
     */
    public static function using(array $values, callable $build): string
    {
        $was = self::$override;
        self::$override = $values;

        /*
         * Anything that remembered an answer derived from config has to let go
         * of it, on the way in and on the way out.
         *
         * Several classes hold their answer for the request because they are
         * asked dozens of times for the same one - which is right, and which
         * quietly stops being right inside here, where config is not what it
         * was. Today no preset carries a feature switch or a language, so
         * nothing is actually wrong; that is a fact about the presets rather
         * than about this method, and it would stop being true the first time
         * somebody added a switch to one. Clearing costs three assignments.
         */
        self::forgetDerived();

        try {
            return $build();
        } finally {
            self::$override = $was;
            self::forgetDerived();
        }
    }

    /**
     * The memos that are worked out from settings rather than from arguments.
     *
     * Palette's are not here: those are keyed on the colour handed to them, so
     * they stay true whatever config says.
     */
    private static function forgetDerived(): void
    {
        Features::forget();
        Languages::forget();
        // Written out rather than imported: this is the most foundational
        // class in the plugin and it should not grow a use list reaching down
        // into a sub-namespace. Naming it in full makes the reach visible.
        \LegendDevelopment\Theme\Support\Minecraft\Minecraft::forget();
        \LegendDevelopment\Theme\Support\Games\Ark::forget();
        \LegendDevelopment\Theme\Support\Games\Valheim::forget();
        \LegendDevelopment\Theme\Support\Access\RoleServers::forget();
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    /**
     * One of this plugin's strings, in the language the reader asked for.
     *
     * The locale is passed rather than left to the application's, and the
     * difference is the switch: Pelican has already set the application locale
     * from the reader's own account, and Languages::current() decides whether
     * this plugin honours it - a language it does not carry, or one an
     * administrator has turned off, answers in English instead. The rest of the
     * panel is untouched either way.
     *
     * Laravel falls back per missing key rather than per missing file, so a
     * half-translated language is a working one.
     */
    public static function trans(string $key, array $replace = []): string
    {
        return trans(self::id() . '::' . $key, $replace, Languages::current());
    }
}
