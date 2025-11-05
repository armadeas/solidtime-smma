<?php

namespace App\Policies;

use App\Models\UnlockRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UnlockRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All users can view unlock requests list
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UnlockRequest $unlockRequest): bool
    {
        // Requester or project managers can view
        $member = $unlockRequest->organization->members()->where('user_id', $user->id)->first();

        if (! $member) {
            return false;
        }

        // Requester can view their own request
        if ($unlockRequest->requester_member_id === $member->id) {
            return true;
        }

        // Project managers can view requests for their projects
        return $this->isProjectManager($member, $unlockRequest->project);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All members can create unlock requests
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UnlockRequest $unlockRequest): bool
    {
        // Only project managers can approve/reject (update)
        $member = $unlockRequest->organization->members()->where('user_id', $user->id)->first();

        if (! $member) {
            return false;
        }

        return $this->isProjectManager($member, $unlockRequest->project);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UnlockRequest $unlockRequest): bool
    {
        // Only requester can delete their own pending request
        $member = $unlockRequest->organization->members()->where('user_id', $user->id)->first();

        if (! $member) {
            return false;
        }

        return $unlockRequest->requester_member_id === $member->id
            && $unlockRequest->status->value === 'pending';
    }

    /**
     * Determine whether the user can approve the unlock request.
     */
    public function approve(User $user, UnlockRequest $unlockRequest): bool
    {
        $member = $unlockRequest->organization->members()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return false;
        }

        // Load project if not loaded
        if (! $unlockRequest->relationLoaded('project')) {
            $unlockRequest->load('project');
        }

        return $this->isProjectManager($member, $unlockRequest->project);
    }

    /**
     * Determine whether the user can reject the unlock request.
     */
    public function reject(User $user, UnlockRequest $unlockRequest): bool
    {
        $member = $unlockRequest->organization->members()
            ->where('user_id', $user->id)
            ->first();

        if (! $member) {
            return false;
        }

        // Load project if not loaded
        if (! $unlockRequest->relationLoaded('project')) {
            $unlockRequest->load('project');
        }

        return $this->isProjectManager($member, $unlockRequest->project);
    }

    /**
     * Check if member is a project manager
     */
    private function isProjectManager(\App\Models\Member $member, \App\Models\Project $project): bool
    {
        // Owner and Admin can manage all projects
        if (in_array($member->role, ['owner', 'admin'])) {
            return true;
        }

        // Manager role bisa manage project jika mereka adalah member dari project tersebut
        if ($member->role === 'manager') {
            $projectMember = $project->members()
                ->where('member_id', $member->id)
                ->exists();

            return $projectMember;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UnlockRequest $unlockRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UnlockRequest $unlockRequest): bool
    {
        return false;
    }
}
