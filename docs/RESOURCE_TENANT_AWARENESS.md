# Resource Tenant Awareness Configuration

## Summary of All Resources

### ✅ Admin Panel Resources (GLOBAL - NO Tenant Scoping)

All resources in the admin panel are configured to be **global** - they manage data across ALL tenants.

| Resource | Location | Tenant Aware? | Configuration | Purpose |
|----------|----------|---------------|---------------|---------|
| **UserResource** | `plugins/iam/src/Filament/Resources/Users/` | ❌ NO | `$isScopedToTenant = false` | Manage ALL users across ALL tenants |
| **RoleResource** | `plugins/iam/src/Filament/Resources/Roles/` | ❌ NO | `$isScopedToTenant = false` | Manage global roles (not tenant-specific) |
| **TenantResource** | `plugins/iam/src/Filament/Resources/Tenants/` | ❌ NO | Default (no tenant scoping) | CRUD for ALL tenants/organizations |
| **TenantMemberResource** | `plugins/iam/src/Filament/Resources/TenantMembers/` | ❌ NO | `$isScopedToTenant = false` | View ALL users who are members of ANY tenant |

**Access**: Only `super_admin` and `admin` roles can access admin panel  
**Routes**: `/admin/*` (no `{tenant}` parameter)  
**Purpose**: System-wide administration and monitoring

---

### ✅ App Panel Resources (TENANT-SCOPED)

Resources in the app panel are **tenant-aware** - automatically scoped to current tenant.

| Resource | Location | Tenant Aware? | Configuration | Purpose |
|----------|----------|---------------|---------------|---------|
| **MemberResource** | `app/Filament/App/Resources/` | ✅ YES | `$tenantOwnershipRelationshipName = 'tenants'` | Manage members within current tenant only |

**Access**: All authenticated users (tenant membership required)  
**Routes**: `/app/{tenant}/*` (tenant-scoped URLs)  
**Purpose**: Tenant-specific features and data management

---

## Detailed Configuration

### 1. UserResource (Admin Panel)

**File**: `plugins/iam/src/Filament/Resources/Users/UserResource.php`

```php
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    // GLOBAL - manages ALL users across ALL tenants
    protected static bool $isScopedToTenant = false;
}
```

**Why Global?**
- Super admins need to see and manage ALL users
- Can create users and assign them to any tenant
- Cross-tenant user management

---

### 2. RoleResource (Admin Panel)

**File**: `plugins/iam/src/Filament/Resources/Roles/RoleResource.php`

```php
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    
    // GLOBAL - roles are system-wide, not tenant-specific
    protected static bool $isScopedToTenant = false;
}
```

**Why Global?**
- Spatie Permission roles are global by default
- Same roles used across all tenants (super_admin, admin, etc.)
- No tenant_id on roles table

**Note**: If you need tenant-specific roles in the future, you would:
1. Add `team_id` support via Spatie's teams feature
2. Create separate RoleResource for App panel
3. Keep this one in Admin panel for global role templates

---

### 3. TenantResource (Admin Panel)

**File**: `plugins/iam/src/Filament/Resources/Tenants/TenantResource.php`

```php
class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    
    // Default: no tenant scoping (tenants don't belong to tenants!)
}
```

**Why Global?**
- Manages the tenants themselves
- Super admins create and configure organizations
- Has MembersRelationManager for managing tenant members

**Features**:
- CRUD for tenants/organizations
- Members relation manager (invite, update role, remove)
- Only accessible by super_admin/admin

---

### 4. TenantMemberResource (Admin Panel)

**File**: `plugins/iam/src/Filament/Resources/TenantMembers/TenantMemberResource.php`

```php
class TenantMemberResource extends Resource
{
    protected static ?string $model = null; // Uses User model via relationship
    
    // GLOBAL - shows ALL users who are members of ANY tenant
    protected static bool $isScopedToTenant = false;
}
```

**Why Global?**
- Super admin monitoring: see all tenant memberships
- Shows which users belong to which tenants
- Read-only overview of all memberships

**Query**:
```php
protected function getTableQuery(): Builder
{
    // Shows ALL users who are members of ANY tenant
    return User::query()
        ->whereHas('tenants')
        ->with('tenants');
}
```

**Columns**:
- User name, email
- Organizations (badge showing all tenants user belongs to)
- Total Organizations count

**No Actions**: Management done via TenantResource relation manager

---

### 5. MemberResource (App Panel) ⭐ NEW

**File**: `app/Filament/App/Resources/MemberResource.php`

```php
class MemberResource extends Resource
{
    protected static ?string $model = User::class;
    
    // TENANT-SCOPED - only shows members of current tenant
    protected static ?string $tenantOwnershipRelationshipName = 'tenants';
}
```

**Why Tenant-Scoped?**
- End users only see/manage members in their own organization
- Automatic filtering by Filament tenancy middleware
- Privacy: users can't see other tenants' members

**Query**:
```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->whereHas('tenants', function (Builder $query) {
            $tenant = Filament::getTenant();
            $query->where('tenants.id', $tenant->id);
        });
}
```

