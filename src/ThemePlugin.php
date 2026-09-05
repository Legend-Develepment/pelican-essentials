<?php

namespace LegendDevelopment\Theme;

use App\Contracts\Plugins\HasPluginSettings;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use LegendDevelopment\Theme\Filament\Admin\Pages\AdvancedSettings;
use LegendDevelopment\Theme\Filament\Admin\Pages\DuplicateServer;
use LegendDevelopment\Theme\Filament\Admin\Pages\EggArtwork;
use LegendDevelopment\Theme\Filament\Admin\Pages\GameSettings;
use LegendDevelopment\Theme\Filament\Admin\Pages\ServerAccess;
use LegendDevelopment\Theme\Filament\Admin\Pages\MinecraftSettings;
use LegendDevelopment\Theme\Filament\App\Pages\Appearance;
use LegendDevelopment\Theme\Filament\App\Pages\MyStatus;
use LegendDevelopment\Theme\Filament\Pages\Favourites;
use LegendDevelopment\Theme\Filament\Admin\Pages\Alerts;
use LegendDevelopment\Theme\Filament\Admin\Pages\Announcements;
use LegendDevelopment\Theme\Filament\Admin\Pages\Backups;
use LegendDevelopment\Theme\Filament\Admin\Pages\LanguageSettings;
use LegendDevelopment\Theme\Http\PanelLanguage;
use LegendDevelopment\Theme\Filament\Admin\Pages\LoginScreen;
use LegendDevelopment\Theme\Filament\Admin\Pages\Look;
use LegendDevelopment\Theme\Filament\Admin\Pages\NavigationLinks;
use LegendDevelopment\Theme\Filament\Admin\Pages\PanelPages;
use LegendDevelopment\Theme\Filament\Admin\Pages\PublicStatus;
use LegendDevelopment\Theme\Filament\Admin\Pages\SystemStatus;
use LegendDevelopment\Theme\Filament\Admin\Pages\ThemeSettings;
use LegendDevelopment\Theme\Filament\Admin\Widgets\ThemeStatus;
use LegendDevelopment\Theme\Filament\Server\Pages\ArkConfig;
use LegendDevelopment\Theme\Filament\Server\Pages\GamePlayers;
use LegendDevelopment\Theme\Filament\Server\Pages\MinecraftConfig;
use LegendDevelopment\Theme\Filament\Server\Pages\Modpacks;
use LegendDevelopment\Theme\Filament\Server\Pages\Players;
use LegendDevelopment\Theme\Filament\Server\Pages\Resources;
use LegendDevelopment\Theme\Filament\Server\Pages\ValheimLists;
use LegendDevelopment\Theme\Filament\Server\Pages\PalworldSettings;
use LegendDevelopment\Theme\Support\Features;
use LegendDevelopment\Theme\Support\Layout;
use LegendDevelopment\Theme\Support\Mode;
use LegendDevelopment\Theme\Support\NavLinks;
use LegendDevelopment\Theme\Support\Palette;
use LegendDevelopment\Theme\Support\Quick;
use LegendDevelopment\Theme\Support\Presets;
use LegendDevelopment\Theme\Support\Settings;
use LegendDevelopment\Theme\Support\Status\Pages as StatusPages;
use LegendDevelopment\Theme\Support\Theme;
use LegendDevelopment\Theme\Support\UserTheme;

class ThemePlugin implements HasPluginSettings, Plugin
{
    public function getId(): string
    {
        return Theme::id();
    }

