<?php

namespace Sekeco\Iam\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions for each resource
        $resources = [
            'user',
            'role',
            'permission',
        ];

        $permissions = [];

        foreach ($resources as $resource) {
            $permissions[] = "view_any_{$resource}";
            $permissions[] = "view_{$resource}";
            $permissions[] = "create_{$resource}";
            $permissions[] = "update_{$resource}";
            $permissions[] = "delete_{$resource}";
            $permissions[] = "delete_any_{$resource}";
            $permissions[] = "force_delete_{$resource}";
            $permissions[] = "force_delete_any_{$resource}";
            $permissions[] = "restore_{$resource}";
            $permissions[] = "restore_any_{$resource}";
            $permissions[] = "replicate_{$resource}";
            $permissions[] = "reorder_{$resource}";
        }

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create Super Admin role with all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Create Admin role with most permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminPermissions = Permission::whereNotIn('name', [
            'delete_role',
            'delete_any_role',
            'force_delete_role',
            'force_delete_any_role',
            'delete_permission',
            'delete_any_permission',
            'force_delete_permission',
            'force_delete_any_permission',
        ])->get();
        $admin->syncPermissions($adminPermissions);

        // Create User role with basic permissions
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userPermissions = Permission::whereIn('name', [
            'view_any_user',
            'view_user',
        ])->get();
        $user->syncPermissions($userPermissions);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
