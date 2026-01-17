# Tenant Invitation & Role Management System

## Overview

Plugin IAM menyediakan sistem lengkap untuk:
- Mengundang anggota ke tenant (organization/team)
- Mengelola role per-tenant (Owner, Admin, Member, Viewer)
- Tracking invitation status (pending, accepted, rejected, expired)
- Permission checking dalam konteks tenant

## Architecture

### Tenant Roles

Setiap user memiliki role yang berbeda di setiap tenant. Role disimpan di pivot table `user_tenant`.

#### Available Roles

```php
use Sekeco\Iam\Enums\TenantRole;

TenantRole::OWNER   // Full access, can delete tenant
TenantRole::ADMIN   // Manage settings, invite members
TenantRole::MEMBER  // Standard access
TenantRole::VIEWER  // Read-only access
```

#### Role Permissions

| Role   | View | Update | Delete | Invite | Manage Members |
|--------|------|--------|--------|--------|----------------|
| Owner  | ✓    | ✓      | ✓      | ✓      | ✓              |
| Admin  | ✓    | ✓      | ✗      | ✓      | ✓              |
| Member | ✓    | ✗      | ✗      | ✗      | ✗              |
| Viewer | ✓    | ✗      | ✗      | ✗      | ✗              |

### Database Structure

#### `user_tenant` Pivot Table
```php
id
user_id              // FK to users
tenant_id            // FK to tenants
role                 // owner, admin, member, viewer
invited_by           // FK to users (who invited)
invited_at           // Timestamp when invited
joined_at            // Timestamp when user accepted
created_at
updated_at
```

#### `tenant_invitations` Table
```php
id
tenant_id            // FK to tenants
invited_by           // FK to users
email                // Email to invite
role                 // Role to assign when accepted
token                // Unique token for accepting
expires_at           // Invitation expiry
accepted_at          // Null if pending
rejected_at          // Null if pending
created_at
updated_at
```

## Usage Examples

### 1. Inviting Members

```php
use Sekeco\Iam\Services\TenantInvitationService;
use Sekeco\Iam\Enums\TenantRole;

$invitationService = app(TenantInvitationService::class);

// Invite a user as Admin
$invitation = $invitationService->invite(
    tenant: $tenant,
    email: 'john@example.com',
    role: TenantRole::ADMIN,
    invitedBy: auth()->id()
);

// Access the token for email/notification
$acceptUrl = route('tenant.invitation.accept', ['token' => $invitation->token]);
```

### 2. Accepting Invitations

```php
// In your controller
public function accept(Request $request, string $token)
{
    $invitationService = app(TenantInvitationService::class);
    
    try {
        $invitationService->accept($token, auth()->user());
        return redirect()->route('tenant.dashboard')
            ->with('success', 'You have joined the organization!');
    } catch (\Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

### 3. Checking User Roles in Tenant

```php
// In your model (using InteractsWithIam trait)
$tenant = Filament::getTenant();

// Get user's role
$role = auth()->user()->getRoleInTenant($tenant);

// Check specific role
if (auth()->user()->hasRoleInTenant($tenant, TenantRole::ADMIN->value)) {
    // User is admin
}

// Check if owner
if (auth()->user()->isOwnerOfTenant($tenant)) {
    // User is owner
}

// Check if can manage members
if (auth()->user()->canManageMembersInTenant($tenant)) {
    // User can invite/remove members
}
```

### 4. Managing Member Roles

```php
$invitationService = app(TenantInvitationService::class);

// Update member role
$invitationService->updateMemberRole(
    tenant: $tenant,
    user: $user,
    newRole: TenantRole::ADMIN
);

// Remove member
$invitationService->removeMember(
    tenant: $tenant,
    user: $user
);

// Note: Cannot remove last owner or change last owner's role
```

### 5. Working with Tenant Members

```php
// Get all members
$members = $tenant->users;

// Get only owners
$owners = $tenant->owners;

// Get only admins
$admins = $tenant->admins;

// Check if user has specific role
if ($tenant->userHasRole($user, TenantRole::OWNER)) {
    // User is owner
}

// Get user's role
$role = $tenant->getUserRole($user);
```

### 6. Pending Invitations

```php
// Get user's pending invitations
$invitations = auth()->user()->pendingInvitations()->get();

// Get tenant's pending invitations
$pendingInvites = $tenant->pendingInvitations;

// Cancel an invitation
$invitationService->cancelInvitation($invitation);
```

## Integration dengan Plugin Lain

### Scoping Resources to Tenant

Ketika membuat resource di plugin lain yang perlu tenant scoping:

```php
use Filament\Resources\Resource;
use Filament\Facades\Filament;

