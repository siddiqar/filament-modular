<?php

namespace Sekeco\Iam\Providers;

use Filament\Panel;
use Illuminate\Support\Facades\Gate;
use Sekeco\Iam\IamPlugin;
use Illuminate\Support\ServiceProvider;
use Sekeco\Iam\Policies\PermissionPolicy;
use Sekeco\Iam\Policies\RolePolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class IamServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		Panel::configureUsing(function (Panel $panel): void {
			if ($panel->getId() !== 'admin') {
				return;
			}

			$panel->plugin(IamPlugin::make());
		});
	}

	public function boot(): void
	{
		// Register policies
		Gate::policy(Role::class, RolePolicy::class);
		Gate::policy(Permission::class, PermissionPolicy::class);

		// Super Admin bypass - give super admin all permissions
		Gate::before(function ($user, $ability) {
			return $user->hasRole('super_admin') ? true : null;
		});
	}
}
