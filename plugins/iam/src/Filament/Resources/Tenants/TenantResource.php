<?php

namespace Sekeco\Iam\Filament\Resources\Tenants;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Sekeco\Iam\Filament\Resources\Tenants\Pages\CreateTenant;
use Sekeco\Iam\Filament\Resources\Tenants\Pages\EditTenant;
use Sekeco\Iam\Filament\Resources\Tenants\Pages\ListTenants;
use Sekeco\Iam\Filament\Resources\Tenants\RelationManagers\MembersRelationManager;
use Sekeco\Iam\Filament\Resources\Tenants\Schemas\TenantForm;
use Sekeco\Iam\Filament\Resources\Tenants\Tables\TenantsTable;
use Sekeco\Iam\Models\Tenant;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|UnitEnum|null $navigationGroup = 'IAM';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return config('iam.tenant.display_name', 'Organization');
    }

    public static function getPluralLabel(): string
    {
        $label = config('iam.tenant.display_name', 'Organization');

        // Simple pluralization
        return str_ends_with($label, 'y')
            ? substr($label, 0, -1).'ies'
            : $label.'s';
    }

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }

    /**
     * Disable this resource if tenancy is not enabled.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('iam.tenant.enabled', false);
    }
}
