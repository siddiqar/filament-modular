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
    | Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configure multi-tenancy for your application. Set 'enabled' to false to
    | completely disable tenant features. When disabled, users do NOT need to
    | implement HasTenants interface, and tenant-related methods will return
    | empty results. This is useful for single-organization applications.
    |
    | When enabled (true), users MUST implement HasTenants interface and the
    | tenant model/display name can be customized (e.g., 'Organization',
    | 'Team', 'Company').
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
        'route_prefix' => null, // e.g., 'team' makes URLs like /admin/team/{tenant}
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
    | Panel Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Filament admin panel that uses IAM features.
    |
    */
    'panel' => [
        'id' => 'admin',

        // Super admin roles that bypass all permission checks
        'super_admin_roles' => ['super_admin'],

        // Roles allowed to access the panel
        'allowed_roles' => ['admin', 'super_admin'],

        // Email domains allowed to access the panel (fallback if no roles)
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
