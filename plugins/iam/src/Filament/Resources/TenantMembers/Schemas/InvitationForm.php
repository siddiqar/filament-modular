<?php

namespace Sekeco\Iam\Filament\Resources\TenantMembers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Sekeco\Iam\Enums\TenantRole;

class InvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Invite New Member')
                    ->description('Send an invitation to a new member to join your organization.')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(
                                table: config('iam.tenant.invitations_table', 'tenant_invitations'),
                                column: 'email',
                                modifyRuleUsing: function ($rule, $livewire) {
                                    $tenant = $livewire->tenant ?? \Filament\Facades\Filament::getTenant();

                                    return $rule->where('tenant_id', $tenant->id)
                                        ->whereNull('accepted_at')
                                        ->whereNull('rejected_at');
                                },
                                ignoreRecord: true
                            )
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
}
