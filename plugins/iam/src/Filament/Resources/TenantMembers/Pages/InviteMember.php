<?php

namespace Sekeco\Iam\Filament\Resources\TenantMembers\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Sekeco\Iam\Enums\TenantRole;
use Sekeco\Iam\Filament\Resources\TenantMembers\TenantMemberResource;
use Sekeco\Iam\Services\TenantInvitationService;

class InviteMember extends CreateRecord
{
    protected static string $resource = TenantMemberResource::class;

    protected static ?string $title = 'Invite Member';

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index', ['tenant' => $this->tenant ?? \Filament\Facades\Filament::getTenant()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Invitation sent successfully';
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $tenant = $this->tenant ?? \Filament\Facades\Filament::getTenant();

        try {
            $invitation = app(TenantInvitationService::class)->invite(
                $tenant,
                $data['email'],
                TenantRole::from($data['role'])
            );

            Notification::make()
                ->title('Invitation sent')
                ->body("An invitation has been sent to {$data['email']}")
                ->success()
                ->send();

            return $invitation;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send invitation')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();

            // Create a dummy model to satisfy return type
            $invitationModel = config('iam.tenant.invitations_model', \Sekeco\Iam\Models\TenantInvitation::class);

            return new $invitationModel;
        }
    }

    public function getBreadcrumb(): string
    {
        return 'Invite';
    }
}
