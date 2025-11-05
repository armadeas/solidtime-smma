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

            if (! $project || ! $this->lockService->hasActiveUnlock($member, $project)) {
                return $this->lockedResponse($organization);
            }
        }

        return $next($request);
    }

    private function handleUpdateTimeEntry(Request $request, TimeEntry $timeEntry, Organization $organization, Member $member, Closure $next): Response
    {
        // Check if the time entry can be modified
        if (! $this->lockService->canModifyTimeEntry($timeEntry, $member)) {
            return $this->lockedResponse($organization);
        }

        // If updating the start time, check the new date
        if ($request->has('start')) {
            $newStartDate = \Illuminate\Support\Carbon::parse($request->input('start'));

            if ($this->lockService->isDateLocked($newStartDate, $organization)) {
                $project = $timeEntry->project;

                if (! $project || ! $this->lockService->hasActiveUnlock($member, $project)) {
                    return $this->lockedResponse($organization);
                }
            }
        }

        return $next($request);
    }

    private function handleDeleteTimeEntry(Request $request, TimeEntry $timeEntry, Organization $organization, Member $member, Closure $next): Response
    {
        if (! $this->lockService->canModifyTimeEntry($timeEntry, $member)) {
            return $this->lockedResponse($organization);
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
}

