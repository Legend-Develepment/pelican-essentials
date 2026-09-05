<?php

namespace LegendDevelopment\Theme\Providers;

use App\Models\Role;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Events\Login as SignedIn;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use LegendDevelopment\Theme\Http\FavouriteController;
use LegendDevelopment\Theme\Http\LayoutController;
use LegendDevelopment\Theme\Http\QuickController;
use LegendDevelopment\Theme\Http\StatusController;
use LegendDevelopment\Theme\Support\Access\RoleServers;
use LegendDevelopment\Theme\Support\Access\Sync;
use LegendDevelopment\Theme\Support\Areas;
use LegendDevelopment\Theme\Support\Alerts\Schedule as AlertSchedule;
use LegendDevelopment\Theme\Support\AutoUpdate;
use LegendDevelopment\Theme\Support\Background;
use LegendDevelopment\Theme\Support\Bars;
use LegendDevelopment\Theme\Support\CustomCss;
use LegendDevelopment\Theme\Support\Icons;
use LegendDevelopment\Theme\Support\Layout;
use LegendDevelopment\Theme\Support\Layouts;
use LegendDevelopment\Theme\Support\Login;
use LegendDevelopment\Theme\Support\NavLinks;
use LegendDevelopment\Theme\Support\Notice;
use LegendDevelopment\Theme\Support\Presets;
use LegendDevelopment\Theme\Support\Quick;
use LegendDevelopment\Theme\Support\Preview;
use LegendDevelopment\Theme\Support\Runtime;
use LegendDevelopment\Theme\Support\ServerConsole;
use LegendDevelopment\Theme\Support\ServerControls;
use LegendDevelopment\Theme\Support\Favourites;
use LegendDevelopment\Theme\Support\Features;
use LegendDevelopment\Theme\Support\FullPreview;
use LegendDevelopment\Theme\Support\ServerList;
use LegendDevelopment\Theme\Support\SidebarFooter;
use LegendDevelopment\Theme\Support\Terminal;
use LegendDevelopment\Theme\Support\Typography;
use LegendDevelopment\Theme\Support\UserTheme;
use LegendDevelopment\Theme\Support\Theme;
use Throwable;

class ThemeServiceProvider extends ServiceProvider
{
    /** The settings block, built once and handed back to every re-fire. */
    private static ?string $settings = null;

    private static ?string $stylesheet = null;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The permissions and the Theme page are registered either way, so the
        // theme can be switched back on from a panel that currently renders
        // completely untouched.
        $this->registerPermissions();
        $this->registerAutoUpdate();

        /*
         * Before the return below, and that is a fix rather than tidying.
         *
         * These are where the page arranger and the stars on the server
         * cards save to. They were registered further down, past the point
         * where a panel with the styling switched off stops reading this
         * method - so on such a panel the stars drew, the browser posted
         * them, and the post hit nothing at all. Neither feature has
         * anything to do with whether the theme is painting.
         */
        $this->registerLayoutRoute();

        /*
         * And this one, also before the return: whether the panel is being
         * painted has nothing to do with whether somebody's role should have
         * given them a server.
         */
        $this->registerAccessSync();

        if (Presets::isDisabled()) {
            return;
        }

