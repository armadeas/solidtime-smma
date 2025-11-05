<?php

namespace App\Filament\Resources\UnlockRequestResource\Pages;

use App\Filament\Resources\UnlockRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUnlockRequest extends CreateRecord
{
    protected static string $resource = UnlockRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Get current user's organization
        $organization = auth()->user()->currentTeam;

        if ($organization) {
            // Set organization_id
            $data['organization_id'] = $organization->id;

            // Only set requester_member_id if not already set (for non-admin users)
            // Admin can choose the requester, so don't override if already set
            if (empty($data['requester_member_id'])) {
                // Get member record for current user
                $member = $organization->members()
                    ->where('user_id', auth()->id())
                    ->first();

                if ($member) {
                    $data['requester_member_id'] = $member->id;
                }
            }

            // Set default status
            $data['status'] = 'pending';
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
