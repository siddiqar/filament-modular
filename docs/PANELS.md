# Filament Panel Architecture

This application uses a two-panel architecture:

## 1. Admin Panel (`/admin`)
- **Purpose**: Global system administration and configuration
- **Tenancy**: NO - This panel is NOT multitenant
- **Access**: Only users with `super_admin` or `admin` roles
- **Use Cases**:
  - User management (all users across all tenants)
  - Role & Permission management (global roles)
  - Tenant management (CRUD tenants)
  - System configuration
  - Global settings

## 2. App Panel (`/app`)
- **Purpose**: Application features for end users
- **Tenancy**: YES - This panel IS multitenant (when `iam.tenant.enabled = true`)
- **Access**: All authenticated users with tenant membership
- **Use Cases**:
  - Tenant-specific features
  - Team collaboration
  - Organization-specific data
  - End-user application logic

## Configuration

Configure in `config/iam.php`:

```php
'panel' => [
    'admin_id' => 'admin', // Global admin panel
    'app_id' => 'app',     // Multitenant app panel
    'super_admin_roles' => ['super_admin'],
],

'tenant' => [
    'enabled' => true, // Only affects App panel, NOT admin panel
    // ...
],

'access_control' => [
    'admin_panel_roles' => ['super_admin', 'admin'], // Who can access admin panel
    // ...
],
```

## Developer Guide

### When to use Admin Panel?
- Creating global CRUD resources (Users, Roles, Tenants)
- System-wide configuration
- Cross-tenant reporting
- Superadmin-only features

### When to use App Panel?
- Tenant-specific features (Projects, Tasks, etc.)
- Organization/Team collaboration features
- End-user facing application
- Tenant-scoped data

## Example: Creating Resources

### Admin Panel Resource (Global)
```bash
php artisan make:filament-resource User --panel=admin
# Location: app/Filament/Resources/UserResource.php
```

### App Panel Resource (Tenant-scoped)
```bash
php artisan make:filament-resource Project --panel=app
# Location: app/Filament/App/Resources/ProjectResource.php
```

Resources in the App panel will automatically be scoped to the current tenant when `iam.tenant.enabled = true`.