        // STYLES_AFTER puts the theme behind Pelican's own stylesheet, which is
        // registered on STYLES_BEFORE. Registering here rather than in the plugin
        // class means the hook is added once, not once per panel.
        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            // The administrator's own CSS goes last of all, so it needs no
            // !important to win from anything the theme itself emitted.
            fn () => new HtmlString(
                $this->stylesheet() . $this->settings() . CustomCss::style() . Runtime::script(),
            ),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn () => new HtmlString($this->script()),
        );

        // One line across the top of the panel. Static markup in the first
        // response, for the reason spelled out in Notice::html().
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_START,
            fn () => new HtmlString($this->notice()),
        );

        /*
         * The sign-in screen: a line above the form, and links under it.
         *
         * The hook names are written out rather than taken from
         * PanelsRenderHook, deliberately. A constant that a future Filament
         * renames is a fatal on every page; a string it no longer recognises is
         * simply a hook nobody renders. On a login screen, the second is the
         * only acceptable way to be wrong.
         */
        if (Features::enabled(Features::LOGIN)) {
            FilamentView::registerRenderHook(
                'panels::auth.login.form.before',
                fn () => new HtmlString($this->attempt(fn (): string => Login::above())),
            );

            FilamentView::registerRenderHook(
                'panels::auth.login.form.after',
                fn () => new HtmlString($this->attempt(fn (): string => Login::links())),
            );
        }

        // The bottom of the sidebar, which Pelican leaves empty. Wrapped like
        // the announcement bar: a hook that throws takes every page with it,
        // and a line of text is not worth that.
        FilamentView::registerRenderHook(
            SidebarFooter::HOOK,
            fn () => new HtmlString($this->attempt(fn (): string => SidebarFooter::html())),
        );

        if (Features::enabled(Features::BARS)) {
            Bars::register();
        }

        /*
         * One control in the top bar: where next.
         *
         * The hook name is written out rather than taken from PanelsRenderHook
         * for the reason given above the login hooks - but unlike those it was
         * read from Filament's own source before being used rather than
         * remembered, so this is a string by policy and not by hope.
         *
         * global-search.after, and the choice is exact rather than approximate.
         * Filament's topbar puts the search box, the notifications bell and the
         * account menu inside one flex row, .fi-topbar-end, and renders
         * topbar.end *after* that row closes. So topbar.end put this past the
         * avatar, at the far edge, away from the group it belongs to.
         * global-search.after is the last hook inside the row and lands
         * immediately before the bell - which is where somebody looks for their
         * own shortcuts, next to their own notifications and their own account.
         *
         * That row also carries x-persist, so this survives a Livewire
         * navigation rather than being rebuilt with the page. Nothing here
         * depends on that: every listener is on the document. It is simply
         * cheaper.
         */
        if (Features::maySee(Features::QUICK)) {
            FilamentView::registerRenderHook(
                'panels::global-search.after',
                fn () => new HtmlString($this->attempt(fn (): string => Quick::html())),
            );
        }

        // The power buttons and the way back to the console, on every page
        // inside a server. Its own render hook, registered once here.
        ServerControls::register();
    }

    /**
     * The announcement bar. Wrapped, because a render hook that throws takes
     * every page with it, and a line of text is not worth that.
     */
    private function notice(): string
    {
        /*
         * The preview bar first, and outside the announcements switch.
         *
         * It is not an announcement - it is the page saying what it is. A panel
         * showing colours that are not saved has to say so on every page,
         * including one where the administrator has switched announcements off,
         * or the preview becomes indistinguishable from the panel and somebody
         * spends an afternoon wondering why a setting will not stick.
         */
        $bar = $this->attempt(fn (): string => FullPreview::html());

        if (!Features::enabled(Features::ANNOUNCEMENTS)) {
            return $bar;
        }

        return $bar . $this->attempt(fn (): string => Notice::html());
    }

    /**
     * A render that is allowed to fail without taking the page with it.
     *
     * The fallback is a parameter, and that is a fix rather than a flourish.
     * This used to be `attempt(callable): string` while the same method in
     * three other classes here was `attempt(callable, mixed): mixed` - so a
     * call written in the shape of its three siblings compiled, ran, and was
     * wrong in a way nothing reported.
     *
     * What happened is worth writing down. PHP does not object to extra
     * arguments passed to a userland function, so the fallback was accepted and
     * silently dropped. The closure then returned an array, the `: string`
     * return type could not coerce it, and the TypeError was thrown *at the
     * return statement* - which is inside the try below. So it was caught by
     * the very handler meant for a failing render, and the caller was handed
     * '' instead of a list.
     *
     * The visible result was that every starred server vanished on reload:
     * Favourites::for() was read correctly, turned into an empty string on the
     * way out, and the browser drew from an empty list - then saved that list
     * back over the real one on the next click.
     *
     * @param  callable(): mixed  $render
     */
    private function attempt(callable $render, mixed $fallback = ''): mixed
    {
        try {
            return $render();
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * Hands the automatic update check to the scheduler, and only where the
     * scheduler exists - resolving it for every web request would be building
     * something nothing is going to read.
     */
    private function registerAutoUpdate(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        // After booting: the schedule is resolved once the rest of the panel is
        // up, so reading the setting cannot land before config is in place.
        $this->app->booted(function (): void {
            try {
                $schedule = $this->app->make(Schedule::class);

                AutoUpdate::schedule($schedule);

                // The watchdog rides the same cron entry Pelican already
                // requires. Registered beside the updater rather than in its
                // own place, because there is only one scheduler and both of
                // them fail the same way if it is not running.
                AlertSchedule::register($schedule);

                // The public status page, rebuilt every minute so what a
                // visitor sees is a minute old at worst rather than however
                // long ago somebody last opened it.
                AlertSchedule::status($schedule);

                // Servers tied to a role. On the same cron entry as the rest,
                // and only worth a quarter hour because saving the mapping and
                // signing in both reconcile on their own - the timer is here
                // for the third case, somebody being given a role.
                Sync::schedule($schedule);
            } catch (Throwable) {
                // Never let a scheduling problem stop artisan from running.
            }
        });
    }

    /**
     * Reconcile one person's role servers as they sign in.
     *
     * The timer catches a role somebody was given an hour ago; this catches the
     * moment they would notice. It asks about one person rather than about
     * everybody, so it is a handful of queries rather than a sweep, and it is
     * wrapped because a sign-in must not fail over this - somebody locked out
     * of the panel by an access feature would be the worst possible way for
     * this to go wrong.
     */
    private function registerAccessSync(): void
    {
        /*
         * Aliased, and not for neatness: this file already imports a Login -
         * the plugin's own, for the sign-in screen - and two classes under one
         * short name is the fault tools/check-classes.js exists to catch.
         */
        Event::listen(SignedIn::class, static function (SignedIn $event): void {
            try {
                if (!RoleServers::enabled() || RoleServers::rows() === []) {
                    return;
                }

                $id = $event->user->getAuthIdentifier();

                if (is_numeric($id)) {
                    Sync::one((int) $id);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    /**
     * Where the page arranger saves to. Registered outside the panels because it
     * is a plain endpoint, not a page.
     */
    private function registerLayoutRoute(): void
    {
        try {
            Route::middleware(['web', 'auth'])
                ->post('/legend-theme/layout', LayoutController::class);

            // The stars on the server cards. Behind the same middleware: it
            // writes a file belonging to whoever is signed in, so there has to
            // be somebody signed in.
            Route::middleware(['web', 'auth'])
                ->post('/legend-theme/favourites', FavouriteController::class);

            /*
             * What the top bar's switcher shows when it is opened.
             *
             * A GET, because it changes nothing - and behind auth, because what
             * it answers with is the list of servers this person may open. It
             * asks accessibleServers() rather than deciding that itself, so it
             * can only ever show somebody what Pelican would already show them.
             */
            Route::middleware(['web', 'auth'])
                ->get('/legend-theme/quick', QuickController::class);

            /*
             * The one route with no auth on it.
             *
             * Which is the entire point of a status page - somebody whose
             * server has stopped does not have an account on the panel. It is
             * throttled instead: the page is served from a snapshot and cannot
             * reach a node on its own, but a route anybody may call still
             * deserves a ceiling. Sixty a minute per address is far above what
             * a person does and far below what a script does.
             *
             * StatusController answers 404 unless an administrator has named at
             * least one server, so on a panel that changed nothing this route
             * exists and serves nothing.
             */
            Route::middleware(['web', 'throttle:60,1'])
                ->get('/status', StatusController::class)
                ->name('legend-theme.status');

            /*
             * And one page per person, at a slug they chose.
             *
             * The pattern is on the route rather than only in the controller,
             * so a request for /status/../../etc never reaches PHP at all. The
             * controller checks it again before looking anything up, because a
             * route constraint is a filter and not a promise about what a
             * string contains.
             */
            Route::middleware(['web', 'throttle:60,1'])
                ->get('/status/{slug}', [StatusController::class, 'user'])
                ->where('slug', '[a-z0-9][a-z0-9-]{1,31}')
                ->name('legend-theme.status.user');
        } catch (Throwable) {
            // Routes are cached; `php artisan optimize:clear` brings it back.
        }
    }

    /**
     * Adds a "Legend Theme" section with View and Update checkboxes to the role
     * editor. Pelican creates the permission records itself the first time a
     * role is saved with them ticked, so there is nothing to seed.
     */
    private function registerPermissions(): void
    {
        /*
         * The three broad ones, and then one per feature.
         *
         * view and update still open everything, which is what keeps this from
         * being a breaking change: a role that could reach the plugin before
         * can still reach all of it. The per-feature permissions are the narrow
         * way in - somebody who should write announcements and touch nothing
         * else gets "announcements" and no more.
         */
        Role::registerCustomPermissions([
            Theme::PERMISSION_MODEL => array_merge(
                ['view', 'update', 'arrange'],
                Features::permissions(),
            ),
        ]);

        Role::registerCustomModelIcon(Theme::PERMISSION_MODEL, 'tabler-adjustments');
    }

    /**
     * The compiled theme. Vite picks the file up through the glob over
     * plugins/<id>/resources/css in the panel's vite.config.js, so `yarn build`
     * is all that is needed.
     */
    private function stylesheet(): string
    {
        /*
         * Held for the request, like settings() below it.
         *
         * Blade::render() is not a string operation: it writes the template to
         * a file named by its hash under storage/framework/views, compiles it if
         * that file is not already there, and renders it. Once per request is
         * one file_exists and an include; the memo makes it once rather than
         * once per render hook that asks.
         */
        if (self::$stylesheet !== null) {
            return self::$stylesheet;
        }

        $asset = 'plugins/' . Theme::directory() . '/resources/css/theme.css';

        try {
            return self::$stylesheet = Blade::render("@vite(['{$asset}'])");
        } catch (Throwable) {
            // Assets have not been built yet - `yarn build` in the panel directory
            // fixes it. Never take the panel down over a stylesheet. Not
            // memoised: a build finishing mid-request is far-fetched, but an
            // empty string cached over a real answer is not worth the risk.
            return '';
        }
    }

    /**
     * The bar levelling script, and - only for someone who may edit the layout -
     * the page arranger with what it needs to start.
     */
    private function script(): string
    {
        $directory = Theme::directory();
        $assets = ["plugins/{$directory}/resources/js/bars.js"];
        $bootstrap = '';

        // Only where the box it drives can appear. The script is inert without
        // it, so this is about not shipping bytes to every page of the panel for
        // a feature that lives on four of them.
        if (Features::maySee(Features::SETTINGS_SEARCH)) {
            $assets[] = "plugins/{$directory}/resources/js/settings-search.js";
        }

        /*
         * The stars on the server cards. Its wording is handed over rather than
         * written into the script, so the one place strings live stays the one
         * place strings live.
         */
        if (Features::maySee(Features::FAVOURITES)) {
            $assets[] = "plugins/{$directory}/resources/js/favourites.js";

            $bootstrap .= '<script>window.__ldFav=' . json_encode([
                /*
                 * The list itself, in the first response.
                 *
                 * Handed over rather than fetched, because the stars are drawn
                 * as soon as the cards are and a request to find out what to
                 * draw would mean a list that appears unstarred for a moment
                 * on every single page load.
                 */
                'starred' => $this->attempt(fn (): array => Favourites::for(), []),
                'save' => url('/legend-theme/favourites'),
                'token' => csrf_token(),

                'off' => Theme::trans('settings.servers.favourite'),
                'on' => Theme::trans('settings.servers.favourited'),
                'tab' => Theme::trans('settings.servers.favourites_tab'),
                'empty' => Theme::trans('settings.servers.favourites_empty'),
                'failed' => Theme::trans('settings.servers.favourites_failed'),
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
        }

        /*
         * The top bar's switcher.
         *
         * What is handed over is an address, a token and some words - not the
         * server list. Nothing is fetched until somebody opens it, so this
         * costs a few hundred bytes on a page nobody uses it on rather than a
         * query on every page of the panel.
         */
        if (Features::maySee(Features::QUICK)) {
            $assets[] = "plugins/{$directory}/resources/js/quick.js";

            $bootstrap .= '<script>window.__ldQuick=' . json_encode(
                $this->attempt(fn (): array => Quick::bootstrap(), []),
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
            ) . ';</script>';
        }

        if (Theme::canArrange()) {
            $assets[] = "plugins/{$directory}/resources/js/arrange.js";

            $path = request()->path();
            $canShare = Theme::canArrangeForEveryone();

            /*
             * Appended, not assigned - and that one character was a bug.
             *
             * Three features put a line of configuration in here and this one
             * replaced the two above it instead of joining them. It only went
             * wrong for somebody who may rearrange pages, which is to say for
             * administrators and for nobody testing as an ordinary user, and
             * what it looked like from the outside was that starred servers
             * emptied themselves: window.__ldFav was never defined, the script
             * read no starred list, and the first click saved that empty list
             * back over the real one.
             */
            $bootstrap .= '<script>window.__ldArrange=' . json_encode([
                'canEdit' => true,
                // Whether the editor offers "for everyone" at all. Checked again
                // on the way in - this only decides what is drawn.
                'canShare' => $canShare,
                'url' => url('/legend-theme/layout'),
                'page' => Layouts::pageKey($path),
                // Each scope on its own, so switching between them shows what
                // that scope holds rather than the layers added together.
                'merged' => (object) Layouts::for($path),
                'shared' => (object) ($canShare ? Layouts::scoped($path, Layouts::SHARED) : []),
                /*
                 * The roles, and what each of them has arranged on this page.
                 *
                 * Sent with the page rather than fetched when the picker
                 * changes: it is the same shape as the shared one above, it
                 * saves an endpoint that would exist for nothing else, and a
                 * panel with a hundred roles is not a panel. Only for somebody
                 * who may set them - for anybody else this is two empty
                 * objects and no query.
                 */
                'roles' => (object) ($canShare ? Layouts::roleOptions() : []),
                'roleLayouts' => (object) ($canShare ? $this->attempt(
                    fn (): array => Layouts::roleLayouts($path),
                    [],
                ) : []),
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
        }

        try {
            $list = implode("','", $assets);

            return $bootstrap . Blade::render("@vite(['{$list}'])");
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * The panel's own settings block, and then - for anyone who has chosen a
     * style of their own - the same block built from theirs.
     *
     * Twice rather than once with the values swapped in, because a second block
     * after the first is the whole mechanism: everything in it wins by being
     * later, and a person who has chosen nothing gets exactly the page they got
     * before this existed. It costs a few kilobytes on the pages of the people
     * who asked for it.
     */
    private function settings(): string
    {
        /*
         * Built once per request.
         *
         * Render hooks re-fire inside Livewire responses - the lesson the blank
         * console cost a week - so this closure runs again on every interaction
         * with the page, not only on the page. Everything it reads is fixed for
         * the life of the request: the settings, who is asking, and the path.
         * Building it twice was already waste; building it twice over, once for
         * the panel and once for somebody's own style, is twice that.
         */
        if (self::$settings !== null) {
            return self::$settings;
        }

        /*
         * A full-page preview replaces the panel's own settings for this one
         * request, and nothing else about the request changes.
         *
         * Through Theme::using(), which is the mechanism a person's own style
         * already uses and is released in a finally - so a pending value cannot
         * be left standing where a form would read it back as the panel's and
         * save it there. Only the stylesheet is built from it; every other
         * reader in the request still sees what is stored.
         *
         * A person's own style is skipped while previewing. The question being
         * asked is what the panel looks like, and answering it with somebody's
         * personal override on top would answer a different one.
         */
        // Not wrapped in attempt(): that one returns a string and has no
        // fallback, and values() already answers null to everything it meets.
        $preview = FullPreview::values();

        if ($preview !== null) {
            $css = $this->attempt(
                fn (): string => Theme::using($preview, fn (): string => $this->settingsCss()),
            );

            return self::$settings = '<style>' . $css . '</style>';
        }

        $panel = $this->settingsCss();

        $own = $this->attempt(fn (): string => UserTheme::css(fn (): string => $this->settingsCss()));

        return self::$settings = '<style>' . $panel . $own . '</style>';
    }

    /**
     * Settings that the stylesheet reads as custom properties, plus the opt-outs
     * for the effects that are toggled off.
     */
    private function settingsCss(): string
    {
        /*
         * The appearance tokens live in Support\Preview now, and the panel is
         * one of its two callers.
         *
         * Not because the panel needed anything, but because the preview box
         * needs exactly these and must not have its own copy of them. A preview
         * built from a second set of rules is a second place the theme can be
         * wrong, and one that disagrees with the panel is worse than no preview
         * at all. Asking for them on `:root` is what this method always did.
         */
        $css = Preview::tokens();

        $css .= Background::css();
        $css .= Icons::css();
        $css .= Bars::css();

        // The shape of the panel: the rail, and the sidebar, topbar and card
        // styles. Before the per-area block, so an area can still override it.
        $css .= Layout::css();

        // How a server card is drawn, before the per-area block below.
        $css .= ServerList::css();
        $css .= ServerConsole::css();

        // The panel's lettering, and nothing at all when it has not been
        // changed - see Typography::css() for why that is the whole rule rather
        // than a custom property.
        $css .= Typography::css();

        // The terminal's own colours and behaviour. Emitted as custom
        // properties that the inlined runtime reads back, because xterm draws
        // to a canvas and a stylesheet cannot reach the glyphs.
        $css .= Terminal::css();

        // A console page opened as a window of its own, stripped to the
        // console. After the layout, since it undoes most of it.
        $css .= ServerControls::bareCss();

        // Which notice this is, so a browser can tell a new one from the one it
        // closed - read before the first paint, so nothing flashes.
        $css .= Features::enabled(Features::ANNOUNCEMENTS) ? Notice::css() : '';

        // The fetched favicons, painted over the icon Filament rendered for
        // each link. Stored data, so nothing here reaches out to a network.
        $css .= Features::enabled(Features::NAV_LINKS) ? NavLinks::css() : '';

        $css .= Features::enabled(Features::LOGIN) ? Login::css() : '';

        // Last, so a per-area override wins from every global setting above.
        $css .= Areas::css();

        // The saved page arrangement. Emitted server side, so the blocks are in
        // place on the first paint rather than jumping once a script runs.
        $css .= Layouts::css(request()->path());

        // The rules only, with no <style> around them: settings() wraps the two
        // blocks it builds together in one.
        return $css;
    }

    /**
     * The login screen: its own picture, card width and card blur.
     */
}
