<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model representing users in your application. This model
    | should implement the HasIam contract and use the InteractsWithIam trait.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | User Contract & Trait
    |--------------------------------------------------------------------------
    |
    | The contract that user models must implement and the trait that provides
    | the default implementation for IAM features including panel access,
    | tenancy support, and attribute merging.
    |
    */
    'user_contract' => \Sekeco\Iam\Contracts\HasIam::class,
    'user_trait' => \Sekeco\Iam\Traits\InteractsWithIam::class,

    /*
    |--------------------------------------------------------------------------
    | Panel Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which Filament panels IAM should apply to:
    | - admin_id: The GLOBAL admin panel (no tenancy, super_admin/admin only)
    | - app_id: The APP panel (multitenant when tenant.enabled = true)
    |
    */
    'panel' => [
        'admin_id' => 'admin', // Global admin panel
        'app_id' => 'app', // Multitenant app panel
        'super_admin_roles' => ['super_admin'], // Roles that bypass all permissions
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configure multi-tenancy for your APP panel (not admin panel).
    | Set 'enabled' to false to completely disable tenant features.
    |
    | When disabled: Users do NOT need HasTenants interface, single-org mode
    | When enabled: Only applies to 'app' panel, admin panel stays global
    |
    | IMPORTANT: Admin panel is ALWAYS global regardless of this setting.
    | Only the App panel uses tenancy when enabled = true.
    |
    */
    'tenant' => [
        'enabled' => true, // Set to false to disable multi-tenancy completely
        'model' => \Sekeco\Iam\Models\Tenant::class,
        'display_name' => 'Organization', // Customize: 'Team', 'Company', etc.
        'slug_attribute' => 'slug',

        // Tenant menu configuration
        'menu' => [
            'searchable' => true,
            'hidden' => false,
        ],

        // Tenant route configuration
        'route_prefix' => null, // e.g., 'team' makes URLs like /app/team/{tenant}
        'domain' => null, // e.g., '{tenant}.example.com' for subdomain routing
    ],

    /*
    |--------------------------------------------------------------------------
    | User Attributes
    |--------------------------------------------------------------------------
    |
    | Attributes that will be merged into the configured user model at boot
    | time. This allows the IAM plugin to extend the user model without
    | modifying the base User class.
    |
    */
    'user_attributes' => [
        'fillable' => [
            // Add IAM-specific fillable fields here
        ],
        'casts' => [
            // Add IAM-specific casts here
        ],
        'hidden' => [
            // Add IAM-specific hidden fields here
        ],
        'appends' => [
            // Add IAM-specific appended attributes here
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Control
    |--------------------------------------------------------------------------
    |
    | Configure access control for the admin panel:
    | - admin_panel_roles: Only these roles can access the GLOBAL admin panel
    | - allowed_email_domains: Email domain whitelist (fallback if no roles)
    |
    */
    'access_control' => [
        'admin_panel_roles' => ['super_admin', 'admin'],
        'allowed_email_domains' => ['example.com'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Models
    |--------------------------------------------------------------------------
    |
    | The Eloquent models used by Spatie Laravel Permission package.
    | These are typically the default Spatie models unless customized.
    |
    */
    'permission_models' => [
        'role' => \Spatie\Permission\Models\Role::class,
        'permission' => \Spatie\Permission\Models\Permission::class,
    ],
];
