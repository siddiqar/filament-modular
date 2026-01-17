# IAM Plugin - Tenancy Configuration Examples

## Example 1: With Tenancy Enabled (Multi-Organization SaaS)

```php
// config/iam.php or published config
return [
    'tenant' => [
        'enabled' => true,
        'model' => \Sekeco\Iam\Models\Tenant::class,
        'display_name' => 'Organization',
        'slug_attribute' => 'slug',
        
        'menu' => [
            'searchable' => true,
            'hidden' => false,
        ],
    ],
];
```

**User Model:**
```php
class User extends Authenticatable implements MustVerifyEmail, HasIam, HasTenants
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, InteractsWithIam;
}
```

**Usage:**
```php
// Get user's organizations
$organizations = auth()->user()->tenants;

// Check access
if (auth()->user()->canAccessTenant($organization)) {
    // User can access this organization
}

// Switch to organization
Filament::setTenant($organization);
```

---

## Example 2: Without Tenancy (Single Organization App)

```php
// config/iam.php or published config
return [
    'tenant' => [
        'enabled' => false, // Disable tenancy
    ],
];
```

**User Model:**
```php
class User extends Authenticatable implements MustVerifyEmail, HasIam
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, InteractsWithIam;
    
    // No HasTenants interface needed!
}
```

**What happens:**
- Tenant resource automatically hidden from navigation
- `getTenants()` returns empty collection
- `canAccessTenant()` returns false
- No tenant scoping applied to queries
- Simpler setup for single-organization apps

---

## Example 3: Team-based Application

```php
return [
    'tenant' => [
        'enabled' => true,
        'model' => \Sekeco\Iam\Models\Tenant::class,
        'display_name' => 'Team', // Changed from Organization
        'slug_attribute' => 'slug',
        
        'menu' => [
            'searchable' => true,
            'hidden' => false,
        ],
        
        'route_prefix' => 'team', // URLs: /admin/team/{team-slug}
    ],
];
```

Now your app says "Team" instead of "Organization" everywhere!

---

## Example 4: Subdomain-based Tenancy

```php
return [
    'tenant' => [
        'enabled' => true,
        'model' => \Sekeco\Iam\Models\Tenant::class,
        'display_name' => 'Company',
        'slug_attribute' => 'slug',
        
        'domain' => '{tenant:slug}.yourdomain.com', // acme.yourdomain.com
    ],
];
```

Each tenant gets their own subdomain!

---

## Migration Guide: Enabling/Disabling Tenancy

### To Disable Tenancy

1. Set `iam.tenant.enabled` to `false` in config
2. Remove `HasTenants` from User model:
   ```php
   - class User extends Authenticatable implements MustVerifyEmail, HasIam, HasTenants
   + class User extends Authenticatable implements MustVerifyEmail, HasIam
   ```
3. Clear cache: `php artisan config:clear`
4. Restart dev server

### To Enable Tenancy

1. Set `iam.tenant.enabled` to `true` in config
2. Add `HasTenants` to User model:
   ```php
   + use Filament\Models\Contracts\HasTenants;
   
   - class User extends Authenticatable implements MustVerifyEmail, HasIam
   + class User extends Authenticatable implements MustVerifyEmail, HasIam, HasTenants
   ```
3. Ensure migrations are run: `php artisan migrate`
4. Clear cache: `php artisan config:clear`
5. Restart dev server

---

## Common Use Cases

### Single Organization (No Tenancy Needed)
- Internal company tools
- Single client applications
- Personal projects

**Config:** `'tenant' => ['enabled' => false]`

### Multi-Tenant SaaS
- Multiple organizations/companies
- Each customer has their own space
- Data isolation required

**Config:** `'tenant' => ['enabled' => true, 'display_name' => 'Organization']`

### Team Collaboration
- Users work in teams
- Switching between teams
- Shared resources per team

**Config:** `'tenant' => ['enabled' => true, 'display_name' => 'Team']`
