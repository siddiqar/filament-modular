# IAM Plugin Documentation

## Overview

Plugin IAM menyediakan sistem Identity and Access Management (IAM) yang lengkap dan configurable untuk aplikasi Laravel dengan Filament v5. Plugin ini mendukung:

- User authentication & authorization
- Role & Permission management (Spatie Laravel Permission)
- Multi-tenancy (Organizations/Teams)
- Configurable panel access rules
- Flexible user model attributes

## Structure

```
plugins/iam/
├── config/
│   └── iam.php                 # Main configuration file
├── src/
│   ├── Contracts/
│   │   └── HasIam.php         # Interface for User model
│   ├── Traits/
│   │   └── InteractsWithIam.php # Trait for User model implementation
│   ├── Models/
│   │   └── Tenant.php         # Tenant model
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── Roles/         # Role management
│   │   │   ├── Permissions/   # Permission management  
│   │   │   ├── Users/         # User management
│   │   │   └── Tenants/       # Tenant management
│   │   └── Pages/
│   │       ├── Profile.php    # User profile page
│   │       └── Registration.php # Registration page
│   └── Providers/
│       └── IamServiceProvider.php
└── database/
    ├── migrations/
    │   ├── *_create_tenants_table.php
    │   └── *_create_user_tenant_table.php
    └── factories/
        └── TenantFactory.php
```

## Configuration

Edit `plugins/iam/config/iam.php` to customize:

### User Model Configuration
```php
'user_model' => \App\Models\User::class,
'user_contract' => \Sekeco\Iam\Contracts\HasIam::class,
'user_trait' => \Sekeco\Iam\Traits\InteractsWithIam::class,
```

### Tenant Configuration
```php
'tenant' => [
    'enabled' => true,
    'model' => \Sekeco\Iam\Models\Tenant::class,
    'display_name' => 'Organization', // Can be: 'Team', 'Company', etc.
    'slug_attribute' => 'slug',
    
    'menu' => [
        'searchable' => true,
        'hidden' => false,
    ],

    'route_prefix' => null, // e.g., 'team' -> /admin/team/{tenant}
    'domain' => null,       // e.g., '{tenant}.example.com'
],
```

### Panel Access Rules
```php
'panel' => [
    'id' => 'admin',
    'super_admin_roles' => ['super_admin'],
    'allowed_roles' => ['admin', 'super_admin'],
    'allowed_email_domains' => ['example.com'],
],
```

### User Attributes (Auto-merged to User model)
```php
'user_attributes' => [
    'fillable' => [
        // Add IAM-specific fillable fields
    ],
    'casts' => [
        // Add IAM-specific casts
    ],
    'hidden' => [
        // Add IAM-specific hidden fields
    ],
    'appends' => [
        // Add IAM-specific appended attributes
    ],
],
```

## User Model Setup

Your `App\Models\User` model should implement the `HasIam` contract and use the `InteractsWithIam` trait:

### With Tenancy Enabled (default)

When tenancy is enabled (`iam.tenant.enabled = true`), also implement `HasTenants`:

```php
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

class User extends Authenticatable implements MustVerifyEmail, HasIam, HasTenants
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, InteractsWithIam;

    // Your model configuration...
}
```

### Without Tenancy

When tenancy is disabled (`iam.tenant.enabled = false`), only implement `HasIam`:

```php
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Sekeco\Iam\Contracts\HasIam;
use Sekeco\Iam\Traits\InteractsWithIam;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail, HasIam
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, InteractsWithIam;

    // Your model configuration...
}
```

## Features

### Panel Access Control

Users can access the panel if they meet ANY of these criteria:
1. Have one of the allowed roles (configured in `iam.panel.allowed_roles`)
2. Email ends with allowed domain (configured in `iam.panel.allowed_email_domains`)

Super admin roles (configured in `iam.panel.super_admin_roles`) bypass all permission checks.

### Multi-Tenancy with Role Management

When enabled (`iam.tenant.enabled = true`), the plugin provides:

- **User-Tenant Relationships**: Users can belong to multiple tenants (many-to-many)
- **Tenant-Specific Roles**: Each user has a role per tenant (Owner, Admin, Member, Viewer)
- **Invitation System**: Invite users via email with specific roles
- **Permission Checking**: Check user permissions within tenant context
- **Automatic Scoping**: Resources are automatically scoped to current tenant

