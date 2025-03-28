<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Client;

use App\Http\Resources\V1\BaseResource;
use App\Models\Client;
use Illuminate\Http\Request;

/**
 * @property Client $resource
 */
class ClientResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var string $id ID */
            'id' => $this->resource->id,
            /** @var string $name Name */
            'name' => $this->resource->name,
            /** @var string $email Email */
            'email' => $this->resource->email,
            /** @var string $phone Phone */
            'phone' => $this->resource->phone,
            /** @var string $taxNumber Tax Number */
            'taxNumber' => $this->resource->taxNumber,
            /** @var string $address Address */
            'address' => $this->resource->address,
            /** @var string $postal_code Postal Code */
            'postal_code' => $this->resource->postal_code,
            /** @var string $city City */
            'city' => $this->resource->city,
            /** @var string $country Country */
            'country' => $this->resource->country,
            /** @var bool $is_archived Whether the client is archived */
            'is_archived' => $this->resource->is_archived,
            /** @var string $created_at When the tag was created */
            'created_at' => $this->formatDateTime($this->resource->created_at),
            /** @var string $updated_at When the tag was last updated */
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
