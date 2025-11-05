<?php

namespace App\Filament\Resources\UnlockRequestResource\Pages;

use App\Filament\Resources\UnlockRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnlockRequests extends ListRecords
{
    protected static string $resource = UnlockRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
