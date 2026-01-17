<?php

namespace Sekeco\Iam\Filament\Resources\TenantMembers\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Sekeco\Iam\Filament\Resources\TenantMembers\TenantMemberResource;

class ListMembers extends ListRecords
{
    protected static string $resource = TenantMemberResource::class;

    protected function getHeaderActions(): array
    {
        // Admin panel version - no invite action
        // Inviting should be done through TenantResource relation manager
        return [];
    }

    protected function getTableQuery(): Builder
    {
        $userModel = config('iam.user_model', \App\Models\User::class);

        // Admin panel: Show ALL users who are members of ANY tenant
        // This gives super admins visibility into all tenant memberships
        return $userModel::query()
            ->whereHas('tenants')
            ->with('tenants'); // Eager load tenants to show which tenants each user belongs to
    }
}
