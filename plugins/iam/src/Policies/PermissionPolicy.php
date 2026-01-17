<?php

namespace Sekeco\Iam\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_permission');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('view_permission');
    }

    public function create(User $user): bool
    {
        return $user->can('create_permission');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can('update_permission');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('delete_permission');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_permission');
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
        return $user->can('force_delete_permission');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_permission');
    }

    public function restore(User $user, Permission $permission): bool
    {
        return $user->can('restore_permission');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_permission');
    }

    public function replicate(User $user, Permission $permission): bool
    {
        return $user->can('replicate_permission');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_permission');
    }
}
