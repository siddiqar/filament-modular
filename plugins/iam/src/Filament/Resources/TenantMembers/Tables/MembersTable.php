<?php

namespace Sekeco\Iam\Filament\Resources\TenantMembers\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Sekeco\Iam\Enums\TenantRole;
use Sekeco\Iam\Services\TenantInvitationService;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => TenantRole::from($state)->label())
                    ->color(fn(string $state): string => match (TenantRole::from($state)) {
                        TenantRole::OWNER => 'danger',
                        TenantRole::ADMIN => 'warning',
                        TenantRole::MEMBER => 'success',
                        TenantRole::VIEWER => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('pivot.joined_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('pivot.invited_by')
                    ->label('Invited By')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $state) {
                            return 'N/A';
                        }
                        $userModel = config('iam.user_model', \App\Models\User::class);
                        $inviter = $userModel::find($state);

                        return $inviter ? $inviter->name : 'Unknown';
                    })
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('updateRole')
                        ->label('Update Role')
                        ->icon(Heroicon::PencilSquare)
                        ->form([
                            Select::make('role')
                                ->label('Role')
                                ->options(TenantRole::class)
                                ->required()
                                ->default(fn($record) => $record->pivot->role),
                        ])
                        ->action(function ($record, array $data, $livewire): void {
                            $tenant = $livewire->tenant ?? \Filament\Facades\Filament::getTenant();

                            try {
                                app(TenantInvitationService::class)->updateMemberRole(
                                    $tenant,
                                    $record,
                                    TenantRole::from($data['role'])
                                );

                                Notification::make()
                                    ->title('Role updated successfully')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Failed to update role')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(function ($record, $livewire) {
                            $tenant = $livewire->tenant ?? \Filament\Facades\Filament::getTenant();
                            /** @phpstan-ignore-next-line */
                            $currentUser = auth()->user();

                            return $currentUser && $currentUser->canManageMembersInTenant($tenant);
                        }),

                    DeleteAction::make('remove')
                        ->label('Remove Member')
                        ->icon(Heroicon::Trash)
                        ->modalHeading('Remove Member')
                        ->modalDescription('Are you sure you want to remove this member from the organization?')
                        ->action(function ($record, $livewire): void {
                            $tenant = $livewire->tenant ?? \Filament\Facades\Filament::getTenant();

                            try {
                                app(TenantInvitationService::class)->removeMember($tenant, $record);

                                Notification::make()
                                    ->title('Member removed successfully')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Failed to remove member')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(function ($record, $livewire) {
                            $tenant = $livewire->tenant ?? \Filament\Facades\Filament::getTenant();
                            /** @phpstan-ignore-next-line */
                            $currentUser = auth()->user();

                            // Don't allow removing self
                            if ($currentUser && $currentUser->id === $record->id) {
                                return false;
                            }

                            return $currentUser && $currentUser->canManageMembersInTenant($tenant);
                        }),
                ]),
            ])
            ->bulkActions([
                // Could add bulk actions here if needed
            ]);
    }
}
