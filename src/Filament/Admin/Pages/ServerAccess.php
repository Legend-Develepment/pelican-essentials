<?php

namespace LegendDevelopment\Theme\Filament\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use LegendDevelopment\Theme\Support\Access\RoleServers;
use LegendDevelopment\Theme\Support\Access\Sync;
use LegendDevelopment\Theme\Support\Features;
use LegendDevelopment\Theme\Support\Theme;
use Throwable;

/**
 * Which servers a role gets.
 *
 * The page is shaped around what it actually does, because what it actually
 * does is write to Pelican's own subusers table. So it says so at the top, it
 * shows what the last run did, and the way to undo everything it has granted is
 * a button here rather than a note in a changelog.
 *
 * Saving reconciles at once rather than waiting for the timer: somebody who has
 * just tied a role to a server wants to check it worked, and a page that made
 * them wait fifteen minutes to find out would be a page they stopped trusting.
 *
 * @property Schema $form
 */
class ServerAccess extends Page implements HasSchemas
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-users-plus';

    protected static ?string $slug = 'essentials-access';

    protected static ?int $navigationSort = 12;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        try {
            return Features::maySee(Features::ACCESS);
        } catch (Throwable) {
            return false;
        }
    }

    public function getTitle(): string
    {
        return Theme::trans('access.title');
    }

    public function getSubheading(): ?string
    {
        return Theme::trans('access.subheading');
    }

    public static function getNavigationLabel(): string
    {
        return Theme::trans('access.nav_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return Theme::name();
    }

    public function getView(): string
    {
        return Theme::id() . '::pages.server-access';
    }

    public function mount(): void
    {
        $this->form->fill(['access_roles' => RoleServers::rows()]);
    }

    /**
     * What the last panel-wide run did, as a sentence, or null.
     *
     * Shown because everything this page does happens somewhere else - in a
     * table, on a timer - and a settings page whose effects are entirely
     * invisible is a settings page nobody can tell is working.
     */
    public function lastRun(): ?string
    {
        $run = Sync::lastRun();

        if ($run === null) {
            return null;
        }

        if ($run['capped'] ?? false) {
            return Theme::trans('access.capped', [
                'pairs' => (int) $run['pairs'],
                'max' => Sync::MAX_PAIRS,
            ]);
        }

        return Theme::trans('access.last_run', [
            'added' => (int) $run['added'],
            'removed' => (int) $run['removed'],
            'held' => (int) $run['pairs'],
            'ago' => max(0, time() - (int) $run['at']),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(Theme::trans('access.which'))
                    ->description(Theme::trans('access.which_helper'))
                    ->schema([
                        Repeater::make('access_roles')
                            ->label('')
                            ->addActionLabel(Theme::trans('access.add'))
                            ->maxItems(RoleServers::MAX)
                            ->schema([
                                Select::make('role')
                                    ->label(Theme::trans('access.role'))
                                    ->helperText(Theme::trans('access.role_helper'))
                                    ->options(fn (): array => RoleServers::roleOptions())
                                    ->searchable()
                                    ->required(),

                                Select::make('servers')
                                    ->label(Theme::trans('access.servers'))
                                    ->helperText(Theme::trans('access.servers_helper'))
                                    ->options(fn (): array => RoleServers::serverOptions())
                                    ->multiple()
                                    ->searchable()
                                    ->required(),

                                /*
                                 * Grouped the way Pelican's own subuser dialog
                                 * groups them. Forty checkboxes in one flat
                                 * list is a form nobody finishes, and the
                                 * grouping is free - the permission strings
                                 * carry it in front of the dot.
                                 */
                                CheckboxList::make('permissions')
                                    ->label(Theme::trans('access.permissions'))
                                    ->helperText(Theme::trans('access.permissions_helper'))
                                    ->options(fn (): array => self::permissionOptions())
                                    ->default(RoleServers::PRESET)
                                    ->columns(3)
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->reorderable(false)
                            ->defaultItems(0),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Every permission, labelled "Control: console" and the like.
     *
     * A flat list with the group in the label rather than a grouped control,
     * because a CheckboxList takes options and not sections - and the label
     * carries the same information for the reader either way.
     *
     * @return array<string, string>
     */
    private static function permissionOptions(): array
    {
        $out = [];

        foreach (RoleServers::groups() as $group => $permissions) {
            foreach ($permissions as $permission => $label) {
                $out[$permission] = ucfirst($group) . ': ' . strtolower($label);
            }
        }

        return $out;
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        $actions = [];

        if (!Features::mayManage(Features::ACCESS)) {
            return $actions;
        }

        $actions[] = Action::make('ld_save')
            ->label(Theme::trans('access.save'))
            ->icon('tabler-device-floppy')
            ->action(fn () => $this->save());

        /*
         * The way out, and it asks first.
         *
         * This removes every row the plugin created, on every server, for
         * everybody - which is the one action on this page that somebody could
         * regret in a way they cannot undo by pressing it again.
         */
        $actions[] = Action::make('ld_revoke')
            ->label(Theme::trans('access.revoke'))
            ->icon('tabler-user-off')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(Theme::trans('access.revoke_confirm'))
            ->modalDescription(Theme::trans('access.revoke_confirm_helper'))
            ->action(fn () => $this->revoke());

        return $actions;
    }

    public function save(): void
    {
        abort_unless(Features::mayManage(Features::ACCESS), 403);

        try {
            $state = $this->form->getState();
            $rows = $state['access_roles'] ?? [];

            if (!RoleServers::save(is_array($rows) ? $rows : [])) {
                Notification::make()
                    ->title(Theme::trans('access.save_failed'))
                    ->body(Theme::trans('access.save_failed_disk'))
                    ->danger()
                    ->persistent()
                    ->send();

                return;
            }

            // At once rather than on the timer, so the person who just saved
            // can go and look.
            $result = Sync::all();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(Theme::trans('access.save_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $this->form->fill(['access_roles' => RoleServers::rows()]);

        if ($result['capped'] ?? false) {
            Notification::make()
                ->title(Theme::trans('access.saved'))
                ->body(Theme::trans('access.capped', [
                    'pairs' => (int) $result['pairs'],
                    'max' => Sync::MAX_PAIRS,
                ]))
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title(Theme::trans('access.saved'))
            ->body(Theme::trans('access.saved_body', [
                'added' => (int) $result['added'],
                'removed' => (int) $result['removed'],
            ]))
            ->success()
            ->send();
    }

    public function revoke(): void
    {
        abort_unless(Features::mayManage(Features::ACCESS), 403);

        try {
            $removed = Sync::revokeAll();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(Theme::trans('access.revoke_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title(Theme::trans('access.revoked', ['count' => $removed]))
            ->body(Theme::trans('access.revoked_body'))
            ->success()
            ->send();
    }
}