    public function register(Panel $panel): void
    {
        /*
         * After Pelican's own LanguageMiddleware, which is what appending to
         * the panel's list gets: that one is already in it, and a plugin's
         * middleware goes on the end. Registered on every panel rather than
         * one, because a reader's language is not a property of which panel
         * they are looking at.
         */
        $panel->middleware([PanelLanguage::class]);

        // The Theme page is registered even with the theme switched off, so it
        // can be switched back on.
        if ($panel->getId() === 'admin') {
            $panel->pages([
                ThemeSettings::class,
                Look::class,
                PanelPages::class,
                AdvancedSettings::class,
                Announcements::class,
                NavigationLinks::class,
                LoginScreen::class,
                SystemStatus::class,
                DuplicateServer::class,
                MinecraftSettings::class,
                GameSettings::class,
                ServerAccess::class,
                LanguageSettings::class,
                EggArtwork::class,
                Alerts::class,
                Backups::class,
                PublicStatus::class,
            ]);

            /*
             * One block on the dashboard, holding both halves: the version line
             * - because "is there an update" is a question you have before you
             * go looking for the page that answers it - and the machines.
             *
             * Which halves it draws is decided inside the widget rather than
             * here, because either one alone is not worth a card of its own and
             * two cards saying the same plugin's name was one too many.
             */
            $panel->widgets([ThemeStatus::class]);
        }

        /*
         * The one page this plugin puts inside a server rather than in the
         * admin area, and the one that is about a game rather than about the
         * panel. It hides itself on any server that is not Palworld, and the
         * Features list switches it off everywhere.
         */
        if ($panel->getId() === 'server' && Features::enabled(Features::PALWORLD)) {
            $panel->pages([PalworldSettings::class]);
        }

        /*
         * The same shape for Minecraft: a page inside a server, hidden on every
         * server whose egg an administrator has not said is Minecraft.
         *
         * Registered whenever the feature is on, and canAccess() does the rest -
         * a page with no eggs ticked simply never appears, which is the same
         * answer as not registering it and one fewer thing that has to be right
         * at boot.
         */
        if ($panel->getId() === 'server' && Features::enabled(Features::MINECRAFT)) {
            $panel->pages([MinecraftConfig::class, Modpacks::class, Players::class, Resources::class]);
        }

        /*
         * Who is on a server, for the games that answer Valve's query.
         *
         * One page rather than one per game: Rust, ARK, Valheim and the rest
         * answer the same packet, so four pages would differ in nothing but the
         * word at the top. canAccess() hides it on every server whose egg an
         * administrator has not ticked, which is the same shape the Minecraft
         * pages use.
         */
        if ($panel->getId() === 'server' && Features::enabled(Features::GAME_PLAYERS)) {
            $panel->pages([GamePlayers::class]);
        }

        /*
         * The two games that keep a file rather than a set of start-up
         * variables: ARK's GameUserSettings.ini, and Valheim's three name
         * lists. Neither gets a settings form beyond that, and that is a
         * finding rather than a gap - everything else about both is a start-up
         * variable, and Pelican's Startup page already edits those.
         *
         * Registered together on one switch, and canAccess() does the rest: a
         * game with no eggs ticked never appears, which is the same answer as
         * not registering it and one fewer thing that has to be right at boot.
         */
        if ($panel->getId() === 'server' && Features::enabled(Features::GAMES)) {
            $panel->pages([ArkConfig::class, ValheimLists::class]);
        }

        /*
         * Where somebody picks the panel's look for themselves. In the client
         * panel because that is the one everybody reaches, and only when an
         * administrator has offered something to pick from.
         */
        if ($panel->getId() === 'app' && UserTheme::enabled()) {
            $panel->pages([Appearance::class]);
        }

        /*
         * A status page of somebody's own.
         *
         * In the client panel because it belongs to whoever owns the servers,
         * and registered only when an administrator has allowed user pages at
         * all - canAccess() would hide it either way, but a page that is not
         * registered is one fewer route on a panel that does not want the
         * feature.
         */
        if ($panel->getId() === 'app' && StatusPages::enabled()) {
            $panel->pages([MyStatus::class]);
        }

        /*
         * Everything this person has starred, in both panels they might be in.
         *
         * Registered twice rather than once and linked to, because the two
         * panels are different places to be: somebody working in admin should
         * not be sent out to the client panel to look at a list that includes
         * the admin pages they starred. The class is the same and the slug is
         * the same, so the address only differs by which panel it is under -
         * which is exactly what Quick::pageUrl() resolves when it builds the
         * way in from the top bar.
         *
         * Not in the server panel: that one has a tenant in every address, and
         * a list of favourites is not a property of the server you happen to be
         * inside. From there the top bar points at the client panel's copy.
         */
        if (in_array($panel->getId(), ['admin', 'app'], true) && Features::enabled(Features::QUICK)) {
            $panel->pages([Favourites::class]);
        }

        /*
         * And in the user menu, on both panels a person actually sits in.
         *
         * A row in the client panel's sidebar was where this started and it was
         * not enough: people spend their time inside a server, on the console,
         * where that sidebar is a different one. So the way to it goes where
         * "Account" and "Sign out" already are - which is also where somebody
         * looks when they want to change something about themselves rather than
         * about a server.
         *
         * The address is built from the panel rather than from the page, the
         * way Pelican builds its own link to the admin area, because from inside
         * a server the page belongs to a panel this one is not.
         */
        if (in_array($panel->getId(), ['app', 'server'], true) && UserTheme::enabled()) {
            $panel->userMenuItems([
                Action::make('ld-appearance')
                    ->label(fn (): string => Theme::trans('appearance.nav_label'))
                    ->icon('tabler-palette')
                    ->url(fn (): string => rtrim(Filament::getPanel('app')->getUrl(), '/') . '/appearance')
                    ->visible(fn (): bool => user() !== null),
            ]);
        }

        /*
         * And the favourites, in the account menu on every panel.
         *
         * The switcher in the top bar is the fast way in and this is the
         * findable one. They are not the same thing: a dropdown that opens on
         * click is not somewhere anybody looks for a feature they have not been
         * told about, and "Account" is where people look for their own things.
         *
         * The address is resolved rather than written, because from inside a
         * server this page belongs to a panel this one is not - the same
         * problem, and the same answer, as the Appearance row above.
         */
        /*
         * A status page of your own, in the account menu on every panel.
         *
         * It was registered as a page in the client panel and nowhere else,
         * which meant it existed and could not be found: the client panel's
         * sidebar is not the sidebar somebody sees while they are inside a
         * server, which is where people actually spend their time. That is the
         * same reason the Appearance row is here rather than only in a sidebar,
         * written down two features ago and not applied to this one.
         *
         * "Account" is also where somebody looks for a thing that belongs to
         * them rather than to a server, which this is.
         */
        if (StatusPages::enabled()) {
            $panel->userMenuItems([
                Action::make('ld-my-status')
                    ->label(fn (): string => Theme::trans('status.mine_nav_label'))
                    ->icon('tabler-world-share')
                    // Built from the panel rather than from the page: inside a
                    // server this belongs to a panel the current one is not.
                    ->url(fn (): string => rtrim(Filament::getPanel('app')->getUrl(), '/') . '/my-status')
                    ->visible(fn (): bool => user() !== null),
            ]);
        }

        if (Features::enabled(Features::QUICK)) {
            $panel->userMenuItems([
                Action::make('ld-favourites')
                    ->label(fn (): string => Theme::trans('quick.nav_label'))
                    ->icon('tabler-star')
                    ->url(fn (): string => Quick::pageUrl())
                    ->visible(fn (): bool => user() !== null),
            ]);
        }

        if (Presets::isDisabled()) {
            return;
        }

        $panel->colors([
            'primary' => Palette::accent(),
        ]);

        // Which mode the panel opens in, and whether anyone may change it. Two
        // questions that used to be one switch - see Support\Mode.
        Mode::apply($panel);

        // Pelican sets these in its own panel provider, and plugins are loaded at
        // the end of it, so this overrides rather than fights.
        $height = (float) Theme::config('logo_height', 2);

        if ($height > 0) {
            $panel->brandLogoHeight(round(max(1, min(8, $height)), 2) . 'rem');
        }

        $logo = trim((string) Theme::config('logo_url', ''));

        if ($logo !== '') {
            $panel->brandLogo($logo);
        }

        // Extra rows in this panel's navigation. Here rather than in the
        // provider because which panel this is has to be known, and the
        // argument is the one place it is not a guess.
        if (Features::enabled(Features::NAV_LINKS)) {
            NavLinks::apply($panel);
        }
    }

    public function boot(Panel $panel): void
    {
        // Here rather than in register(): Pelican sets some of these itself
        // while building the panel - the admin panel makes its sidebar
        // collapsible - and boot runs after all of that, so this is the point
        // at which a choice made in the settings actually wins.
        Layout::apply($panel);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettingsFormData(): array
    {
        return Settings::data();
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public function getSettingsForm(): array
    {
        return Settings::fields();
    }

    /**
     * @param  array<mixed, mixed>  $data
     */
    public function saveSettings(array $data): void
    {
        Settings::persist($data);
    }
}
