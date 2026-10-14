<?php

namespace App\Concerns;

use App\Enums\ProjectStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProjectValidationRules
{
    /**
     * Get the validation rules used for creating and updating projects.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function projectRules(int $userId, ?int $ignoreId = null): array
    {
        return [
            'name' => $this->projectNameRules($userId, $ignoreId),
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Get the validation rules for a project name, unique per owner.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function projectNameRules(int $userId, ?int $ignoreId = null): array
    {
        return [
            'required',
            'string',
            'max:120',
            Rule::unique('projects', 'name')->where('user_id', $userId)->ignore($ignoreId),
        ];
    }
}
