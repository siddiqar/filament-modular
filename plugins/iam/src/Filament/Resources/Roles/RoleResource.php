<?php

namespace Sekeco\Iam\Filament\Resources\Roles;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Sekeco\Iam\Filament\Resources\Roles\Pages\CreateRole;
use Sekeco\Iam\Filament\Resources\Roles\Pages\EditRole;
use Sekeco\Iam\Filament\Resources\Roles\Pages\ListRoles;
use Sekeco\Iam\Filament\Resources\Roles\Schemas\RoleForm;
use Sekeco\Iam\Filament\Resources\Roles\Tables\RolesTable;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string | UnitEnum | null $navigationGroup = 'IAM';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
