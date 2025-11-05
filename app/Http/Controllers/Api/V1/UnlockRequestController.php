<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UnlockRequestStatus;
use App\Http\Requests\V1\UnlockRequest\UnlockRequestIndexRequest;
use App\Http\Requests\V1\UnlockRequest\UnlockRequestStoreRequest;
use App\Http\Requests\V1\UnlockRequest\UnlockRequestUpdateRequest;
use App\Http\Resources\V1\UnlockRequest\UnlockRequestCollection;
use App\Http\Resources\V1\UnlockRequest\UnlockRequestResource;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\UnlockRequest;
use App\Service\PermissionStore;
use App\Service\TimeEntryLockService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UnlockRequestController extends Controller
{
    public function __construct(
        PermissionStore $permissionStore,
        private readonly TimeEntryLockService $lockService
    ) {
        parent::__construct($permissionStore);
    }

    protected function checkPermission(Organization $organization, string $permission, ?UnlockRequest $unlockRequest = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($unlockRequest !== null && $unlockRequest->organization_id !== $organization->id) {
            throw new AuthorizationException('Unlock request does not belong to organization');
        }
    }

    /**
     * Get unlock requests for the organization
     *
     * @return UnlockRequestCollection<UnlockRequestResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getUnlockRequests
     */
    public function index(Organization $organization, UnlockRequestIndexRequest $request): UnlockRequestCollection
    {
        $this->authorize('viewAny', [UnlockRequest::class, $organization]);

        $member = $this->member($organization);
        $isManager = in_array($member->role, ['owner', 'admin', 'manager'], true);

        $query = UnlockRequest::query()
            ->whereBelongsToOrganization($organization)
            ->with(['project.client', 'requester.user', 'approver.user']);

        // Filter berdasarkan parameter request
        if ($request->my_requests) {
            // User ingin melihat request mereka sendiri
            $query->where('requester_member_id', $member->id);
        } elseif ($request->pending_approvals && $isManager) {
            // Manager ingin melihat pending approvals untuk project yang mereka handle
            $projectIds = $this->getManagedProjectIds($member);
            $query->whereIn('project_id', $projectIds)
                ->where('status', UnlockRequestStatus::Pending);
        } elseif ($isManager) {
            // Manager melihat semua unlock requests untuk project yang mereka handle
            $projectIds = $this->getManagedProjectIds($member);
            $query->whereIn('project_id', $projectIds);
        } else {
            // Employee biasa hanya melihat request mereka sendiri
            $query->where('requester_member_id', $member->id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by project
        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $unlockRequests = $query->orderBy('created_at', 'desc')
            ->paginate(config('app.pagination_per_page_default', 20));

        return new UnlockRequestCollection($unlockRequests);
    }

    /**
     * Get a specific unlock request
     *
     * @throws AuthorizationException
     *
     * @operationId getUnlockRequest
     */
    public function show(Organization $organization, UnlockRequest $unlockRequest): UnlockRequestResource
    {
        // Check that unlock request belongs to organization
        if ($unlockRequest->organization_id !== $organization->id) {
            throw new AuthorizationException('Unlock request does not belong to organization');
        }

        $this->authorize('view', $unlockRequest);

        $unlockRequest->load(['project.client', 'requester.user', 'approver.user']);

        return new UnlockRequestResource($unlockRequest);
    }

    /**
     * Create a new unlock request
     *
     * @throws AuthorizationException
     *
     * @operationId createUnlockRequest
     */
    public function store(Organization $organization, UnlockRequestStoreRequest $request): UnlockRequestResource
    {
        $this->authorize('create', [UnlockRequest::class, $organization]);

        $project = Project::findOrFail($request->project_id);

        if ($project->organization_id !== $organization->id) {
            throw new AuthorizationException('Project does not belong to organization');
        }

        $member = $this->member($organization);

        // Check if there's already a pending or active request
        $existingRequest = UnlockRequest::where('organization_id', $organization->id)
            ->where('project_id', $project->id)
            ->where('requester_member_id', $member->id)
            ->where(function ($query) {
                $query->where('status', UnlockRequestStatus::Pending)
                    ->orWhere(function ($q) {
                        $q->where('status', UnlockRequestStatus::Approved)
                            ->where('expires_at', '>', now());
                    });
            })
            ->first();

        if ($existingRequest) {
            abort(422, 'You already have an active or pending unlock request for this project');
        }

        $unlockRequest = $this->lockService->createUnlockRequest(
            $organization,
            $project,
            $member,
            $request->reason
        );

        $unlockRequest->load(['project', 'requester.user']);

        return new UnlockRequestResource($unlockRequest);
    }

    /**
     * Approve an unlock request
     *
     * @throws AuthorizationException
     *
     * @operationId approveUnlockRequest
     */
    public function approve(Organization $organization, UnlockRequest $unlockRequest): UnlockRequestResource
    {
        // Check that unlock request belongs to organization
        if ($unlockRequest->organization_id !== $organization->id) {
            throw new AuthorizationException('Unlock request does not belong to organization');
        }

        $this->authorize('approve', $unlockRequest);

        if ($unlockRequest->status !== UnlockRequestStatus::Pending) {
            abort(422, 'Only pending requests can be approved');
        }

        $member = $this->member($organization);
        $unlockRequest->approve($member);

        $unlockRequest->load(['project.client', 'requester.user', 'approver.user']);

        return new UnlockRequestResource($unlockRequest);
    }

    /**
     * Reject an unlock request
     *
     * @throws AuthorizationException
     *
     * @operationId rejectUnlockRequest
     */
    public function reject(Organization $organization, UnlockRequest $unlockRequest): UnlockRequestResource
    {
        // Check that unlock request belongs to organization
        if ($unlockRequest->organization_id !== $organization->id) {
            throw new AuthorizationException('Unlock request does not belong to organization');
        }

        $this->authorize('reject', $unlockRequest);

        if ($unlockRequest->status !== UnlockRequestStatus::Pending) {
            abort(422, 'Only pending requests can be rejected');
        }

        $member = $this->member($organization);
        $unlockRequest->reject($member);

        $unlockRequest->load(['project.client', 'requester.user', 'approver.user']);

        return new UnlockRequestResource($unlockRequest);
    }

    /**
     * Delete an unlock request
     *
     * @throws AuthorizationException
     *
     * @operationId deleteUnlockRequest
     */
    public function destroy(Organization $organization, UnlockRequest $unlockRequest): Response
    {
        // Check that unlock request belongs to organization
        if ($unlockRequest->organization_id !== $organization->id) {
            throw new AuthorizationException('Unlock request does not belong to organization');
        }

        $this->authorize('delete', $unlockRequest);

        $unlockRequest->delete();

        return response()->noContent();
    }

    /**
     * Get project IDs managed by the member
     */
    private function getManagedProjectIds(Member $member): array
    {
        // Owner dan Admin bisa manage semua project di organization
        if (in_array($member->role, ['owner', 'admin'], true)) {
            return Project::where('organization_id', $member->organization_id)
                ->pluck('id')
                ->toArray();
        }

        // Manager hanya bisa manage project dimana mereka terdaftar sebagai project member
        // dengan role manager atau project dimana mereka adalah member
        if ($member->role === 'manager') {
            return \App\Models\ProjectMember::where('member_id', $member->id)
                ->pluck('project_id')
                ->toArray();
        }

        return [];
    }
}

