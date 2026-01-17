<?php

namespace App\Providers\Filament;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Enums\UserMenuPosition;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

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
            // Disable tenancy features for admin panel (global access only)
            ->tenantMenu(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            // default settings
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            ->databaseNotificationsPolling('30s')
            ->unsavedChangesAlerts()
            ->databaseTransactions()
            ->sidebarCollapsibleOnDesktop(true)
            // enalble this on production
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
            ])
            ->authGuard('web');
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
