<?php

namespace Sekeco\Iam\Filament\Resources\TenantMembers\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Sekeco\Iam\Filament\Resources\TenantMembers\TenantMemberResource;

class ListMembers extends ListRecords
{
    protected static string $resource = TenantMemberResource::class;

    protected function getHeaderActions(): array
    {
        /** @phpstan-ignore-next-line */
        $currentUser = auth()->user();
        $tenant = $this->tenant ?? \Filament\Facades\Filament::getTenant();

        $canInvite = $currentUser && $tenant && $currentUser->canManageMembersInTenant($tenant);

        return [
            Action::make('invite')
                ->label('Invite Member')
                ->icon(Heroicon::PaperAirplane)
                ->url(fn(): string => static::getResource()::getUrl('invite', ['tenant' => $tenant]))
                ->visible($canInvite),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $tenant = $this->tenant ?? \Filament\Facades\Filament::getTenant();

        if (! $tenant) {
            $userModel = config('iam.user_model', \App\Models\User::class);

            return $userModel::query()->whereRaw('1 = 0'); // Return empty query
        }

        // Get all users that belong to this tenant
        return $tenant->users()->getQuery();
    }
}
