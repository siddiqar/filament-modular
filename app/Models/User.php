<?php

namespace App\Models;

use Filament\Models\Contracts\HasTenants;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Sekeco\Iam\Contracts\HasIam;
use Sekeco\Iam\Traits\InteractsWithIam;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasIam, HasTenants, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, InteractsWithIam, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine if user can access a specific Filament panel.
     *
     * Admin panel: Only super_admin and admin roles
     * App panel: All authenticated users with tenant membership
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // Admin panel - restricted to super_admin and admin only
        if ($panel->getId() === 'admin') {
            $allowedRoles = config('iam.access_control.admin_panel_roles', ['super_admin', 'admin']);

            return $this->hasAnyRole($allowedRoles);
        }

        // App panel - all authenticated users can access (tenancy handles scoping)
        if ($panel->getId() === 'app') {
            return true;
        }

        // Default: deny access
        return false;
    }

    /**
     * Get all tenants for this user.
     *
     * Admin panel does NOT use tenancy - return empty collection.
     * App panel uses tenancy from 'tenants' relationship.
     */
    public function getTenants(\Filament\Panel $panel): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        // Admin panel: No tenancy - return empty collection
        if ($panel->getId() === 'admin') {
            return collect([]);
        }

        // App panel: Return user's tenants from relationship
        if ($panel->getId() === 'app' && config('iam.tenant.enabled', false)) {
            return $this->tenants;
        }

        // Default: No tenants
        return collect([]);
    }

    /**
     * Check if user can access a specific tenant.
     *
     * Admin panel has no tenancy.
     * App panel checks tenant membership.
     */
    public function canAccessTenant(\Illuminate\Database\Eloquent\Model $tenant): bool
    {
        // Admin panel: No tenancy, always false
        if (\Filament\Facades\Filament::getCurrentPanel()?->getId() === 'admin') {
            return false;
        }

        // App panel: Check if user is member of tenant
        return $this->tenants->contains($tenant);
    }
}
