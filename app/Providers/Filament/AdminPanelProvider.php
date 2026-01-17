<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Tables\Table;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Enums\UserMenuPosition;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Form;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Widgets\FilamentInfoWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->brandLogo(null)
            ->favicon(asset('favicon.ico'))
            ->topbar(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            //default settings
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            ->databaseNotificationsPolling('30s')
            ->unsavedChangesAlerts()
            ->databaseTransactions()
            ->sidebarCollapsibleOnDesktop(true)
            //enalble this on production
            // ->errorNotifications(false)

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot()
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->reorderableColumns()
                ->filtersLayout(FiltersLayout::BeforeContentCollapsible)
                ->paginationPageOptions([10, 25, 50]);
        });

        TextInput::configureUsing(function (TextInput $textInput): void {
            $textInput
                ->translateLabel();
        });

        TextEntry::configureUsing(function (TextEntry $textEntry): void {
            $textEntry
                ->translateLabel();
        });

        Select::configureUsing(function (Select $select): void {
            $select
                ->translateLabel();
        });

        Textarea::configureUsing(function (Textarea $textarea): void {
            $textarea
                ->translateLabel();
        });

        AttachAction::configureUsing(function (AttachAction $action): void {
            $action
                ->label(__('Attach'))
                ->translateLabel();
        });

        DetachAction::configureUsing(function (DetachAction $action): void {
            $action
                ->label(__('Detach'))
                ->translateLabel();
        });
    }
}
