<?php

namespace Sekeco\Iam\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Sekeco\Iam\IamPlugin;
use Sekeco\Iam\Policies\PermissionPolicy;
use Sekeco\Iam\Policies\RolePolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class IamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge plugin configuration
        $this->mergeConfigFrom(__DIR__.'/../../config/iam.php', 'iam');

        // Configure Filament panel with IAM plugin
        Panel::configureUsing(function (Panel $panel): void {
            $adminPanelId = (string) config('iam.panel.admin_id', 'admin');
            $appPanelId = (string) config('iam.panel.app_id', 'app');

            // Configure Admin Panel (always global, no tenancy)
            if ($panel->getId() === $adminPanelId) {
                // Admin panel: manually register only global resources (Users, Roles, Tenants)
                // DO NOT use IamPlugin to avoid auto-discovering tenant-specific resources
                $panel
                    ->login()
                    ->registration(\Sekeco\Iam\Filament\Pages\Registration::class)
                    ->profile(page: \Sekeco\Iam\Filament\Pages\Profile::class, isSimple: false)
                    ->emailVerification()
                    ->passwordReset()
                    // Only discover Users, Roles, and Tenants for admin panel (global resources)
                    ->discoverResources(
                        in: __DIR__.'/../../src/Filament/Resources/Users',
                        for: 'Sekeco\\Iam\\Filament\\Resources\\Users',
                    )
                    ->discoverResources(
                        in: __DIR__.'/../../src/Filament/Resources/Roles',
                        for: 'Sekeco\\Iam\\Filament\\Resources\\Roles',
                    )
                    ->discoverResources(
                        in: __DIR__.'/../../src/Filament/Resources/Tenants',
                        for: 'Sekeco\\Iam\\Filament\\Resources\\Tenants',
                    );

                // Admin panel is GLOBAL - no tenancy configuration
                return;
            }

            // Configure App Panel (multitenant if enabled)
            if ($panel->getId() === $appPanelId && config('iam.tenant.enabled', false)) {
                // App panel: manually register tenant-specific resources (Members only)
                $panel
                    ->login()
                    ->registration(\Sekeco\Iam\Filament\Pages\Registration::class)
                    ->emailVerification()
                    ->passwordReset()
                    // Only discover Members for app panel (tenant-scoped)
                    ->discoverResources(
                        in: __DIR__.'/../../src/Filament/Resources/Members',
                        for: 'Sekeco\\Iam\\Filament\\Resources\\Members',
                    );

                // Configure tenancy for app panel
                $tenantModel = config('iam.tenant.model', \Sekeco\Iam\Models\Tenant::class);
                $slugAttribute = config('iam.tenant.slug_attribute', 'slug');
                $routePrefix = config('iam.tenant.route_prefix');
                $domain = config('iam.tenant.domain');

                $panel->tenant($tenantModel, slugAttribute: $slugAttribute);

                // Configure tenant route prefix if set
                if ($routePrefix) {
                    $panel->tenantRoutePrefix($routePrefix);
                }

                // Configure tenant domain if set
                if ($domain) {
                    $panel->tenantDomain($domain);
                }

                // Configure tenant menu
                $menuConfig = config('iam.tenant.menu', []);
                if (($menuConfig['searchable'] ?? true)) {
                    $panel->searchableTenantMenu();
                }
                if (($menuConfig['hidden'] ?? false)) {
                    $panel->tenantMenu(false);
                }
            }
        });
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Register policies
        $roleModel = config('iam.permission_models.role', Role::class);
        $permissionModel = config('iam.permission_models.permission', Permission::class);

        Gate::policy($roleModel, RolePolicy::class);
        Gate::policy($permissionModel, PermissionPolicy::class);

        // Super admin bypass - configurable via config
        Gate::before(function ($user, $ability) {
            $bypassRoles = (array) config('iam.panel.super_admin_roles', ['super_admin']);

            if (! method_exists($user, 'hasAnyRole') && ! method_exists($user, 'hasRole')) {
                return null;
            }

            // Prefer hasAnyRole for efficiency
            if (method_exists($user, 'hasAnyRole')) {
                return $user->hasAnyRole($bypassRoles) ? true : null;
            }

            // Fallback to checking each role individually
            foreach ($bypassRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }

            return null;
        });

        // Validate configured user model implements required contract
        $this->validateUserModel();
    }

    /**
     * Validate that the configured user model implements the required contract and uses the trait.
     */
    protected function validateUserModel(): void
    {
        $userModel = config('iam.user_model');
        $contract = config('iam.user_contract');
        $trait = config('iam.user_trait');

        if (! is_string($userModel) || ! class_exists($userModel)) {
            Log::warning("IAM: configured user model [{$userModel}] does not exist.");

            return;
        }

        // Check contract implementation
        if (is_string($contract) && interface_exists($contract)) {
            $implements = class_implements($userModel) ?: [];
            if (! in_array($contract, $implements, true)) {
                Log::warning("IAM: configured user model [{$userModel}] does not implement required contract [{$contract}]. Please add 'implements {$contract}' to your User model.");
            }
        }

        // Check trait usage
        if (is_string($trait) && trait_exists($trait)) {
            $uses = class_uses_recursive($userModel) ?: [];
            if (! in_array($trait, $uses, true)) {
                Log::warning("IAM: configured user model [{$userModel}] does not use trait [{$trait}]. Please add 'use {$trait};' to your User model.");
            }
        }

        // Check HasTenants implementation when tenancy is enabled
        if (config('iam.tenant.enabled', false)) {
            $implements = class_implements($userModel) ?: [];
            if (! in_array(\Filament\Models\Contracts\HasTenants::class, $implements, true)) {
                Log::warning("IAM: Tenancy is enabled but user model [{$userModel}] does not implement HasTenants interface. Please add 'implements HasTenants' to your User model or disable tenancy in config.");
            }
        }
    }
}