class YourResource extends Resource
{
    // Enable automatic tenant scoping
    protected static bool $isScopedToTenant = true;
    
    // Custom tenant relationship name (default: 'tenant')
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';
    
    // Custom tenant relationship on tenant model (default: pluralized model name)
    protected static ?string $tenantRelationshipName = 'yourModels';
}
```

### Checking Permissions in Context

```php
use Filament\Facades\Filament;

// In your resource/page
public function mount(): void
{
    $tenant = Filament::getTenant();
    
    // Check if current user can manage this resource
    if (!auth()->user()->canManageMembersInTenant($tenant)) {
        abort(403, 'You do not have permission to manage members.');
    }
}
```

### Model with Tenant Relationship

```php
namespace YourPlugin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YourModel extends Model
{
    public function tenant(): BelongsTo
    {
        $tenantModel = config('iam.tenant.model', \Sekeco\Iam\Models\Tenant::class);
        
        return $this->belongsTo($tenantModel);
    }
    
    // Scope to current tenant
    protected static function booted(): void
    {
        if (config('iam.tenant.enabled', false)) {
            static::addGlobalScope('tenant', function ($query) {
                if (\Filament\Facades\Filament::hasTenancy() && \Filament\Facades\Filament::getTenant()) {
                    $query->where('tenant_id', \Filament\Facades\Filament::getTenant()->id);
                }
            });
            
            static::creating(function ($model) {
                if (\Filament\Facades\Filament::hasTenancy() && \Filament\Facades\Filament::getTenant()) {
                    $model->tenant_id = \Filament\Facades\Filament::getTenant()->id;
                }
            });
        }
    }
}
```

## Best Practices

### 1. Always Validate Tenant Context

```php
// In controllers/actions
$tenant = Filament::getTenant();

if (!$tenant) {
    abort(404, 'No tenant selected');
}

if (!auth()->user()->canAccessTenant($tenant)) {
    abort(403, 'Access denied');
}
```

### 2. Protect Owner Role

```php
// Don't allow removing or demoting the last owner
try {
    $invitationService->removeMember($tenant, $user);
} catch (\Exception $e) {
    // Handle: "Cannot remove the last owner"
}
```

### 3. Cleanup Expired Invitations

```php
// In a scheduled task
protected function schedule(Schedule $schedule): void
{
    $schedule->call(function () {
        app(TenantInvitationService::class)->cleanupExpiredInvitations();
    })->daily();
}
```

### 4. Use Enum for Type Safety

```php
// Good
use Sekeco\Iam\Enums\TenantRole;

$invitation = $service->invite(
    tenant: $tenant,
    email: $email,
    role: TenantRole::ADMIN  // Type-safe
);

// Avoid
$invitation = $service->invite(
    tenant: $tenant,
    email: $email,
    role: 'admin'  // String, prone to typos
);
```

## Security Considerations

1. **Always check permissions** sebelum operasi sensitif
2. **Validate tenant ownership** sebelum izinkan akses
3. **Protect owner role** - jangan izinkan remove/demote owner terakhir
4. **Expire invitations** - default 7 hari, cleanup secara berkala
5. **Validate email** - pastikan invitation hanya bisa diterima oleh email yang diundang
6. **Use transactions** - untuk operasi multi-step (invite, accept, remove)

## API Reference

### TenantInvitationService Methods

```php
// Invite user
invite(Tenant $tenant, string $email, TenantRole|string $role, $invitedBy): TenantInvitation

// Accept invitation
accept(string $token, $user = null): bool

// Reject invitation
reject(string $token, $user = null): bool

// Update member role
updateMemberRole(Tenant $tenant, $user, TenantRole|string $newRole): bool

// Remove member
removeMember(Tenant $tenant, $user): bool

// Cancel invitation
cancelInvitation(TenantInvitation $invitation): bool

// Cleanup expired
cleanupExpiredInvitations(): int
```

### InteractsWithIam Trait Methods

```php
getRoleInTenant(Model $tenant): ?string
hasRoleInTenant(Model $tenant, string $role): bool
isOwnerOfTenant(Model $tenant): bool
canManageMembersInTenant(Model $tenant): bool
pendingInvitations(): Collection
```

### Tenant Model Methods

```php
userHasRole($user, TenantRole|string $role): bool
getUserRole($user): ?string
owners(): BelongsToMany
admins(): BelongsToMany
invitations(): HasMany
pendingInvitations(): HasMany
```
