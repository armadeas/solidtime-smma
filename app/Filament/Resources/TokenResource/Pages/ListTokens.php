<?php

declare(strict_types=1);

namespace App\Filament\Resources\TokenResource\Pages;

use App\Filament\Resources\TokenResource;
use Filament\Resources\Pages\ListRecords;

class ListTokens extends ListRecords
{
    protected static string $resource = TokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