**Features**:
- View team members
- Invite new members (with role selection)
- Update member roles
- Remove members
- Uses pivot data (role, joined_at, invited_by)

**Access Control**:
- Only users with `canManageMembersInTenant()` permission can invite/edit
- Users cannot remove themselves

---

## Migration Summary

### Changes Made

#### ✅ Fixed Resources:

1. **UserResource**:
   - ❌ **Before**: Had `$tenantOwnershipRelationshipName = 'tenants'` (WRONG)
   - ✅ **After**: `$isScopedToTenant = false` (CORRECT - global resource)

2. **TenantMemberResource**:
   - ⚠️ **Before**: Tried to use `Filament::getTenant()` in admin panel
   - ✅ **After**: Shows ALL tenant members globally (no tenant context)
   - ✅ **After**: Removed invite page (use TenantResource instead)

#### ⭐ New Resources:

3. **MemberResource** (App Panel):
   - Created tenant-scoped version for end users
   - Full CRUD for team members within current tenant
   - Replaces TenantMemberResource functionality for app panel

---

## Testing Verification

Run this tinker command to verify configuration:

```php
$resources = [
    'UserResource' => \Sekeco\Iam\Filament\Resources\Users\UserResource::class,
    'RoleResource' => \Sekeco\Iam\Filament\Resources\Roles\RoleResource::class,
    'TenantResource' => \Sekeco\Iam\Filament\Resources\Tenants\TenantResource::class,
    'TenantMemberResource' => \Sekeco\Iam\Filament\Resources\TenantMembers\TenantMemberResource::class,
    'MemberResource' => \App\Filament\App\Resources\MemberResource::class,
];

foreach ($resources as $name => $class) {
    $reflection = new \ReflectionClass($class);
    $scoped = $reflection->hasProperty('isScopedToTenant') 
        ? $reflection->getProperty('isScopedToTenant')->getDefaultValue() 
        : null;
    $ownership = $reflection->hasProperty('tenantOwnershipRelationshipName')
        ? $reflection->getProperty('tenantOwnershipRelationshipName')->getDefaultValue()
        : null;
    
    echo "$name: scoped=$scoped, ownership=$ownership\n";
}
```

**Expected Output**:
```
UserResource: scoped=false, ownership=null ✓
RoleResource: scoped=false, ownership=null ✓
TenantResource: scoped=true, ownership=null ✓
TenantMemberResource: scoped=false, ownership=null ✓
MemberResource: scoped=true, ownership=tenants ✓
```

---

## Routes Verification

### Admin Panel (Global):
```bash
/admin/users                 # All users
/admin/roles                 # All roles  
/admin/tenants               # All tenants
/admin/tenant-members        # All member relationships
```

### App Panel (Tenant-Scoped):
```bash
/app/{tenant}/members        # Current tenant's members only
```

---

## Best Practices

### When Creating New Resources

**Ask yourself**: Is this resource global or tenant-specific?

#### Global Resources (Admin Panel)
- System configuration
- Cross-tenant reporting
- User management (all users)
- Tenant management

```php
// In admin panel
protected static bool $isScopedToTenant = false;
```

#### Tenant-Scoped Resources (App Panel)
- Projects, Tasks, Documents
- Team-specific data
- Organization features

```php
// In app panel  
protected static ?string $tenantOwnershipRelationshipName = 'tenants';
```

---

## Common Pitfalls

### ❌ Don't Do This

**Mixing tenant scoping in admin panel:**
```php
// plugins/iam/.../SomeResource.php (in admin panel)
protected static ?string $tenantOwnershipRelationshipName = 'tenants'; // WRONG!
```
Admin panel has no tenant context!

**Using Filament::getTenant() in admin panel:**
```php
// In admin panel page
$tenant = Filament::getTenant(); // Returns NULL! Admin panel is global.
```

### ✅ Do This Instead

**Admin panel resources:**
```php
protected static bool $isScopedToTenant = false; // Explicit global
```

**App panel resources:**
```php
protected static ?string $tenantOwnershipRelationshipName = 'tenants';
```

**Accessing tenant in app panel:**
```php
$tenant = Filament::getTenant(); // Works! Returns current tenant model
```

---

## Summary Table

| Resource | Panel | Scoped? | Shows | Actions |
|----------|-------|---------|-------|---------|
| UserResource | Admin | ❌ NO | ALL users | Create, Edit, Delete |
| RoleResource | Admin | ❌ NO | ALL roles | Create, Edit, Delete |
| TenantResource | Admin | ❌ NO | ALL tenants | Create, Edit, Delete, Manage Members |
| TenantMemberResource | Admin | ❌ NO | ALL tenant memberships | View only (read-only overview) |
| MemberResource | App | ✅ YES | Current tenant members | View, Invite, Edit Role, Remove |

**Access Control**:
- Admin Panel: `super_admin` and `admin` roles only
- App Panel: All authenticated users (scoped to their tenants)
