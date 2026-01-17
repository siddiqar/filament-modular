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
            $panelId = (string) config('iam.panel.id', 'admin');

            if ($panel->getId() !== $panelId) {
                return;
            }

            // Register IAM plugin
            $panel->plugin(IamPlugin::make());

            // Configure tenancy if enabled
            if (config('iam.tenant.enabled', false)) {
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
