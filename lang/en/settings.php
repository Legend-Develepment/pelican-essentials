<?php

return [
    'css_warning' => 'Saved, but this CSS looks wrong',
    'css_unclosed' => 'A rule opened on line :line is never closed. Everything after it is inside that rule and will not apply.',
    'css_extra' => 'There is a closing brace on line :line with nothing open. Everything after it is outside any rule and will be ignored.',
    'css_comment' => 'A comment opened on line :line is never closed, so the rest of the file is inside it.',

    'groups' => [
        'appearance' => 'Appearance',
        'servers' => 'Server list',
        'minecraft' => 'Minecraft',
        'ark' => 'ARK',
        'valheim' => 'Valheim',
        'languages' => 'Languages',
        'servers_helper' => 'How a server card is drawn. Whether they are shown as a grid or a list is each person\'s own choice, under Account → Dashboard layout.',
        'server_pages' => 'Server pages',
        'server_pages_helper' => 'What every page inside a server carries, whichever page it is.',
        'console' => 'Console page',
        'console_helper' => 'The terminal\'s own font, size and height are each person\'s own choice, under Account.',
        'background' => 'Background',
        'background_helper' => 'Applies to the whole panel, including the login screen.',
        'icons' => 'Icons',
        'bars' => 'Resource meters',
        'bars_helper' => 'The CPU, memory and disk bars on the server cards.',
        'updates' => 'Updates',
        'updates_helper' => 'Which releases the Theme page offers, and where it looks for them.',
        'brand' => 'Brand',
        'login' => 'Login screen',
        'login_helper' => 'Applies to the sign-in, password reset and two-factor screens.',
        'advanced' => 'Custom CSS',
        'advanced_helper' => 'For anything the settings above do not cover. Loaded after everything else, so it wins.',
        'areas' => 'Per area',
        'areas_helper' => 'Everything above applies everywhere. Here you can set one area apart; anything left empty keeps following the global setting.',
        'footer' => 'Sidebar footer',
        'footer_helper' => 'The bottom of the sidebar, which Pelican leaves empty. Everything here is off until you fill it in.',
        'features' => 'What this plugin adds',
        'features_helper' => 'Unticking one takes it out of the panel entirely. Its own settings are kept and its page keeps its address, so nothing is lost by switching something off to see what it was doing. Most of these also have a permission of their own under Roles, for handing one out without handing over the rest. Not all: the resource meters, the sidebar footer and the settings search are drawn for everybody and administered by nobody, the star on a server card belongs to whoever clicked it, and the Palworld and Minecraft pages inside a server go by that server’s own permissions rather than by one of these. The styling itself is not in this list — that has its own switch, under Look → Appearance → Style → None.',
        'identity' => 'This plugin in the sidebar',
        'identity_helper' => 'The row this plugin adds to the sidebar, and the picture on it.',
    ],

    /*
     * The settings pages, each a row in the plugin's own sidebar group. Grouped
     * by the question you are answering rather than by which class implements
     * them.
     */
    'pages' => [
        'look' => 'Look',
        'look_helper' => 'Colour, shape and what the panel is called.',
        'pages' => 'Pages',
        'pages_helper' => 'The server list, the pages inside a server, and the terminal.',
        'advanced' => 'Advanced',
        'advanced_helper' => 'The two escape hatches: your own CSS, and settings that apply to one area only.',
        'minecraft' => 'Minecraft',
        'minecraft_helper' => 'Which eggs are Minecraft, and everything else about it.',
        'artwork' => 'Egg artwork',
        'artwork_helper' => 'A page listing every egg, and a way to fetch the game\'s picture for it from Steam or IGDB. It writes to the eggs themselves — the picture, and two tags recording which game it is and whether the picture was chosen by hand — so it carries a permission of its own.',
        'alerts' => 'Alerts',
        'alerts_helper' => 'A check on a timer for the things the panel already measures but tells nobody: a node that stops answering, a disk filling up, a queue worker that has stopped, a version falling behind. Sends to Discord, to the panel, or by email. Its own permission, because it reaches every node on a timer and posts to an address somebody typed.',
        'backups' => 'Backups overview',
        'backups_helper' => 'A page listing every server with how long it has gone without a backup, sorted so the ones with none are at the top. Read only — everything that acts on a backup stays on Pelican\'s own page for that server. Its own permission, because the list is a map of where the gaps are.',
        'public_status' => 'Public status page',
        'public_status_helper' => 'A page anybody can open without an account, showing which of your servers are up and how many people are on them. Nothing is published until you name a server, a machine or a service — all three lists start empty, and while they all are the address answers 404. Its own permission, because it decides what leaves the panel.',
        'game_players' => 'Players, other games',
        'access' => 'Server access',
        'access_helper' => 'Tie a role to servers, so everyone holding it can reach them. It works by keeping Pelican\'s own subusers up to date, which is what the server list and every permission check already read. Its own permission, because it is the one page here that grants people access to things.',
        'games' => 'Other games',
        'games_helper' => 'The files ARK and Valheim keep beside their world, as forms: ARK\'s world settings, and Valheim\'s admin, ban and permitted lists. Which servers get them is the egg list on that page, so an empty list is already a per-game off switch.',
        'game_players_helper' => 'A page inside Rust, ARK, Valheim and anything else that answers Valve\'s query, showing who is connected and how long they have been on. Read only — what you can do to somebody differs per game, and that is a release of its own. Which eggs count is the same list the status page uses.',
        'languages' => 'Languages',
        'languages_helper' => 'Which languages this plugin answers in.',
    ],

    'features' => [
        'look' => 'Look settings',
        'look_helper' => 'The sidebar row for colour, shape and branding.',
        'pages' => 'Pages settings',
        'pages_helper' => 'The sidebar row for the server list, server pages and the terminal.',
        'advanced' => 'Advanced settings',
        'advanced_helper' => 'The sidebar row for your own CSS and per-area overrides.',
        'announcements' => 'Announcements',
        'announcements_helper' => 'The bar across the top of the panel.',
        'nav_links' => 'Navigation links',
        'nav_links_helper' => 'Your own rows in the sidebar.',
        'login' => 'Login screen',
        'login_helper' => 'The sign-in screen\'s picture, notice and links.',
        'bars' => 'Resource meters',
        'bars_helper' => 'The recoloured CPU, memory and disk bars.',
        'dashboard_status' => 'Version line',
        'dashboard_status_helper' => 'The top of the dashboard block: which version is installed and whether one is waiting.',
        'dashboard_nodes' => 'Machines',
        'dashboard_nodes_helper' => 'The rest of the dashboard block: this panel and every node, with what each is using.',
        'system_status' => 'System status page',
        'system_status_helper' => 'The page for the machine the panel itself runs on.',
        'sidebar_footer' => 'Sidebar footer',
        'sidebar_footer_helper' => 'Your line of text, the panel version and one link, at the bottom of the sidebar.',
        'languages' => 'Languages',
        'languages_helper' => 'Answering each person in the language their own account is set to, where this plugin has been translated into it. With this off everybody gets English.',
        'minecraft' => 'Minecraft',
        'minecraft_helper' => 'A Minecraft tab in the sidebar, and a page inside each Minecraft server for editing its server.properties as a form. Which eggs count is yours to say.',
        'palworld' => 'Palworld settings',
        'palworld_helper' => 'A page inside a Palworld server for editing its world settings. It appears on no other server, and never while that server is running.',
        'settings_search' => 'Settings search',
        'settings_search_helper' => 'The box above these forms that narrows them to the sections holding what you type.',
        'preview' => 'Live preview',
        'preview_helper' => 'The box beside the Look form showing what the colours, corners and spacing do before you save them.',
        'duplicate' => 'Duplicate server',
        'duplicate_helper' => 'A page for setting up another server exactly like one you already have, or several at once. Files are never copied.',
        'favourites' => 'Starred servers',
        'favourites_helper' => 'A star on each server card. Starred ones come first, and each person\'s list is kept on the panel — so their stars follow them to whatever they next sign in on. It changes what they see and nothing for anyone else. Being on the panel does mean it is a file under storage, which anyone with access to the machine can read.',
        'artwork' => 'Egg artwork',
        'artwork_helper' => 'The admin page that fetches each egg\'s picture from Steam or IGDB and writes it to the egg itself.',
        'alerts' => 'Alerts',
        'alerts_helper' => 'The check on a timer for a node that has stopped answering, a disk filling up, a queue worker that has died or a version falling behind, and the Discord, panel or email message it sends.',
        'backups' => 'Backups overview',
        'backups_helper' => 'The admin page listing every server by how long it has gone without a backup. Read only.',
        'public_status' => 'Public status page',
        'public_status_helper' => 'The page anybody can open without an account. With this off the address answers 404 whatever is on the list.',
        'game_players' => 'Players, other games',
        'game_players_helper' => 'A page inside Rust, ARK, Valheim and anything else that answers Valve\'s query, showing who is connected and how long they have been on.',
        'access' => 'Server access by role',
        'access_helper' => 'A page for tying a role to servers, kept true in Pelican\'s own subuser table. It grants nothing until you map something. Switching it off stops it reconciling; access already granted stays, and the page has a button for taking it back.',
        'games' => 'Other games',
        'games_helper' => 'ARK\'s world settings, and Valheim\'s admin, ban and permitted lists, as forms rather than as files in the file manager. Which servers get them is the egg list on the Other games page.',
        'quick' => 'Go to menu',
        'quick_helper' => 'One control at the top of every page for jumping to a server or to a page you starred, with a search box over your whole server list. It also stars the page you are on. What somebody finds through it is what they could already reach, so it grants nothing - switching this off takes away the shortcut and the Favourites page with it.',
    ],

    /*
     * The search box above the settings forms. It filters what is already on
     * the page in the browser and asks the server for nothing, so there is no
     * "searching" state to describe and no way for it to fail.
     */
    /*
     * The preview box. Everything in it is a stand-in rather than a sample of
     * your panel, and the wording says so - a box that named a real server or a
     * real figure would be read as one.
     */
    'preview' => [
        'label' => 'Preview',
        'card' => 'A card',
        'card_helper' => 'Drawn by the same rules as the panel, with the settings on this page instead of the saved ones.',
        'button' => 'A button',
        'field' => 'A field',
        'meter_ok' => 'Fine',
        'meter_warning' => 'Warning',
        'meter_danger' => 'Danger',

        /*
         * The full-page preview. A tab and not a pane, because Pelican sends
         * X-Frame-Options: DENY and refuses to be framed by anything, itself
         * included - see Support\FullPreview.
         */
        'full' => 'See the whole panel',
        'full_confirm' => 'Opens the panel drawn from the settings on this page instead of the saved ones. Nothing is written — the values are held for fifteen minutes and the panel goes back to normal when you leave the preview or save.',
        'full_go' => 'Show me',
        'full_failed' => 'Could not start the preview',
        'bar' => 'You are looking at unsaved settings. Nothing here has been written.',
        'bar_back' => 'Back to the settings',
    ],

    'search' => [
        'placeholder' => 'Search settings',
        'label' => 'Search these settings',
        'none' => 'Nothing on this page matches. The settings are spread over four pages — try Look, Pages, Advanced, or Essentials settings.',
    ],

    'footer' => [
        'text' => 'Your own line',
        'text_helper' => 'Plain text, at most 120 characters. Escaped, like the announcement bar — this renders on every page of the panel, which makes it the wrong place to accept markup.',
        'version' => 'Show the panel version',
        'version_helper' => 'Pelican\'s version, not this plugin\'s. The plugin says its own on the dashboard; what people ask at the bottom of a sidebar is which panel they are looking at.',
        'link_label' => 'Link text',
        'link_url' => 'Link address',
        'link_url_helper' => 'An http or https address, or a path of the panel\'s own such as /account. Opens in a new tab.',
    ],

    'layout' => [
        'label' => 'Layout',
        'helper' => 'How the panel is arranged, rather than what colour it is. Applies to the admin area, the server list and the client area alike. Where the navigation goes is a default: anyone who has set their own under Account → Navigation keeps it.',
        'default' => 'Sidebar — Pelican\'s own',
        'rail' => 'Icon rail — narrow, opens on hover',
        'top' => 'Top navigation — no sidebar',
        'mixed' => 'Top bar and sidebar — both',
        'wide' => 'Wide — content uses the whole screen',
        'focus' => 'Focused — narrow column, sidebar folds away',

        'nav_label' => 'Sidebar style',
        'nav_helper' => 'How the sidebar itself is drawn.',
        'nav_default' => 'Default',
        'nav_floating' => 'Floating — a card of its own',
        'nav_flat' => 'Flat — no background at all',
        'nav_bordered' => 'Bordered — a line, not a surface',

        'topbar_label' => 'Topbar style',
        'topbar_helper' => 'Hidden applies to desktop only — on a phone the topbar holds the only way back to the menu.',
        'topbar_default' => 'Default',
        'topbar_floating' => 'Floating — a detached bar',
        'topbar_flush' => 'Flush — flat, no blur',
        'topbar_hidden' => 'Hidden on desktop',

        'card_label' => 'Card style',
        'card_helper' => 'Sections, widgets, server cards and the blocks above the console.',
        'card_default' => 'Default — raised with a soft edge',
        'card_flat' => 'Flat — no lift',
        'card_outline' => 'Outline — a border and nothing behind it',
        'card_glass' => 'Frosted — the background shows through',
        'card_sharp' => 'Sharp — square corners',
    ],

    'servers' => [
        /*
         * The star on a card. Handed to the script rather than written into it,
         * so the strings stay in the one place strings live.
         */
        'favourite' => 'Star this server',
        'favourited' => 'Starred — shown first',

        /*
         * The pill beside Pelican's own tabs. Named for what it does to the
         * list rather than as a fourth tab, because it filters whichever tab is
         * chosen instead of replacing it.
         */
        'favourites_tab' => 'Favourites',
        'favourites_empty' => 'Nothing starred on this page. Use the star on a server card to add one — and note this filters the servers already listed here, so a starred server on a later page is not being hidden, it simply is not on this one.',
        'favourites_failed' => 'Your starred servers could not be saved, so they have been put back to what the panel last had. The browser console says what the request answered.',

        'art' => 'Game artwork',
        'art_helper' => 'Pelican renders the egg\'s picture on every card. This decides what is done with it.',
        'art_faded' => 'Faded — a wash behind the text',
        'art_cover' => 'Cover — behind the name, fading out',
        'art_off' => 'Off',
        'art_dim' => 'Darken the cover',
        'art_dim_helper' => 'One game\'s artwork is a bright sky and another\'s is a cave.',

        'status' => 'Condition marker',
        'status_helper' => 'Where the running/starting/stopped colour is shown.',
        'status_bar' => 'Bar — down the left edge',
        'status_edge' => 'Edge — across the top',
        'status_dot' => 'Dot — in the corner',
        'status_off' => 'Off',

        'density' => 'Card height',
        'density_comfortable' => 'Comfortable',
        'density_compact' => 'Compact — for a lot of servers',

        'filter_label' => 'Label the filter button',
        'filter_label_helper' => 'Pelican already filters this list by egg and by owner, across every page - but the way in is an unlabelled icon beside the search box. This puts the word on it.',
        'filter_button' => 'Filters',

        'columns' => 'Cards across a wide screen',
        'columns_helper' => 'Only applies to the grid layout, and only from 1280px up. Pelican\'s own maximum is two.',
    ],

    'controls' => [
        'mode' => 'Console button on every server page',
        'mode_helper' => 'One floating button, on every page inside a server. It opens the console over whatever you were doing, with the state and the power buttons in its header — reaching the node directly, the way the server list does, rather than over the console page\'s websocket. It never appears on the console page, which already has all of it.',
        'mode_full' => 'Console and power buttons',
        'mode_console' => 'Console only',
        'mode_off' => 'Off',

        'label' => 'The button shows',
        'label_text' => 'Icon and name',
        'label_icon' => 'Icon only',

        'position' => 'Where it floats',
        'position_helper' => 'Against the edge you are least likely to be reading.',
        'position_top' => 'Top',
        'position_right' => 'Right',
        'position_bottom' => 'Bottom',
    ],

    'console' => [
        'stats' => 'Blocks above the console',
        'stats_helper' => 'Pelican shows the name, status, address and the three usage figures above the terminal. Hiding them gives the console the height back.',
        'stats_tiles' => 'Tiles — label, figure and an icon',
        'stats_plain' => 'Plain — as Pelican draws them',
        'stats_off' => 'Hidden',
    ],

    'terminal' => [
        'helper' => 'Handed to the terminal itself, so they take effect on the next page load rather than the moment they are saved.',

        'renderer' => 'Drawn by',
        'renderer_helper' => 'Pelican draws the terminal on the GPU, which is much faster on a wall of scrolling output. A browser keeps only so many GPU contexts alive at once — fewer on a phone — and takes the oldest away when the limit is passed; the terminal then draws nothing at all, with no error. If your console goes blank while everything else about it looks right, this is the setting to change.',
        'renderer_webgl' => 'The GPU — Pelican\'s own, faster',
        'renderer_dom' => 'The browser — slower, always draws',

        'scheme' => 'Colour scheme',
        'scheme_helper' => 'The one terminal setting Pelican does not offer. Follow theme derives the colours from the accent, which is why this exists at all.',
        'scheme_theme' => 'Follow theme',
        'scheme_dracula' => 'Dracula',
        'scheme_nord' => 'Nord',
        'scheme_solarized' => 'Solarized Dark',
        'scheme_gruvbox' => 'Gruvbox Dark',
        'scheme_one_dark' => 'One Dark',
        'scheme_tokyo_night' => 'Tokyo Night',
        'scheme_catppuccin' => 'Catppuccin Mocha',
        'scheme_monokai' => 'Monokai',

        'cursor' => 'Cursor',
        'cursor_helper' => 'The console takes no typing — the command box sits underneath it — so this is where the output stopped, not where you are.',
        'cursor_underline' => 'Underline — Pelican\'s own',
        'cursor_block' => 'Block',
        'cursor_bar' => 'Bar',

        'blink' => 'Blinking cursor',

        'scrollback' => 'Scrollback',
        'scrollback_helper' => 'How far back the console can be scrolled. Every line is kept in the browser, so a chatty server on a large setting is real memory on the machine reading it.',
        'scrollback_lines' => ':lines lines',
    ],

    'notice' => [
        'text' => 'Message',
        'text_helper' => 'One line, up to 200 characters. It is escaped on the way in and on the way out, so it cannot carry markup onto a page other people load.',
        'style' => 'Tone',
        'style_info' => 'Info',
        'style_warning' => 'Warning',
        'style_danger' => 'Urgent',
        'style_accent' => 'Accent colour',
        'scope' => 'Shown to',
        'scope_all' => 'Everyone',
        'scope_client' => 'Only outside the admin area',
        'scope_admin' => 'Only in the admin area',
        'link_label' => 'Button text',
        'link_url' => 'Button address',
        'link_url_helper' => 'https:// or a path inside this panel, such as /account. Anything else is ignored — a link in a bar on every page is not a place for a scheme nobody expected.',
        'dismissible' => 'Can be closed',
        'dismissible_helper' => 'Closing it is remembered per browser, and only for this message: change the text and it comes back for everyone.',
        'dismiss' => 'Close',
    ],

    'preset' => [
        'label' => 'Style',
        'helper' => 'Pick a look to start from. It fills in everything below, which you can then change. None turns the theme off and leaves the panel exactly as Pelican ships it.',
        'options' => [
            'none' => 'None - no theme',
            'legend' => 'Legend - red fire into blue lightning',
            'ember' => 'Ember - warm black, orange accent',
            'midnight' => 'Midnight - deep blue, calm',
            'crimson' => 'Crimson - red, sharp corners, compact',
            'forest' => 'Forest - green, rounded, no glow',
            'nebula' => 'Nebula - purple with a gradient backdrop',
            'terminal' => 'Terminal - green on black, monospace, sharp',
            'console' => 'Console - round and roomy, for a tablet',
            'nord' => 'Nord - the Nord palette, muted',
            'solarized' => 'Solarized - Solarized dark, cyan accent',
            'paper' => 'Paper - light, high contrast, flat',
            'mono' => 'Mono - greyscale, flat and dense',
        ],

        'save' => 'Save as a style',
        'save_confirm' => 'Keeps the colours, corners, background, lettering, icons and meter thresholds you have on screen right now — under a name of your own, in the picker beside the built-in ones. It saves what is on the page, not what was last saved.',
        'save_name' => 'Name',
        'save_name_helper' => 'What it will be called in the picker. Saving under a name you have used before replaces that one.',
        'saved' => 'Style saved',
        'save_failed' => 'Could not save that style',
        'save_full' => 'There is room for :max styles of your own. Delete one first.',

        'delete' => 'Delete a style',
        'delete_which' => 'Which one',
        'delete_confirm' => 'Only styles of your own can be deleted; the built-in ones cannot. Nothing about how the panel currently looks changes — a style is a starting point, and every value it set is already in the settings below.',
        'deleted' => 'Style deleted',
        'deleted_current' => 'That was the one this panel was set to. Its settings are unchanged and still on this page — pick a style, or save it again under a name.',
    ],

    'user_themes' => [
        'label' => 'Styles people may choose for themselves',
        'helper' => 'Ticked styles appear on an Appearance page in the client panel, where anyone signed in can pick one for themselves. It changes what they see and nothing for anyone else. Nothing ticked means nobody chooses anything and the panel keeps one look — which is what it does now.',
    ],

    'mode' => [
        'label' => 'Panel mode',
        'helper' => 'Which mode the panel opens in. Anyone who has not chosen for themselves gets this one; the switcher in the user menu still lets them change it, unless you lock it below.',
        'dark' => 'Dark',
        'light' => 'Light',
        'system' => 'System — follow the visitor\'s own setting',
    ],

    'font' => [
        'label' => 'Panel lettering',
        'helper' => 'Every option is a family the operating system already has — nothing is fetched from a font host. The terminal is not affected: its font is each person\'s own choice, under Account.',
        'default' => 'Default - Pelican\'s own',
        'mono' => 'Monospace',
        'rounded' => 'Rounded',
        'serif' => 'Serif',
        'system' => 'System - whatever this machine uses',
    ],

    'surface' => [
        'label' => 'Surface colour',
        'helper' => 'The cards and panels. Lighter and darker shades are derived from it.',
        'placeholder' => 'Follow the theme',
    ],

    'radius' => [
        'label' => 'Corners',
    ],

    'accent' => [
        'label' => 'Accent colour',
        'helper' => 'Used for buttons, links, the active navigation item and focus rings.',

        /*
         * Said, not enforced. A colour this warns about is still saved: it is
         * somebody's panel, the figure is one measure of one thing, and there
         * are good reasons to want an accent that scores badly. The picker says
         * what it sees and gets out of the way.
         */
        'contrast_dark' => 'Readability: :ratio against a dark panel. Under 3 an accent is hard to read as a button or a link — a lighter one lifts it.',
        'contrast_light' => 'Readability: :ratio against a light panel. Under 3 an accent is hard to read as a button or a link — a darker one lifts it.',
    ],
    'density' => [
        'label' => 'Density',
        'helper' => 'Compact tightens the spacing so more rows fit on screen.',
        'comfortable' => 'Comfortable',
        'compact' => 'Compact',
    ],
    'force_dark' => [
        'label' => 'Force dark mode',
        'helper' => 'Hides the light/dark switcher and keeps every user on the dark theme.',
    ],
    'glass' => [
        'label' => 'Frosted topbar',
        'helper' => 'Blurs the topbar and modal backdrops. Turn off on low-end devices.',
    ],
    'glow' => [
        'label' => 'Accent glow',
        'helper' => 'Soft accent shadow on primary buttons, active navigation and the login card.',
    ],

    'background' => [
        'label' => 'Background type',
        'helper' => "Aurora is the theme's own background: accent glows with a fine grain.",
        'aurora' => 'Aurora (default)',
        'solid' => 'Single colour',
        'gradient' => 'Gradient',
        'image' => 'Image',
        'color' => 'Colour',
        'color_end' => 'Second colour',
        'angle' => 'Direction',
        'upload' => 'Upload an image',
        'upload_helper' => 'Up to 8 MB. An uploaded image takes precedence over the URL below.',
        'url' => 'Or a URL',
        'url_helper' => 'Must start with https:// and be reachable from outside.',
        'dim' => 'Dim',
        'dim_helper' => 'Without dimming, white text on a bright photo is unreadable.',
        'blur' => 'Blur',
    ],

    'channel' => [
        'installed' => 'installed',
        'version' => 'Install a particular version',
        'version_helper' => 'Any release on this channel, not only the newest — for going back when something new turns out to be worse, or forward to a build you were told to try. Only while updates are not installing themselves: with that on, whatever you pick would last until the next check.',
        'version_placeholder' => 'Pick a version',
        'version_install' => 'Install this version',
        'version_confirm' => 'The panel downloads that release, rebuilds its assets and clears its caches. Your settings are kept. Going back to an older version is allowed and is not undone for you — pick the newer one again to move forward.',
        'label' => 'Update channel',
        'helper' => 'Which releases the Theme page offers. Beta gets new versions first, and gets the rough edges first too.',
        'stable' => 'Stable',
        'beta' => 'Beta',
        'dev' => 'Dev (working branch)',
        'auto' => [
            'label' => 'Install updates automatically',
            'helper' => 'Off leaves updating to you. On, the panel checks the selected channel and installs anything newer - it rebuilds its assets while that runs and is unavailable for a few minutes, so daily and weekly go at 04:00. Needs the panel\'s cron to be running.',
            'interval' => 'Check every',
            'minute' => 'Every minute',
            'five_minutes' => 'Every 5 minutes',
            'ten_minutes' => 'Every 10 minutes',
            'thirty_minutes' => 'Every 30 minutes',
            'hourly' => 'Every hour',
            'daily' => 'Every day (04:00)',
            'weekly' => 'Every week (Monday 04:00)',
        ],
    ],

    /*
     * The Languages tab.
     *
     * Careful about what it claims. Pelican already lets every person choose a
     * language for their whole account and already applies it; nothing here
     * changes that or should. This decides only whether this plugin's own
     * strings follow that choice.
     */
    'languages' => [
        'section_helper' => 'Pelican already lets each person pick a language for their account, and this plugin follows it wherever it has been translated. This is where you decide which of those it will follow. Most languages sit at a low percentage on purpose: what is translated first is the part everybody sees on every page — the power buttons above a console and the node meters — and the rest arrives as people contribute it.',
        'panel' => 'Let this decide the language of the whole panel',
        'panel_helper' => 'On, a language this plugin does not carry — or one switched off below — puts the whole panel in English for that reader, not just these pages. Off, only this plugin follows the list and Pelican goes on speaking whatever the account is set to, which means a reader can meet two languages on one screen. No account is changed either way: switch a language back on and they have it again.',
        'label' => 'Languages to answer in',
        'helper' => 'Unticking one sends readers whose account is set to it back to English for this plugin only — the rest of the panel still speaks their language. English is not listed because everything falls back to it.',
        'done' => ':percent% translated',
        'main' => 'Main language',
        'main_helper' => 'What a reader gets when their own language cannot be used — either this plugin does not carry it, or it is unticked below. It was always English; on a team that does not work in English that was the wrong answer arrived at confidently. It cannot be unticked below, because everything falls back to it.',
        'labels' => 'What each language is called',
        'labels_helper' => 'The name readers and administrators see in the pickers. Leave one empty to keep the name this plugin knows it by. A language uploaded under a name of your own has none, so it would be listed as its code until you give it one here.',
        'labels_code' => 'Code',
        'labels_name' => 'Shown as',
        'download' => 'Download a translation file',
        'download_from' => 'Start from',
        'download_from_helper' => 'A JSON of every string this plugin has. Pick English for a language nobody has started, or an existing one to carry on with what is already translated.',
        'code' => 'Language code',
        'code_helper' => 'The code the file is for. A real locale as accounts use it — fr, de, pt_BR — reaches readers whose account is set to it, and has to match exactly or it will not. A name of your own, such as Gaming-NL, is allowed and works differently: Pelican only lets an account hold a real locale, so nobody can select yours. It is reachable as the main language above, which is what everyone gets whose own cannot be used.',
        'url' => 'Or fetch it from an address',
        'url_helper' => 'An https address the panel can reach — a CDN, a bucket, a raw file on a repository. It is fetched once when you save and written the same way an upload is, so changing the file at that address later does nothing until you save again. A file chosen above wins over an address left in this box.',
        'upload' => 'Upload a translation file',
        'upload_helper' => 'The JSON from above, with the values translated. It is written outside the plugin, so an update will not throw it away, and it is merged over English per key — a file with half the strings in it gives you half a language and English for the rest.',
        'uploaded' => ':count strings installed for :code',
        'uploaded_halves' => ':mine of them are this plugin’s own strings and :panel are the panel’s. Zero on either side means that half of the file held nothing — the plugin’s keys start with essentials:: and the panel’s do not.',
        'uploaded_skipped' => ':count were skipped: empty, or keys this plugin does not have. First few: :keys',
        'upload_failed' => 'That file could not be read',
        'upload_failed_body' => 'It has to be the JSON from the download above — a flat object of keys and strings. Check that an editor has not saved it as something else.',
    ],

    'arranger' => [
        'label' => 'Page arranger',
        'helper' => 'The Arrange page button, on every page of the panel. Anyone holding the Arrange permission gets it and can also set the arrangement everyone else starts from, or one for a role. Off hides it for everyone; arrangements already saved stay in place.',
        'roles' => 'An arrangement is not a permission. A block a role hides is still a block somebody could reach by typing the address — what stops that is Pelican\'s own permissions, on the roles page. Three layers apply in this order: the one everyone starts from, then the reader\'s role, then whatever they have moved themselves.',
        'users' => 'Let everyone arrange their own pages',
        'users_helper' => 'On, anybody signed in can rearrange and hide blocks on the pages they can already see, for themselves only — it changes nothing for anyone else. Setting the arrangement everyone starts from stays with the Arrange permission.',
    ],

    'brand' => [
        'logo_height' => 'Logo height',
        'logo_height_helper' => 'Pelican ships 2rem. Larger values make the sidebar header taller with it.',
        'logo_url' => 'Logo override',
        'logo_url_helper' => "Leave empty to keep whatever Pelican's own settings point at.",
    ],

    'login' => [
        'image' => 'Background image',
        'image_helper' => 'Just for the login screen. Without one it keeps showing the panel background.',
        'url' => 'Or a URL',
        'blur' => 'Card blur',
        'blur_helper' => 'Frosts the card so the picture behind it shows through.',
        'width' => 'Card width',
        'position' => 'Picture position',
        'position_helper' => 'Which part of the picture survives being cropped to the screen.',
        'position_center' => 'Centre',
        'position_top' => 'Top',
        'position_bottom' => 'Bottom',
        'position_left' => 'Left',
        'position_right' => 'Right',
        'align' => 'Card position',
        'align_helper' => 'Where the sign-in card sits across the screen.',
        'align_center' => 'Centre',
        'align_start' => 'Left',
        'align_end' => 'Right',
        'opacity' => 'Card solidity',
        'opacity_helper' => 'Lower lets more of the picture through the card.',
        'glow' => 'Accent glow',
        'glow_helper' => 'The halo around the card. Off keeps its edge and its depth.',
        'hide_heading' => 'Hide the heading',
        'hide_heading_helper' => 'Removes the title above the form, leaving the form on its own.',
        'hide_footer' => 'Hide the footer',
        'hide_footer_helper' => 'Removes the line under the card that links to pelican.dev.',
        'above' => 'Line above the form',
        'above_helper' => 'One line, shown to everyone who reaches the sign-in screen. Leave empty for none.',
        'notice' => 'Notice under the card',
        'notice_helper' => 'One line, shown to everyone who reaches the sign-in screen. Leave empty for none.',
    ],

    'advanced' => [
        'css' => 'Custom CSS',
        'css_helper' => 'Up to 100 KB. Saved to storage, not to .env.',
        'reference' => 'CSS reference',
        'reference_helper' => 'Every variable and class this theme and the panel expose.',
    ],

    'areas' => [
        'add' => 'Add an area',
        'area' => 'Area',
        'inherit' => 'Global',
        'radius' => 'Corners',
        'radius_sharp' => 'Sharp',
        'radius_normal' => 'Normal',
        'radius_round' => 'Round',
        'surface' => 'Surface colour',
        'surface_helper' => 'The cards and panels inside this area; lighter and darker shades are derived from it.',
        'names' => [
            'terminal' => 'Terminal',
            'console' => 'Console (rest of the page)',
            'files' => 'Files page',
            'edit' => 'Edit page',
            'server' => 'Other server pages and tabs',
        ],
    ],

    'bars' => [
        'base' => 'Base colour',
        'base_green' => 'Green',
        'base_accent' => 'Accent colour',
        'warning' => 'Amber from',
        'danger' => 'Red from',
    ],

    'icons' => [
        'stroke' => 'Line weight',
        'stroke_thin' => 'Thin',
        'stroke_normal' => 'Normal',
        'stroke_bold' => 'Bold',
        'scale' => 'Size',
        'accent' => 'Menu icons in the accent colour',
        'accent_helper' => 'Applies to the icons in the sidebar and the topbar.',
        'pack' => 'Icon pack',
        'pack_helper' => 'Which set the picker below draws from. Every icon set installed on the server is offered, plus the Essentials set that comes with this plugin and any pack you upload. One difference worth knowing: a line icon is drawn in the menu colour and follows hover and the active row, while the Essentials icons are pictures and keep their own colours instead. That is decided by what the file is, not by which set it came from.',
        'pack_custom' => 'Uploaded pack',
        'pack_shipped' => 'Essentials icons',
        'use_shipped' => 'Use the Essentials icons everywhere',
        'use_shipped_confirm' => 'Sets the pack to Essentials icons and fills every menu row below with the icon drawn for it — console gets the terminal, startup gets the launch button, and so on. It replaces the rows you have now, and nothing is saved until you press Save, so closing the page undoes it.',
        'pack_upload' => 'Upload a pack',
        'pack_upload_helper' => 'A .zip of SVG files. Each file becomes an icon named after it — logo.svg becomes custom-logo. Uploading replaces whatever pack is there now. Files over 256 KB and anything past 4,000 icons are left out, and you are told how many: for scale, the whole Tabler set is close to six thousand icons in about three megabytes, so a pack much larger than that is carrying something other than icons and most of it will be skipped. A big upload may also be refused before this field says anything, by upload_max_filesize and post_max_size in the php.ini of the panel host — no setting here can raise those.',
        'pack_partial' => ':count icons installed, but not all of them',
        'pack_partial_body' => 'Skipped: :big too large for one icon, :unusable not usable as SVG, :duplicate sharing a name with one already taken, :empty left with nothing to draw once cleaned. An SVG over 256 KB is nearly always a picture wrapped in one rather than a drawing — export it at icon size and it will be a few kilobytes. An icon left with nothing to draw held only something this will not serve — if that is a whole pack, it is worth reporting.',
        'pack_stopped_files' => 'It also stopped at the limit on how many icons a pack may hold.',
        'pack_stopped_size' => 'It also stopped because the rest of the pack expands to more than the panel will hold in memory at once — the zip may be smaller than that, since SVG compresses about five to one.',
        'overrides' => 'Replace icons',
        'overrides_helper' => 'One row per icon you want changed. Pick the menu item, then choose an icon from the pack above, give an address, or upload a picture of your own. If more than one is filled in, the upload wins, then the address, then the pack.',
        'overrides_key' => 'Menu item',
        'overrides_value' => 'Icon from the pack',
        'overrides_url' => 'Or an address',
        'overrides_url_helper' => 'An https address for a picture you host yourself — a CDN, a bucket, anywhere the browser can reach. Nothing is copied to the panel, so replacing the file at that address changes the icon without touching this page; the other side of that is an icon that disappears when the address does. It keeps its own colours, like an uploaded picture.',
        'overrides_file' => 'Or upload a picture',
        /*
         * Says what the difference actually is, because it is not obvious and
         * it is the reason somebody would pick one over the other.
         */
        'overrides_file_helper' => 'PNG, SVG or ICO. An icon from the pack is drawn in the menu\'s own colour and follows hover and the active row; an uploaded picture keeps its own colours and does not. For a logo that is usually what you want.',
        'overrides_add' => 'Replace another icon',
        'overrides_search' => 'Type a name, or the menu item…',
    ],

    /*
     * Not under brand. Brand is about how the panel looks; this is about
     * how this plugin appears in it, which is a different question and is
     * answered on a different page.
     */
    'identity' => [
        'nav_icon' => 'Icon for the Essentials settings row',
        'nav_icon_helper' => 'PNG, SVG or ICO, up to 8 MB. Replaces the icon on that one row in the sidebar; leave it empty for the one this plugin ships with. It is drawn as a picture rather than as an icon, so it keeps its own colours instead of following the text — which is usually what a logo wants. The file is served rather than embedded, so each browser fetches it once, but it is still worth exporting something small: a few kilobytes is plenty for a row twenty pixels tall. If an upload fails before this field says anything, the limit it hit is upload_max_filesize in the panel\'s php.ini.',
    ],
];
