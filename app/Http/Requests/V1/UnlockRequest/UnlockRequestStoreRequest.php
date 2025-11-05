<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\UnlockRequest;

use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class UnlockRequestStoreRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'string',
                'uuid',
                (new ExistsEloquent(Project::class))
                    ->query(function (Builder $builder): Builder {
                        /** @var Builder<Project> $builder */
                        return $builder->whereBelongsTo($this->organization, 'organization');
                    }),
            ],
            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}

