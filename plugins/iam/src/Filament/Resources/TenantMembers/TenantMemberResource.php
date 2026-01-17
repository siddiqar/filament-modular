<?php

namespace Sekeco\Iam\Filament\Resources\TenantMembers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Sekeco\Iam\Filament\Resources\TenantMembers\Pages\ListMembers;
use Sekeco\Iam\Filament\Resources\TenantMembers\Schemas\InvitationForm;
use Sekeco\Iam\Filament\Resources\TenantMembers\Tables\MembersTable;
use UnitEnum;

class TenantMemberResource extends Resource
{
    protected static ?string $model = null; // We don't use a model directly, we use the User model via tenant relationship

    // Admin panel resource - shows ALL tenant members across ALL tenants
    // Super admins can see and manage members from any tenant
    protected static bool $isScopedToTenant = false;

    protected static string|UnitEnum|null $navigationGroup = 'IAM';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'All Tenant Members';

    protected static ?string $modelLabel = 'Tenant Member';

    protected static ?string $pluralModelLabel = 'Tenant Members';

    public static function form(Schema $schema): Schema
    {
        return InvitationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            // No invite page in admin panel - use TenantResource relation manager instead
        ];
    }

    public static function canViewAny(): bool
    {
        // Only show this resource if tenancy is enabled
        return config('iam.tenant.enabled', false);
    }
}
