<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\UnlockRequest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @template TValue
 */
class UnlockRequestCollection extends ResourceCollection
{
    /**
     * @var class-string<UnlockRequestResource>
     */
    public $collects = UnlockRequestResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}

