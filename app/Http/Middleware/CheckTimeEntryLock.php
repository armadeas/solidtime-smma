<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Service\TimeEntryLockService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckTimeEntryLock
{
    public function __construct(
        private readonly TimeEntryLockService $lockService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Organization|null $organization */
        $organization = $request->route('organization');

        if (! $organization) {
            return $next($request);
        }

        // Get current user's member in this organization
        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        /** @var Member|null $member */
        $member = $organization->members()->where('user_id', $user->id)->first();

        if (! $member) {
            return $next($request);
        }

        // Check if time entry lock is enabled for this organization
        if ($organization->time_entry_lock_days === null) {
            return $next($request);
        }

        // Handle different request methods
        $method = $request->method();
        $routeName = $request->route()?->getName();

        // POST: Create new time entry
        if ($method === 'POST' && str_contains((string) $routeName, 'time-entries')) {
            return $this->handleCreateTimeEntry($request, $organization, $member, $next);
        }

        // PUT/PATCH: Update time entry
        if (in_array($method, ['PUT', 'PATCH'])) {
            /** @var TimeEntry|null $timeEntry */
            $timeEntry = $request->route('timeEntry');

            if ($timeEntry) {
                return $this->handleUpdateTimeEntry($request, $timeEntry, $organization, $member, $next);
            }

            // Bulk update
            if ($request->has('ids')) {
                return $this->handleBulkUpdate($request, $organization, $member, $next);
            }
        }

        // DELETE: Delete time entry
        if ($method === 'DELETE') {
            /** @var TimeEntry|null $timeEntry */
            $timeEntry = $request->route('timeEntry');

            if ($timeEntry) {
                return $this->handleDeleteTimeEntry($request, $timeEntry, $organization, $member, $next);
            }

            // Bulk delete
            if ($request->has('ids')) {
                return $this->handleBulkDelete($request, $organization, $member, $next);
            }
        }

        return $next($request);
    }

    private function handleCreateTimeEntry(Request $request, Organization $organization, Member $member, Closure $next): Response
    {
        $startDate = $request->input('start');

        if (! $startDate) {
            return $next($request);
        }

        $startDateTime = \Illuminate\Support\Carbon::parse($startDate);
        $projectId = $request->input('project_id');

        if ($this->lockService->isDateLocked($startDateTime, $organization)) {
            // Check if user has active unlock for this project
            $project = $projectId ? \App\Models\Project::find($projectId) : null;

            if (! $project) {
                return $this->lockedResponse($organization);
            }
            
            $unlock = $this->lockService->getActiveUnlock($member, $project);
            if (! $unlock) {
                return $this->lockedResponse($organization);
            }
            
            // Set unlock context for audit
            $this->lockService->setUnlockContext($unlock->id);
        }

        return $next($request);
    }

    private function handleUpdateTimeEntry(Request $request, TimeEntry $timeEntry, Organization $organization, Member $member, Closure $next): Response
    {
        $isLocked = $this->lockService->isTimeEntryLocked($timeEntry, $organization);
        $isProjectChanging = $request->has('project_id') && $request->input('project_id') !== $timeEntry->project_id;
        
        // If entry is locked and project is changing, need dual unlock
        if ($isLocked && $isProjectChanging) {
            $oldProject = $timeEntry->project;
            $newProjectId = $request->input('project_id');
            $newProject = $newProjectId ? \App\Models\Project::find($newProjectId) : null;
            
            if (! $oldProject || ! $newProject) {
                return $this->lockedResponse($organization);
            }
            
            $oldUnlock = $this->lockService->getActiveUnlock($member, $oldProject);
            $newUnlock = $this->lockService->getActiveUnlock($member, $newProject);
            
            if (! $oldUnlock || ! $newUnlock) {
                return $this->dualUnlockRequiredResponse($organization, $oldProject, $newProject, (bool) $oldUnlock, (bool) $newUnlock);
            }
            
            // Set unlock context for audit (use old project unlock by default)
            $this->lockService->setUnlockContext($oldUnlock->id);
        }
        // Normal locked entry check (not changing project)
        elseif ($isLocked) {
            if (! $this->lockService->canModifyTimeEntry($timeEntry, $member)) {
                return $this->lockedResponse($organization);
            }
            
            // Set unlock context for audit if unlock is used
            if ($timeEntry->project_id) {
                $unlock = $this->lockService->getActiveUnlock($member, $timeEntry->project);
                if ($unlock) {
                    $this->lockService->setUnlockContext($unlock->id);
                }
            }
        }

        // If updating the start time, check the new date
        if ($request->has('start')) {
            $newStartDate = \Illuminate\Support\Carbon::parse($request->input('start'));

            if ($this->lockService->isDateLocked($newStartDate, $organization)) {
                $project = $request->has('project_id') 
                    ? \App\Models\Project::find($request->input('project_id'))
                    : $timeEntry->project;

                if (! $project) {
                    return $this->lockedResponse($organization);
                }
                
                $unlock = $this->lockService->getActiveUnlock($member, $project);
                if (! $unlock) {
                    return $this->lockedResponse($organization);
                }
                
                // Set unlock context for audit
                $this->lockService->setUnlockContext($unlock->id);
            }
        }

        return $next($request);
    }

    private function handleDeleteTimeEntry(Request $request, TimeEntry $timeEntry, Organization $organization, Member $member, Closure $next): Response
    {
        if (! $this->lockService->canModifyTimeEntry($timeEntry, $member)) {
            return $this->lockedResponse($organization);
        }

        // Set unlock context for audit if unlock is used
        if ($this->lockService->isTimeEntryLocked($timeEntry, $organization) && $timeEntry->project_id) {
            $unlock = $this->lockService->getActiveUnlock($member, $timeEntry->project);
            if ($unlock) {
                $this->lockService->setUnlockContext($unlock->id);
            }
        }

        return $next($request);
    }

    private function handleBulkUpdate(Request $request, Organization $organization, Member $member, Closure $next): Response
    {
        $ids = $request->input('ids', []);

        $timeEntries = TimeEntry::whereIn('id', $ids)
            ->where('member_id', $member->id)
            ->get();

        foreach ($timeEntries as $timeEntry) {
            if (! $this->lockService->canModifyTimeEntry($timeEntry, $member)) {
                return $this->lockedResponse($organization);
            }
        }

        return $next($request);
    }

    private function handleBulkDelete(Request $request, Organization $organization, Member $member, Closure $next): Response
    {
        $ids = $request->input('ids', []);

        $timeEntries = TimeEntry::whereIn('id', $ids)
            ->where('member_id', $member->id)
            ->get();

        foreach ($timeEntries as $timeEntry) {
            if (! $this->lockService->canModifyTimeEntry($timeEntry, $member)) {
                return $this->lockedResponse($organization);
            }
        }

        return $next($request);
    }

    private function lockedResponse(Organization $organization): Response
    {
        $cutoffDate = $this->lockService->getLockCutoffDate($organization);

        return response()->json([
            'message' => 'This time entry is locked. You need to request unlock permission from a project manager.',
            'locked' => true,
            'lock_cutoff_date' => $cutoffDate?->toIso8601String(),
        ], Response::HTTP_FORBIDDEN);
    }

    private function dualUnlockRequiredResponse(
        Organization $organization, 
        \App\Models\Project $oldProject, 
        \App\Models\Project $newProject,
        bool $hasOldUnlock,
        bool $hasNewUnlock
    ): Response
    {
        $cutoffDate = $this->lockService->getLockCutoffDate($organization);
        $missingUnlocks = [];
        
        if (! $hasOldUnlock) {
            $missingUnlocks[] = [
                'project_id' => $oldProject->id,
                'project_name' => $oldProject->name,
                'reason' => 'old_project'
            ];
        }
        
        if (! $hasNewUnlock) {
            $missingUnlocks[] = [
                'project_id' => $newProject->id,
                'project_name' => $newProject->name,
                'reason' => 'new_project'
            ];
        }

        return response()->json([
            'message' => 'Changing project requires unlock permission for both old and new projects.',
            'locked' => true,
            'requires_dual_unlock' => true,
            'lock_cutoff_date' => $cutoffDate?->toIso8601String(),
            'missing_unlocks' => $missingUnlocks,
            'old_project' => [
                'id' => $oldProject->id,
                'name' => $oldProject->name,
                'has_unlock' => $hasOldUnlock,
            ],
            'new_project' => [
                'id' => $newProject->id,
                'name' => $newProject->name,
                'has_unlock' => $hasNewUnlock,
            ],
        ], Response::HTTP_FORBIDDEN);
    }
}

