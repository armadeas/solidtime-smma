<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\UnlockRequest;

use App\Http\Requests\V1\BaseFormRequest;

class UnlockRequestIndexRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string>>
     */
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string 'true'/'false' to boolean before validation
        if ($this->has('my_requests')) {
            $this->merge([
                'my_requests' => filter_var($this->input('my_requests'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        if ($this->has('pending_approvals')) {
            $this->merge([
                'pending_approvals' => filter_var($this->input('pending_approvals'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'string',
                'in:pending,approved,rejected,expired',
            ],
            'project_id' => [
                'sometimes',
                'string',
                'uuid',
            ],
            'my_requests' => [
                'sometimes',
                'boolean',
            ],
            'pending_approvals' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}

