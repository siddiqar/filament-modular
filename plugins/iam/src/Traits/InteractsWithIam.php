<?php

namespace Sekeco\Iam\Traits;

use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Trait for models that interact with the IAM plugin.
 *
 * This trait provides default implementations for panel access, tenant
 * management (when enabled), and automatic merging of IAM-configured
 * attributes into the host model at boot time.
 *
 * Tenancy methods (getTenants, canAccessTenant, tenants) are only active
 * when tenancy is enabled in config. The model will automatically implement
 * HasTenants interface when needed.
 */
trait InteractsWithIam
{
    /**
     * Boot the InteractsWithIam trait.
     *
     * Merges IAM-configured attributes (fillable, casts, hidden, appends)
     * into the model at boot time.
     */
    public static function bootInteractsWithIam(): void
    {
        $model = new static;
        $config = (array) config('iam.user_attributes', []);

        // Merge fillable attributes
        $fillable = (array) ($config['fillable'] ?? []);
        if (! empty($fillable)) {
            if (method_exists($model, 'mergeFillable')) {
                $model->mergeFillable($fillable);
            } else {
                $existingFillable = method_exists($model, 'getFillable')
                    ? $model->getFillable()
                    : (property_exists($model, 'fillable') ? $model->fillable : []);
                $model->fillable = array_values(array_unique(array_merge($existingFillable, $fillable)));
            }
        }

        // Merge casts
        $casts = (array) ($config['casts'] ?? []);
        if (! empty($casts)) {
            if (method_exists($model, 'mergeCasts')) {
                $model->mergeCasts($casts);
            }
            // Note: mergeCasts is available in Laravel 8+, no fallback needed for modern Laravel
        }

        // Merge hidden attributes
        $hidden = (array) ($config['hidden'] ?? []);
        if (! empty($hidden)) {
            $existingHidden = method_exists($model, 'getHidden')
                ? $model->getHidden()
                : (property_exists($model, 'hidden') ? $model->hidden : []);
            $model->hidden = array_values(array_unique(array_merge($existingHidden, $hidden)));
        }

        // Merge appends
        $appends = (array) ($config['appends'] ?? []);
        if (! empty($appends)) {
            $existingAppends = property_exists($model, 'appends') ? $model->appends : [];
            $model->appends = array_values(array_unique(array_merge($existingAppends, $appends)));
        }
    }

    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        $config = config('iam.panel', []);
        $allowedRoles = (array) ($config['allowed_roles'] ?? []);

        // Role-based check (if Spatie HasRoles trait is present)
        if (! empty($allowedRoles) && method_exists($this, 'hasAnyRole')) {
            if ($this->hasAnyRole($allowedRoles)) {
                return true;
            }
        }

        // Email domain fallback check
        $allowedDomains = (array) ($config['allowed_email_domains'] ?? []);
        if (! empty($allowedDomains) && isset($this->email)) {
            foreach ($allowedDomains as $domain) {
                if (Str::endsWith($this->email, "@{$domain}")) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the tenants relationship (many-to-many).
     */
    public function tenants(): BelongsToMany
    {
        $tenantModel = config('iam.tenant.model', \Sekeco\Iam\Models\Tenant::class);

        return $this->belongsToMany($tenantModel, 'user_tenant')
            ->withPivot(['role', 'invited_by', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get all tenants that the user belongs to for the given panel.
     */
    public function getTenants(Panel $panel): Collection
    {
        // Only return tenants if tenancy is enabled
        if (! config('iam.tenant.enabled', false)) {
            return collect();
        }

        return $this->tenants;
    }

    /**
     * Determine if the user can access the given tenant.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // Check if user belongs to this tenant
        return $this->tenants()->whereKey($tenant->getKey())->exists();
    }

    /**
     * Get user's role in a specific tenant.
     */
    public function getRoleInTenant(Model $tenant): ?string
    {
        $pivot = $this->tenants()
            ->where('tenants.id', $tenant->getKey())
            ->first();

        return $pivot?->pivot?->role;
    }

    /**
     * Check if user has a specific role in a tenant.
     */
    public function hasRoleInTenant(Model $tenant, string $role): bool
    {
        return $this->getRoleInTenant($tenant) === $role;
    }

    /**
     * Check if user is owner of a tenant.
     */
    public function isOwnerOfTenant(Model $tenant): bool
    {
        return $this->hasRoleInTenant($tenant, \Sekeco\Iam\Enums\TenantRole::OWNER->value);
    }

    /**
     * Check if user can manage members in a tenant.
     */
    public function canManageMembersInTenant(Model $tenant): bool
    {
        $role = $this->getRoleInTenant($tenant);

        return in_array($role, [
            \Sekeco\Iam\Enums\TenantRole::OWNER->value,
            \Sekeco\Iam\Enums\TenantRole::ADMIN->value,
        ]);
    }

    /**
     * Get pending invitations for this user.
     */
    public function pendingInvitations()
    {
        return \Sekeco\Iam\Models\TenantInvitation::forEmail($this->email)
            ->pending()
            ->with(['tenant', 'inviter']);
    }
}
