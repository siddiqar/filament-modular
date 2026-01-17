# Modular Laravel Application

A modular Laravel application with multi-tenancy support, built with Filament v5 and a plugin-based architecture.

## About This Project

This is a modular Laravel 12 application featuring:

- **Plugin-Based Architecture** - Extensible modular structure using Laravel packages
- **Multi-Tenancy** - Organization-based tenancy with role-based access control
- **Dual Panel System** - Separate admin and application panels
- **IAM Plugin** - Complete identity and access management system
- **Filament v5** - Modern admin panel framework with Livewire

## Tech Stack

- **PHP** 8.4.15
- **Laravel** 12.x
- **Filament** 5.x
- **Livewire** 4.x
- **Spatie Permission** - Role and permission management
- **PHPUnit** 11.x - Testing framework
- **Laravel Pint** - Code formatting

## Architecture

### Panel Structure

The application uses two separate Filament panels with distinct purposes:

#### Admin Panel (`/admin`)
- **Purpose**: Global system administration
- **Tenancy**: No tenant scoping
- **Access**: Super Admin and Admin roles only
- **Features**:
  - User management
  - Role management
  - Organization (Tenant) management
  - Global settings

#### App Panel (`/app/{tenant}`)
- **Purpose**: Tenant-scoped application features
- **Tenancy**: Fully tenant-aware
- **Access**: All authenticated users with tenant membership
- **Features**:
  - Member management (within tenant context)
  - Tenant-specific resources
  - User profile

### Plugin System

Plugins are located in the `plugins/` directory and follow Laravel package conventions:

#### IAM Plugin (`plugins/iam`)
The Identity and Access Management plugin provides:

- **Authentication**: User login and registration
- **Multi-Tenancy**: Organization/tenant management
- **Role Management**: Tenant-level roles (Owner, Admin, Member, Viewer)
- **Invitations**: Email-based member invitations
- **Permissions**: Fine-grained access control

**Key Components**:
- `InteractsWithIam` trait - Adds tenancy and IAM features to User model
- `TenantInvitationService` - Handles member invitations and role management
- Filament Resources for Admin panel (Users, Roles, Tenants)
- Filament Resources for App panel (Members)

## Getting Started

### Prerequisites

- PHP >= 8.4
- Composer
- Node.js & PNPM
- MySQL/PostgreSQL

### Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd modular
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node dependencies:
```bash
pnpm install
```

4. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

5. Configure database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=modular
DB_USERNAME=root
DB_PASSWORD=
```

6. Run migrations and seeders:
```bash
php artisan migrate:fresh --seed
```

7. Build assets:
```bash
pnpm dev
```

8. Start development server:
```bash
php artisan serve
```

### Default Access

After seeding, you can access:

- **Admin Panel**: `http://localhost:8000/admin`
- **App Panel**: `http://localhost:8000/app`

Default credentials will be created by the seeder.

## Development

### Code Formatting

This project uses Laravel Pint for code formatting:

```bash
# Format all files
vendor/bin/pint

# Format specific plugin
vendor/bin/pint plugins/iam

# Check formatting without fixing
vendor/bin/pint --test
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run with coverage
php artisan test --coverage
```

### Clearing Cache

```bash
# Clear all caches
php artisan optimize:clear

# Clear specific cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Multi-Tenancy

### Tenant Roles

The IAM plugin defines four tenant-level roles:

- **Owner** - Full control over the organization
- **Admin** - Can manage members and settings
- **Member** - Standard access to tenant resources
- **Viewer** - Read-only access

### Managing Tenants

**In Admin Panel** (`/admin/tenants`):
- Create new organizations
- Edit organization details
- Manage all organization members
- Delete organizations

**In App Panel** (`/app/{tenant}/members`):
- Invite new members (Owner/Admin only)
- Update member roles (Owner/Admin only)
- Remove members (Owner/Admin only)
- View member list

### Permission Checks

The `InteractsWithIam` trait provides helper methods:

```php
// Check if user can manage members in a tenant
$user->canManageMembersInTenant($tenant);

// Check if user is owner of a tenant
$user->isOwnerOfTenant($tenant);

// Get user's role in a tenant
$role = $user->getRoleInTenant($tenant);

// Check specific role
$user->hasRoleInTenant($tenant, TenantRole::ADMIN->value);
```

## Project Structure

```
.
├── app/                          # Core application
│   ├── Models/
│   │   └── User.php             # User model with IAM traits
│   └── Providers/
├── plugins/                      # Modular plugins
│   └── iam/                     # IAM plugin
│       ├── config/              # Plugin configuration
│       ├── database/            # Migrations, seeders, factories
│       ├── resources/           # Views, translations
│       ├── routes/              # Plugin routes
│       ├── src/
│       │   ├── Contracts/       # Interfaces
│       │   ├── Enums/           # TenantRole enum
│       │   ├── Filament/        # Filament resources
│       │   ├── Models/          # Tenant, TenantInvitation
│       │   ├── Services/        # Business logic
│       │   └── Traits/          # InteractsWithIam
│       └── tests/               # Plugin tests
├── bootstrap/
│   └── app.php                  # Panel configuration
├── config/
│   └── app-modules.php          # Plugin registration
└── tests/                        # Application tests
```

## Key Files

- [bootstrap/app.php](bootstrap/app.php) - Panel and middleware configuration
- [config/app-modules.php](config/app-modules.php) - Plugin registration and discovery
- [plugins/iam/src/Providers/IamServiceProvider.php](plugins/iam/src/Providers/IamServiceProvider.php) - IAM plugin service provider
- [plugins/iam/src/Traits/InteractsWithIam.php](plugins/iam/src/Traits/InteractsWithIam.php) - Core IAM functionality
- [plugins/iam/src/Services/TenantInvitationService.php](plugins/iam/src/Services/TenantInvitationService.php) - Member invitation logic

## Contributing

When contributing to this project:

1. Follow existing code conventions and structure
2. Run `vendor/bin/pint` before committing
3. Ensure all tests pass: `php artisan test`
4. Update documentation for new features
5. Check sibling files for patterns before creating new ones

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
