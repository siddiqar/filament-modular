<?php

namespace Sekeco\Iam\Filament\Resources\Tenants\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Sekeco\Iam\Enums\TenantRole;
use Sekeco\Iam\Services\TenantInvitationService;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Members';

    protected static ?string $modelLabel = 'member';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Invite New Member')
                    ->description('Send an invitation to a new member to join this organization.')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->helperText('The email address of the person you want to invite.'),

                        Select::make('role')
                            ->label('Role')
                            ->options(TenantRole::class)
                            ->default(TenantRole::MEMBER->value)
                            ->required()
                            ->helperText(function ($state) {
                                if (! $state) {
                                    return null;
                                }

                                $role = TenantRole::tryFrom($state);

                                return $role ? $role->description() : null;
                            }),
                    ]),
            ]);
    }

    public function table(Table $table): Table
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
            ])
            ->headerActions([
                Action::make('invite')
                    ->label('Invite Member')
                    ->icon(Heroicon::PaperAirplane)
                    ->form([
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->helperText('The email address of the person you want to invite.'),

                        Select::make('role')
                            ->label('Role')
                            ->options(TenantRole::class)
                            ->default(TenantRole::MEMBER->value)
                            ->required()
                            ->helperText(function ($state) {
                                if (! $state) {
                                    return null;
                                }

                                $role = TenantRole::tryFrom($state);

                                return $role ? $role->description() : null;
                            }),
                    ])
                    ->action(function (array $data): void {
                        try {
                            app(TenantInvitationService::class)->invite(
                                $this->getOwnerRecord(),
                                $data['email'],
                                TenantRole::from($data['role'])
                            );

                            Notification::make()
                                ->title('Invitation sent')
                                ->body("An invitation has been sent to {$data['email']}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to send invitation')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(function () {
                        /** @phpstan-ignore-next-line */
                        $currentUser = auth()->user();
                        $tenant = $this->getOwnerRecord();

                        return $currentUser && $currentUser->canManageMembersInTenant($tenant);
                    }),
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
                        ->action(function ($record, array $data): void {
                            try {
                                app(TenantInvitationService::class)->updateMemberRole(
                                    $this->getOwnerRecord(),
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
                        ->visible(function ($record) {
                            /** @phpstan-ignore-next-line */
                            $currentUser = auth()->user();
                            $tenant = $this->getOwnerRecord();

                            return $currentUser && $currentUser->canManageMembersInTenant($tenant);
                        }),

                    DeleteAction::make('remove')
                        ->label('Remove Member')
                        ->icon(Heroicon::Trash)
                        ->modalHeading('Remove Member')
                        ->modalDescription('Are you sure you want to remove this member from the organization?')
                        ->action(function ($record): void {
                            try {
                                app(TenantInvitationService::class)->removeMember(
                                    $this->getOwnerRecord(),
                                    $record
                                );

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
                        ->visible(function ($record) {
                            /** @phpstan-ignore-next-line */
                            $currentUser = auth()->user();

                            // Don't allow removing self
                            if ($currentUser && $currentUser->id === $record->id) {
                                return false;
                            }

                            $tenant = $this->getOwnerRecord();

                            return $currentUser && $currentUser->canManageMembersInTenant($tenant);
                        }),
                ]),
            ]);
    }
}
