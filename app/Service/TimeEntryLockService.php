<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\UnlockRequestStatus;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\UnlockRequest;
use Illuminate\Support\Carbon;

class TimeEntryLockService
{
    /**
     * Check if a time entry is locked based on organization settings
     */
    public function isTimeEntryLocked(TimeEntry $timeEntry, Organization $organization): bool
    {
        // If no lock days configured, nothing is locked
        if ($organization->time_entry_lock_days === null) {
            return false;
        }

        $lockDate = now()->subDays($organization->time_entry_lock_days)->startOfDay();
        $entryDate = $timeEntry->start->startOfDay();

        return $entryDate->lessThan($lockDate);
    }

    /**
     * Check if a date is locked based on organization settings
     */
    public function isDateLocked(Carbon $date, Organization $organization): bool
    {
        // If no lock days configured, nothing is locked
        if ($organization->time_entry_lock_days === null) {
            return false;
        }

        $lockDate = now()->subDays($organization->time_entry_lock_days)->startOfDay();
        $checkDate = $date->copy()->startOfDay();

        return $checkDate->lessThan($lockDate);
    }

    /**
     * Check if user has active unlock request for a project
     */
    public function hasActiveUnlock(Member $member, Project $project): bool
    {
        return UnlockRequest::where('organization_id', $project->organization_id)
            ->where('project_id', $project->id)
            ->where('requester_member_id', $member->id)
            ->where('status', UnlockRequestStatus::Approved)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get active unlock request for a project
     */
    public function getActiveUnlock(Member $member, Project $project): ?UnlockRequest
    {
        return UnlockRequest::where('organization_id', $project->organization_id)
            ->where('project_id', $project->id)
            ->where('requester_member_id', $member->id)
            ->where('status', UnlockRequestStatus::Approved)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Set unlock request context for current request
     * This will be used to inject unlock_request_id into audit logs
     */
    public function setUnlockContext(?string $unlockRequestId): void
    {
        if ($unlockRequestId) {
            request()->merge(['_unlock_request_id' => $unlockRequestId]);
        }
    }

    /**
     * Get unlock request ID from current request context
     */
    public function getUnlockContext(): ?string
    {
        return request()->get('_unlock_request_id');
    }

    /**
     * Check if user can modify a time entry
     * 
     * @param array|null $newData New data untuk detect project change (optional)
     */
    public function canModifyTimeEntry(TimeEntry $timeEntry, Member $member, ?array $newData = null): bool
    {
        $organization = $timeEntry->organization;

        // If entry is not locked, allow modification
        if (! $this->isTimeEntryLocked($timeEntry, $organization)) {
            return true;
        }

        // If locked, check for active unlock request for OLD project
        if ($timeEntry->project_id) {
            $hasOldProjectUnlock = $this->hasActiveUnlock($member, $timeEntry->project);
            
            if (! $hasOldProjectUnlock) {
                return false;
            }
            
            // If project is being changed, also check unlock for NEW project
            if ($newData && isset($newData['project_id']) && $newData['project_id'] !== $timeEntry->project_id) {
                $newProject = Project::find($newData['project_id']);
                
                if ($newProject && $this->isTimeEntryLocked($timeEntry, $organization)) {
                    return $this->hasActiveUnlock($member, $newProject);
                }
            }
            
            return true;
        }

        return false;
    }

    /**
     * Check if user can create/modify time entry on a specific date
     */
    public function canModifyDate(Carbon $date, Organization $organization, Member $member, ?Project $project = null): bool
    {
        // If date is not locked, allow modification
        if (! $this->isDateLocked($date, $organization)) {
            return true;
        }

        // If locked and project is specified, check for active unlock request
        if ($project !== null) {
            return $this->hasActiveUnlock($member, $project);
        }

        return false;
    }

    /**
     * Get the lock cutoff date for an organization
     */
    public function getLockCutoffDate(Organization $organization): ?Carbon
    {
        if ($organization->time_entry_lock_days === null) {
            return null;
        }

        return now()->subDays($organization->time_entry_lock_days)->startOfDay();
    }

    /**
     * Create an unlock request
     */
    public function createUnlockRequest(
        Organization $organization,
        Project $project,
        Member $requester,
        ?string $reason = null
    ): UnlockRequest {
        return UnlockRequest::create([
            'organization_id' => $organization->id,
            'project_id' => $project->id,
            'requester_member_id' => $requester->id,
            'reason' => $reason,
            'status' => UnlockRequestStatus::Pending,
        ]);
    }

    /**
     * Check if member is a manager of the project
     */
    public function isProjectManager(Member $member, Project $project): bool
    {
        // Owner and Admin can manage all projects
        if (in_array($member->role, ['owner', 'admin'], true)) {
            return true;
        }

        // Manager role can only manage projects they are members of
        if ($member->role === 'manager') {
            return $project->members()
                ->where('member_id', $member->id)
                ->exists();
        }

        return false;
    }
}

