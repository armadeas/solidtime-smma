<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\UnlockRequest;

use App\Http\Resources\V1\BaseResource;
use App\Models\UnlockRequest;
use Illuminate\Http\Request;

/**
 * @property UnlockRequest $resource
 */
class UnlockRequestResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID of unlock request */
            'id' => $this->resource->id,
            /** @var string $organization_id ID of organization */
            'organization_id' => $this->resource->organization_id,
            /** @var string $project_id ID of project */
            'project_id' => $this->resource->project_id,
            /** @var array|null $project Project details */
            'project' => $this->whenLoaded('project', function () {
                $projectData = [
                    'id' => $this->resource->project->id,
                    'name' => $this->resource->project->name,
                    'color' => $this->resource->project->color,
                ];

                // Add client if loaded
                if ($this->resource->project->relationLoaded('client')) {
                    $projectData['client'] = $this->resource->project->client ? [
                        'id' => $this->resource->project->client->id,
                        'name' => $this->resource->project->client->name,
                    ] : null;
                }

                return $projectData;
            }),
            /** @var string $requester_member_id ID of requester member */
            'requester_member_id' => $this->resource->requester_member_id,
            /** @var array|null $requester Requester details */
            'requester' => $this->whenLoaded('requester', function () {
                return [
                    'id' => $this->resource->requester->id,
                    'user' => $this->resource->requester->user ? [
                        'id' => $this->resource->requester->user->id,
                        'name' => $this->resource->requester->user->name,
                        'email' => $this->resource->requester->user->email,
                    ] : null,
                ];
            }),
            /** @var string|null $approver_member_id ID of approver member */
            'approver_member_id' => $this->resource->approver_member_id,
            /** @var array|null $approver Approver details */
            'approver' => $this->whenLoaded('approver', function () {
                if ($this->resource->approver) {
                    return [
                        'id' => $this->resource->approver->id,
                        'user' => $this->resource->approver->user ? [
                            'id' => $this->resource->approver->user->id,
                            'name' => $this->resource->approver->user->name,
                            'email' => $this->resource->approver->user->email,
                        ] : null,
                    ];
                }
                return null;
            }),
            /** @var string|null $reason Reason for unlock request */
            'reason' => $this->resource->reason,
            /** @var string $status Status of unlock request (pending, approved, rejected, expired) */
            'status' => $this->resource->status->value,
            /** @var string|null $approved_at Timestamp when request was approved */
            'approved_at' => $this->resource->approved_at?->toISOString(),
            /** @var string|null $rejected_at Timestamp when request was rejected */
            'rejected_at' => $this->resource->rejected_at?->toISOString(),
            /** @var string|null $expires_at Timestamp when unlock expires */
            'expires_at' => $this->resource->expires_at?->toISOString(),
            /** @var bool $is_active Whether the unlock is currently active */
            'is_active' => $this->resource->isActive(),
            /** @var string $created_at Timestamp when request was created */
            'created_at' => $this->resource->created_at->toISOString(),
            /** @var string $updated_at Timestamp when request was last updated */
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }
}

