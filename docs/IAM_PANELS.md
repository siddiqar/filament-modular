# IAM Plugin - Panel Architecture

## Overview

This IAM plugin implements a **two-panel architecture** to separate global system administration from tenant-specific application features:

### 1. Admin Panel (`/admin`) - GLOBAL
- **URL**: `http://localhost/admin`
- **Tenancy**: ❌ NO - This panel is NOT multitenant
- **Purpose**: System-wide administration and configuration
- **Access Control**: Only users with `super_admin` or `admin` roles
- **Resources Location**: `plugins/iam/src/Filament/Resources/`

**Available Resources:**
- **Users** - Manage all users across all tenants
- **Roles** - Global role management (not tenant-scoped)
- **Tenants** - CRUD operations for organizations/teams
- **Tenant Members** - View and manage members across all tenants

**Use Cases:**
- Super admin managing all users
- Creating and configuring tenants
- Global role and permission management
- Cross-tenant reporting and analytics
- System configuration

### 2. App Panel (`/app/{tenant}`) - MULTITENANT
- **URL**: `http://localhost/app/{tenant-slug}`
- **Tenancy**: ✅ YES - Fully multitenant (when `iam.tenant.enabled = true`)
- **Purpose**: End-user application features scoped to specific tenant
- **Access Control**: All authenticated users with tenant membership
- **Resources Location**: `app/Filament/App/Resources/`

**Available Resources:**
- *Empty by default* - Developers create their own tenant-scoped resources here

**Use Cases:**
- Projects, Tasks, Documents (tenant-specific)
- Team collaboration features
- Organization/Tenant-specific data
- End-user facing application logic

---

## Configuration

Configure in `config/iam.php`:

```php
return [
    'panel' => [
        'admin_id' => 'admin',  // Global admin panel
        'app_id' => 'app',      // Multitenant app panel
        'super_admin_roles' => ['super_admin'],
    ],

    'tenant' => [
        'enabled' => true,  // ⚠️ Only affects App panel, NOT admin panel
        'model' => \Sekeco\Iam\Models\Tenant::class,
        'display_name' => 'Organization',
        'slug_attribute' => 'slug',
        // ...
    ],

    'access_control' => [
        // Only these roles can access the GLOBAL admin panel
        'admin_panel_roles' => ['super_admin', 'admin'],
    ],
];
```

---

## Access Control Logic

### Admin Panel Authorization (`/admin`)

Implemented in `User::canAccessPanel()`:

```php
if ($panel->getId() === 'admin') {
    $allowedRoles = config('iam.access_control.admin_panel_roles', ['super_admin', 'admin']);
    return $this->hasAnyRole($allowedRoles);
}
```

✅ **Allowed**: Users with `super_admin` OR `admin` role  
❌ **Denied**: All other users (including regular tenant members)

### App Panel Authorization (`/app/{tenant}`)

```php
if ($panel->getId() === 'app') {
    return true; // All authenticated users can access
}
```

✅ **Allowed**: All authenticated users  
🔒 **Scoping**: Filament automatically scopes data to current tenant via middleware

---

## Creating Resources

### Admin Panel Resource (Global)

```bash
php artisan make:filament-resource GlobalReport --panel=admin
```

**Location**: `app/Filament/Resources/GlobalReportResource.php`

**Characteristics:**
- ❌ Not scoped to tenant
- ✅ Accessible only by super_admin/admin
- ✅ Can query across all tenants
- ✅ Use for system-wide CRUD

**Example:**
```php
class GlobalReportResource extends Resource
{
    protected static ?string $model = Report::class;
    
    // No $isScopedToTenant property needed (admin panel is global)
    
    public static function getEloquentQuery(): Builder
    {
        // Access ALL reports across ALL tenants
        return parent::getEloquentQuery();
    }
}
```

### App Panel Resource (Tenant-Scoped)

```bash
php artisan make:filament-resource Project --panel=app
```

**Location**: `app/Filament/App/Resources/ProjectResource.php`

**Characteristics:**
- ✅ Automatically scoped to current tenant
- ✅ Accessible by all tenant members
- ✅ Queries filtered by `tenant_id` automatically
- ✅ Use for tenant-specific features

**Example:**
```php
class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';
    
    public static function getEloquentQuery(): Builder
    {
        // Automatically filtered to current tenant
        return parent::getEloquentQuery();
    }
}
```

---

## Developer Workflow

### Step 1: Understand Your Feature

**Ask yourself**: Is this feature global or tenant-specific?

| Feature | Panel | Reason |
|---------|-------|--------|
| User Management | Admin | Global - manage ALL users |
| Role Management | Admin | Global - system-wide roles |
| Tenant CRUD | Admin | Global - create/manage tenants |
| Projects | App | Tenant-specific feature |
| Tasks | App | Scoped to organization |
| Documents | App | Team collaboration |

### Step 2: Create Resource in Correct Panel

