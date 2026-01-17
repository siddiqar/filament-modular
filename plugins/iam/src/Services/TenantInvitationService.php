<?php

namespace Sekeco\Iam\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Sekeco\Iam\Enums\TenantRole;
use Sekeco\Iam\Models\Tenant;
use Sekeco\Iam\Models\TenantInvitation;

class TenantInvitationService
{
    /**
     * Invite a user to a tenant.
     */
    public function invite(
        Tenant $tenant,
        string $email,
        TenantRole|string $role = TenantRole::MEMBER,
        $invitedBy = null
    ): TenantInvitation {
        /** @phpstan-ignore-next-line */
        $invitedBy = $invitedBy ?? Auth::id();
        $roleValue = $role instanceof TenantRole ? $role->value : $role;

        // Check if user is already a member
        $userModel = config('iam.user_model', \App\Models\User::class);
        $existingUser = $userModel::where('email', $email)->first();

        if ($existingUser && $tenant->users()->where('users.id', $existingUser->id)->exists()) {
            throw new \Exception('User is already a member of this tenant.');
        }

        // Check for existing pending invitation
        $existingInvitation = TenantInvitation::where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->pending()
            ->first();

        if ($existingInvitation) {
            // Update existing invitation
            $existingInvitation->update([
                'role' => $roleValue,
                'invited_by' => $invitedBy,
                'token' => TenantInvitation::generateToken(),
                'expires_at' => now()->addDays(7),
            ]);

            return $existingInvitation;
        }

        // Create new invitation
        $invitation = TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'invited_by' => $invitedBy,
            'email' => $email,
            'role' => $roleValue,
            'token' => TenantInvitation::generateToken(),
            'expires_at' => now()->addDays(7),
        ]);

        // TODO: Send invitation email
        // Mail::to($email)->send(new TenantInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Accept an invitation.
     */
    public function accept(string $token, $user = null): bool
    {
        /** @phpstan-ignore-next-line */
        $user = $user ?? Auth::user();

        if (! $user) {
            throw new \Exception('User must be authenticated to accept invitation.');
        }

        $invitation = TenantInvitation::where('token', $token)
            ->pending()
            ->firstOrFail();

        if ($invitation->email !== $user->email) {
            throw new \Exception('This invitation is not for your email address.');
        }

        return DB::transaction(function () use ($invitation, $user) {
            // Add user to tenant
            $invitation->tenant->users()->attach($user->id, [
                'role' => $invitation->role,
                'invited_by' => $invitation->invited_by,
                'invited_at' => $invitation->created_at,
                'joined_at' => now(),
            ]);

            // Mark invitation as accepted
            $invitation->update([
                'accepted_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Reject an invitation.
     */
    public function reject(string $token, $user = null): bool
    {
        /** @phpstan-ignore-next-line */
        $user = $user ?? Auth::user();

        $invitation = TenantInvitation::where('token', $token)
            ->pending()
            ->firstOrFail();

        if ($user && $invitation->email !== $user->email) {
            throw new \Exception('This invitation is not for your email address.');
        }

        $invitation->update([
            'rejected_at' => now(),
        ]);

        return true;
    }

    /**
     * Update member role in a tenant.
     */
    public function updateMemberRole(Tenant $tenant, $user, TenantRole|string $newRole): bool
    {
        $userId = is_object($user) ? $user->id : $user;
        $roleValue = $newRole instanceof TenantRole ? $newRole->value : $newRole;

        // Don't allow changing the last owner
        $currentRole = $tenant->getUserRole($userId);
        if ($currentRole === TenantRole::OWNER->value && $roleValue !== TenantRole::OWNER->value) {
            $ownersCount = $tenant->owners()->count();
            if ($ownersCount <= 1) {
                throw new \Exception('Cannot change role of the last owner. Please assign another owner first.');
            }
        }

        $tenant->users()->updateExistingPivot($userId, [
            'role' => $roleValue,
        ]);

        return true;
    }

    /**
     * Remove a member from a tenant.
     */
    public function removeMember(Tenant $tenant, $user): bool
    {
        $userId = is_object($user) ? $user->id : $user;

        // Don't allow removing the last owner
        $currentRole = $tenant->getUserRole($userId);
        if ($currentRole === TenantRole::OWNER->value) {
            $ownersCount = $tenant->owners()->count();
            if ($ownersCount <= 1) {
                throw new \Exception('Cannot remove the last owner from the tenant.');
            }
        }

        $tenant->users()->detach($userId);

        return true;
    }

    /**
     * Cancel a pending invitation.
     */
    public function cancelInvitation(TenantInvitation $invitation): bool
    {
        if (! $invitation->isPending()) {
            throw new \Exception('Can only cancel pending invitations.');
        }

        $invitation->delete();

        return true;
    }

    /**
     * Cleanup expired invitations.
     */
    public function cleanupExpiredInvitations(): int
    {
        return TenantInvitation::expired()->delete();
    }
}