See [TENANT_ROLES.md](TENANT_ROLES.md) for complete documentation on roles and invitations.

#### Available Tenant Roles

- **Owner**: Full access including tenant deletion and member management
- **Admin**: Manage settings and invite members
- **Member**: Standard access to tenant resources
- **Viewer**: Read-only access

### Panel Access Control

Multi-tenancy is **completely optional** and controlled via configuration. When enabled (`iam.tenant.enabled = true`):

- Users can belong to multiple tenants (many-to-many)
- Tenant switcher appears in the panel
- Resources are automatically scoped to current tenant
- Tenant display name is configurable (Organization, Team, Company, etc.)
- User model MUST implement `HasTenants` interface

When disabled (`iam.tenant.enabled = false`):
- No tenant features are active
- Tenant resource is hidden from navigation
- User model should NOT implement `HasTenants` interface
- All tenant-related methods return empty/false results

#### Managing User-Tenant Relationships

```php
use Sekeco\Iam\Services\TenantInvitationService;
use Sekeco\Iam\Enums\TenantRole;

$service = app(TenantInvitationService::class);

// Invite a user
$invitation = $service->invite(
    tenant: $tenant,
    email: 'user@example.com',
    role: TenantRole::ADMIN
);

// Check user's role in tenant
$role = auth()->user()->getRoleInTenant($tenant);

// Check if user can manage members
if (auth()->user()->canManageMembersInTenant($tenant)) {
    // User can invite/remove members
}

// Update member role
$service->updateMemberRole($tenant, $user, TenantRole::MEMBER);

// Remove member
$service->removeMember($tenant, $user);
```

See [TENANT_ROLES.md](TENANT_ROLES.md) for complete documentation.

### Customizing Tenant Display Name

Change the `display_name` in config to customize labels throughout the app:

```php
'tenant' => [
    'display_name' => 'Team', // Changes "Organization" to "Team" everywhere
],
```

This affects:
- Navigation labels
- Page titles
- Tenant switcher labels
- Resource names

### Extending User Model

Add fields to user model without modifying `app/Models/User.php` by configuring `user_attributes`:

```php
'user_attributes' => [
    'fillable' => ['phone', 'avatar_url'],
    'casts' => ['preferences' => 'array'],
    'hidden' => ['secret_token'],
    'appends' => ['full_name'],
],
```

These attributes are automatically merged into the User model at boot time.

## Testing

```bash
# Run IAM tests
php artisan test plugins/iam/tests

# Run specific test
php artisan test --filter=IamServiceProviderTest
```

## Migrations

### Running Migrations

```bash
# Run all migrations (including IAM plugin)
php artisan migrate

# Fresh migration with seeders
php artisan migrate:fresh --seed
```

### Creating New Migrations in IAM Plugin

When you need to create new migrations for the IAM plugin, use the `--path` option to place them in the plugin's migration directory:

```bash
# Create migration in IAM plugin
php artisan make:migration create_your_table_name --path=plugins/iam/database/migrations

# Or for modular applications, you can use:
php artisan make:migration create_your_table_name --modules=iam
```

The migrations in `plugins/iam/database/migrations/` are automatically loaded by the `IamServiceProvider`.

## Disabling Tenancy

To completely disable tenancy, set `iam.tenant.enabled` to `false` in config:

```php
'tenant' => [
    'enabled' => false, // Disable multi-tenancy
],
```

When tenancy is disabled:
- The Tenant resource will automatically hide from navigation
- Tenant-related methods (`getTenants`, `canAccessTenant`, `tenants`) will return empty results
- No tenant scoping will be applied
- User model should NOT implement `HasTenants` interface (remove it)
- Tenant migrations will still exist but can be ignored

**Important:** If you disable tenancy, remember to:
1. Remove `HasTenants` from your User model's implements clause
2. Clear config cache: `php artisan config:clear`
3. Restart any running dev servers

## Security Notes

- Always validate that users have appropriate permissions before performing sensitive actions
- The `super_admin` role bypasses ALL permission checks - use with caution
- Tenant scoping is automatic for resources in the admin panel
- Queries outside the panel are NOT automatically scoped to tenants