**Global Feature:**
```bash
php artisan make:filament-resource User --panel=admin
```

**Tenant Feature:**
```bash
php artisan make:filament-resource Task --panel=app
```

### Step 3: Configure Tenant Ownership (App Panel Only)

For tenant-scoped resources:

```php
class TaskResource extends Resource
{
    protected static ?string $model = Task::class;
    
    // Tell Filament how to scope to tenant
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';
    
    // Optional: Customize tenant column
    // protected static ?string $tenantOwnershipColumnName = 'organization_id';
}
```

### Step 4: Test Access Control

1. **Login as super_admin**:
   - ✅ Can access `/admin` (Roles, Users, Tenants)
   - ✅ Can access `/app/{tenant}` (your features)

2. **Login as regular user**:
   - ❌ Cannot access `/admin`
   - ✅ Can access `/app/{tenant}` (only their tenant)

---

## Important Notes

### ⚠️ Tenancy Only Affects App Panel

```php
// config/iam.php
'tenant' => [
    'enabled' => true,  // ← Only for App panel!
],
```

- When `enabled = true`: App panel becomes multitenant (`/app/{tenant}`)
- When `enabled = false`: App panel is single-tenant (`/app`)
- **Admin panel is ALWAYS global** regardless of this setting

### 🚫 Do Not Put Tenant Resources in Admin Panel

❌ **Wrong:**
```php
// app/Filament/Resources/ProjectResource.php (in admin panel)
protected static ?string $tenantOwnershipRelationshipName = 'tenant';
```

Admin panel is global - tenant scoping won't work!

✅ **Correct:**
```php
// app/Filament/App/Resources/ProjectResource.php (in app panel)
protected static ?string $tenantOwnershipRelationshipName = 'tenant';
```

### 📝 IAM Resources Are Intentionally Global

The IAM plugin resources are in admin panel because:

- **TenantResource**: Manages ALL tenants (not scoped to one)
- **UserResource**: Manages ALL users (cross-tenant)
- **RoleResource**: Global roles (not tenant-specific)
- **TenantMemberResource**: View members across ALL tenants

If you need **tenant-scoped** member management, create a new resource in App panel.

---

## Testing

### 1. Verify Panel Configuration

```bash
php artisan route:list --json | jq -r '.[] | select(.uri | contains("admin") or contains("app")) | .uri' | head -20
```

**Expected Output:**
```
admin                          ← Global
admin/users                    ← Global
admin/roles                    ← Global
admin/tenants                  ← Global
app/{tenant}                   ← Multitenant
app/{tenant}/projects          ← Multitenant
```

### 2. Test Admin Panel Access

```bash
# Login as regular user
curl http://localhost/admin
# Should redirect to /app/{tenant} or show 403

# Login as super_admin
curl http://localhost/admin
# Should show admin dashboard
```

### 3. Test Tenant Scoping

```php
// In app panel, test tenant scoping
use Filament\Facades\Filament;

$currentTenant = Filament::getTenant(); // Returns current tenant model
$projects = Project::all(); // Automatically scoped to $currentTenant
```

---

## Migration Guide

### From Old Single-Panel Architecture

If you're upgrading from a single admin panel:

1. **Keep global resources in Admin panel** (Users, Roles, Tenants)
2. **Move tenant-specific resources to App panel**:
   ```bash
   mv app/Filament/Resources/ProjectResource.php \
      app/Filament/App/Resources/ProjectResource.php
   ```
3. **Update namespace**:
   ```php
   namespace App\Filament\App\Resources;
   ```
4. **Add tenant ownership**:
   ```php
   protected static ?string $tenantOwnershipRelationshipName = 'tenant';
   ```

---

## Troubleshooting

### Issue: "Cannot access admin panel"

**Cause**: User doesn't have required role  
**Solution**: Assign `super_admin` or `admin` role:

```php
$user->assignRole('admin');
```

### Issue: "Seeing all tenants' data in App panel"

**Cause**: Missing tenant ownership configuration  
**Solution**: Add to resource:

```php
protected static ?string $tenantOwnershipRelationshipName = 'tenant';
```

### Issue: "Tenant dropdown not showing in App panel"

**Cause**: Tenancy not enabled in config  
**Solution**: Enable in `config/iam.php`:

```php
'tenant' => ['enabled' => true],
```

---

## Summary

| Aspect | Admin Panel | App Panel |
|--------|-------------|-----------|
| **URL Pattern** | `/admin/*` | `/app/{tenant}/*` |
| **Tenancy** | ❌ Global | ✅ Multitenant |
| **Access** | super_admin/admin only | All authenticated users |
| **Purpose** | System administration | Application features |
| **Resources** | Users, Roles, Tenants | Projects, Tasks, etc. |
| **Scope** | Cross-tenant queries OK | Auto-scoped to tenant |

**Golden Rule**: 
- **Admin = Global System Management**
- **App = Tenant-Specific Features**
