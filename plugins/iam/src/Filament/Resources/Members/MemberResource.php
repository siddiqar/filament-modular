<?php

declare(strict_types=1);

namespace Sekeco\Iam\Filament\Resources\Members;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Sekeco\Iam\Enums\TenantRole;
use Sekeco\Iam\Filament\Resources\Members\Pages\ListMembers;
use Sekeco\Iam\Services\TenantInvitationService;

class MemberResource extends Resource
{
    protected static ?string $model = \App\Models\User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'Member';

    protected static ?int $navigationSort = 3;

    // User model has 'tenants' relationship (plural), not 'tenant' (singular)
    protected static ?string $tenantOwnershipRelationshipName = 'tenants';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Jika dalam konteks tenant (panel App), filter by tenant
                if ($tenant = \Filament\Facades\Filament::getTenant()) {
                    $query->whereHas('tenants', function (Builder $q) use ($tenant) {
                        $q->where('tenants.id', $tenant->id);
                    });
                }

                // Eager load relationships
                $query->with(['tenants']);
            })
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->getStateUsing(function ($record) {
                        if ($tenant = \Filament\Facades\Filament::getTenant()) {
                            $membership = $record->tenants()
                                ->where('tenants.id', $tenant->id)
                                ->first();

                            return $membership?->pivot->role ?? TenantRole::VIEWER->value;
                        }

                        return null;
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'danger',
                        'admin' => 'warning',
                        'member' => 'success',
                        default => 'gray',
                    })
                    ->sortable(false),

                TextColumn::make('joined_at')
                    ->label('Joined')
                    ->getStateUsing(function ($record) {
                        if ($tenant = \Filament\Facades\Filament::getTenant()) {
                            $membership = $record->tenants()
                                ->where('tenants.id', $tenant->id)
                                ->first();

                            return $membership?->pivot->joined_at;
                        }

                        return null;
                    })
                    ->dateTime()
                    ->sortable(false),
            ])
            ->recordActions([
                Action::make('updateRole')
                    ->label('Update Role')
                    ->icon(Heroicon::PencilSquare)
                    ->form([
                        Select::make('role')
                            ->label('Role')
                            ->options(TenantRole::class)
                            ->required()
                            ->default(function ($record) {
                                if ($tenant = \Filament\Facades\Filament::getTenant()) {
                                    $membership = $record->tenants()
                                        ->where('tenants.id', $tenant->id)
                                        ->first();

                                    return $membership?->pivot->role;
                                }

                                return TenantRole::VIEWER->value;
                            }),
                    ])
                    ->action(function (array $data, $record): void {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $user = Auth::user();

                        if (! $tenant || ! $user) {
                            return;
                        }

                        // Check if user has permission to manage members (owner or admin)
                        $membership = $user->tenants()->where('tenants.id', $tenant->id)->first();
                        if (! $membership || ! in_array($membership->pivot->role, [TenantRole::OWNER->value, TenantRole::ADMIN->value])) {
                            return;
                        }

                        $service = app(TenantInvitationService::class);
                        $service->updateMemberRole(
                            $tenant,
                            $record,
                            TenantRole::from($data['role'])
                        );
                    })
                    ->visible(function (): bool {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $user = Auth::user();

                        if (! $tenant || ! $user) {
                            return false;
                        }

                        // Check if user has permission to manage members (owner or admin)
                        $membership = $user->tenants()->where('tenants.id', $tenant->id)->first();

                        return $membership && in_array($membership->pivot->role, [TenantRole::OWNER->value, TenantRole::ADMIN->value]);
                    }),

                Action::make('remove')
                    ->label('Remove')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Member')
                    ->modalDescription('Are you sure you want to remove this member from the organization?')
                    ->action(function ($record): void {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $user = Auth::user();

                        if (! $tenant || ! $user) {
                            return;
                        }

                        // Check if user has permission to manage members (owner or admin)
                        $membership = $user->tenants()->where('tenants.id', $tenant->id)->first();
                        if (! $membership || ! in_array($membership->pivot->role, [TenantRole::OWNER->value, TenantRole::ADMIN->value])) {
                            return;
                        }

                        $service = app(TenantInvitationService::class);
                        $service->removeMember($tenant, $record);
                    })
                    ->visible(function ($record): bool {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $user = Auth::user();

                        if (! $tenant || ! $user) {
                            return false;
                        }

                        // Check if user has permission to manage members (owner or admin)
                        $membership = $user->tenants()->where('tenants.id', $tenant->id)->first();
                        if (! $membership || ! in_array($membership->pivot->role, [TenantRole::OWNER->value, TenantRole::ADMIN->value])) {
                            return false;
                        }

                        // Cannot remove self
                        return $record->id !== $user->id;
                    }),
            ])
            ->headerActions([
                Action::make('invite')
                    ->label('Invite Member')
                    ->icon(Heroicon::PlusCircle)
                    ->form([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->rules([
                                fn () => function (string $attribute, $value, \Closure $fail) {
                                    $tenant = \Filament\Facades\Filament::getTenant();

                                    if (! $tenant) {
                                        return;
                                    }

                                    $user = \App\Models\User::where('email', $value)->first();

                                    if (! $user) {
                                        return;
                                    }

                                    $isMember = $user->tenants()
                                        ->where('tenants.id', $tenant->id)
                                        ->exists();

                                    if ($isMember) {
                                        $fail('This user is already a member of this organization.');
                                    }
                                },
                            ]),

                        Select::make('role')
                            ->label('Role')
                            ->options(TenantRole::class)
                            ->default(TenantRole::MEMBER)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $user = Auth::user();

                        if (! $tenant || ! $user) {
                            return;
                        }

                        $service = app(TenantInvitationService::class);
                        $service->invite(
                            $tenant,
                            $data['email'],
                            TenantRole::from($data['role']),
                            $user->id
                        );
                    })
                    ->visible(function (): bool {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $user = Auth::user();

                        if (! $tenant || ! $user) {
                            return false;
                        }

                        // Check if user has permission to manage members (owner or admin)
                        $membership = $user->tenants()->where('tenants.id', $tenant->id)->first();

                        return $membership && in_array($membership->pivot->role, [TenantRole::OWNER->value, TenantRole::ADMIN->value]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        // MemberResource ONLY untuk App panel (multitenant)
        // Jangan register di Admin panel (global)
        $currentPanel = \Filament\Facades\Filament::getCurrentPanel();

        if (! $currentPanel) {
            return false;
        }

        // Hanya izinkan di App panel
        return $currentPanel->getId() === config('iam.panel.app_id', 'app');
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show in navigation if in App panel AND has tenant context
        $currentPanel = \Filament\Facades\Filament::getCurrentPanel();

        if (! $currentPanel || $currentPanel->getId() !== config('iam.panel.app_id', 'app')) {
            return false;
        }

        return \Filament\Facades\Filament::getTenant() !== null;
    }
}
