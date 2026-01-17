<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Sekeco\Iam\Database\Seeders\RolePermissionSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);

        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Assign super_admin role to default user
        $user->assignRole('super_admin');

        // Create default tenant if tenancy is enabled
        if (config('iam.tenant.enabled', false)) {
            $tenantModel = config('iam.tenant.model', \Sekeco\Iam\Models\Tenant::class);
            $tenant = $tenantModel::create([
                'name' => 'Default Organization',
                'slug' => 'default',
            ]);

            // Attach user as owner of the tenant
            $user->tenants()->attach($tenant->id, [
                'role' => \Sekeco\Iam\Enums\TenantRole::OWNER->value,
                'invited_by' => null,
                'invited_at' => null,
                'joined_at' => now(),
            ]);
        }
    }
}
