<?php

namespace App\Filament\Resources\UnlockRequestResource\Pages;

use App\Filament\Resources\UnlockRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUnlockRequest extends ViewRecord
{
    protected static string $resource = UnlockRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status->value === 'pending')
                ->action(function () {
                    $member = $this->record->organization->members()
                        ->where('user_id', auth()->id())
                        ->first();

                    if ($member) {
                        $this->record->approve($member);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Unlock request approved')
                            ->body('The unlock request has been approved and will expire in 30 minutes.')
                            ->send();
                    }

                    return redirect()->to(UnlockRequestResource::getUrl('index'));
                })
                ->authorize('update', $this->record),
            Actions\Action::make('reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status->value === 'pending')
                ->action(function () {
                    $member = $this->record->organization->members()
                        ->where('user_id', auth()->id())
                        ->first();

                    if ($member) {
                        $this->record->reject($member);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Unlock request rejected')
                            ->send();
                    }

                    return redirect()->to(UnlockRequestResource::getUrl('index'));
                })
                ->authorize('update', $this->record),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status->value === 'pending'),
        ];
    }
}
