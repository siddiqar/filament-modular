<?php

namespace Sekeco\Iam\Models;

use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Sekeco\Iam\Enums\TenantRole;

class Tenant extends Model implements HasCurrentTenantLabel
{
    use HasFactory;

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the users that belong to this tenant.
     */
    public function users(): BelongsToMany
    {
        $userModel = config('iam.user_model', \App\Models\User::class);

        return $this->belongsToMany($userModel, 'user_tenant')
            ->withPivot(['role', 'invited_by', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get pending invitations for this tenant.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    /**
     * Get pending invitations only.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class)
            ->whereNull('accepted_at')
            ->whereNull('rejected_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Get owners of this tenant.
     */
    public function owners(): BelongsToMany
    {
        $userModel = config('iam.user_model', \App\Models\User::class);

        return $this->belongsToMany($userModel, 'user_tenant')
            ->wherePivot('role', TenantRole::OWNER->value)
            ->withPivot(['role', 'invited_by', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get admins of this tenant.
     */
    public function admins(): BelongsToMany
    {
        $userModel = config('iam.user_model', \App\Models\User::class);

        return $this->belongsToMany($userModel, 'user_tenant')
            ->wherePivot('role', TenantRole::ADMIN->value)
            ->withPivot(['role', 'invited_by', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Check if user has a specific role in this tenant.
     */
    public function userHasRole($user, TenantRole|string $role): bool
    {
        $roleValue = $role instanceof TenantRole ? $role->value : $role;

        return $this->users()
            ->wherePivot('user_id', is_object($user) ? $user->id : $user)
            ->wherePivot('role', $roleValue)
            ->exists();
    }

    /**
     * Get user's role in this tenant.
     */
    public function getUserRole($user): ?string
    {
        $pivot = $this->users()
            ->wherePivot('user_id', is_object($user) ? $user->id : $user)
            ->first();

        return $pivot?->pivot?->role;
    }

    /**
     * Get the label to display above the current tenant name in the tenant switcher.
     */
    public function getCurrentTenantLabel(): string
    {
        $displayName = config('iam.tenant.display_name', 'Organization');

        return "Active {$displayName}";
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Sekeco\Iam\Database\Factories\TenantFactory::new();
    }
}
