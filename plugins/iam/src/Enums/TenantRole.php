<?php

namespace Sekeco\Iam\Enums;

enum TenantRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin',
            self::MEMBER => 'Member',
            self::VIEWER => 'Viewer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OWNER => 'Full access to all tenant features including member management and deletion',
            self::ADMIN => 'Manage tenant settings and invite members',
            self::MEMBER => 'Standard access to tenant resources',
            self::VIEWER => 'Read-only access to tenant resources',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::OWNER => [
                'tenant.view',
                'tenant.update',
                'tenant.delete',
                'tenant.members.view',
                'tenant.members.invite',
                'tenant.members.update',
                'tenant.members.remove',
            ],
            self::ADMIN => [
                'tenant.view',
                'tenant.update',
                'tenant.members.view',
                'tenant.members.invite',
                'tenant.members.update',
            ],
            self::MEMBER => [
                'tenant.view',
                'tenant.members.view',
            ],
            self::VIEWER => [
                'tenant.view',
            ],
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_column(self::cases(), 'name')
        );
    }

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->toArray();
    }

    public function canInviteMembers(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN]);
    }

    public function canDeleteTenant(): bool
    {
        return $this === self::OWNER;
    }
}
