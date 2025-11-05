<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\UnlockRequest;

use App\Http\Requests\V1\BaseFormRequest;

class UnlockRequestUpdateRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            // No fields to update for now
        ];
    }
}

