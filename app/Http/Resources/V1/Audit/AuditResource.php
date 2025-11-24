<?php

namespace App\Http\Resources\V1\Audit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'event_label' => $this->getEventLabel(),
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'auditable_name' => $this->getAuditableName(),
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'changes' => $this->getChanges(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'unlock_request_id' => $this->unlock_request_id,
            'unlock_request' => $this->whenLoaded('unlockRequest', function () {
                return [
                    'id' => $this->unlockRequest->id,
                    'status' => $this->unlockRequest->status->value ?? null,
                    'project' => $this->unlockRequest->project->name ?? null,
                ];
            }),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'url' => $this->url,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get human-readable event label
     */
    private function getEventLabel(): string
    {
        return match ($this->event) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            default => ucfirst($this->event),
        };
    }

    /**
     * Get auditable model name
     */
    private function getAuditableName(): string
    {
        $type = class_basename($this->auditable_type);
        return preg_replace('/(?<!^)[A-Z]/', ' $0', $type);
    }

    /**
     * Get formatted changes
     */
    private function getChanges(): ?array
    {
        if (!$this->old_values && !$this->new_values) {
            return null;
        }

        $changes = [];
        $allKeys = array_unique(array_merge(
            array_keys($this->old_values ?? []),
            array_keys($this->new_values ?? [])
        ));

        foreach ($allKeys as $key) {
            $oldValue = $this->old_values[$key] ?? null;
            $newValue = $this->new_values[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
