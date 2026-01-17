<?php

namespace Sekeco\Iam\Filament\Resources\TenantMembers;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Sekeco\Iam\Filament\Resources\TenantMembers\Pages\InviteMember;
use Sekeco\Iam\Filament\Resources\TenantMembers\Pages\ListMembers;
use Sekeco\Iam\Filament\Resources\TenantMembers\Schemas\InvitationForm;
use Sekeco\Iam\Filament\Resources\TenantMembers\Tables\MembersTable;
use UnitEnum;

class TenantMemberResource extends Resource
{
    protected static ?string $model = null; // We don't use a model directly, we use the User model via tenant relationship

    protected static bool $isScopedToTenant = false; // This resource manages members, not scoped to tenant

    protected static string|UnitEnum|null $navigationGroup = 'IAM';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'Member';

    protected static ?string $pluralModelLabel = 'Members';

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
            'invite' => InviteMember::route('/invite'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Only show this resource if tenancy is enabled
        return config('iam.tenant.enabled', false);
    }
}
