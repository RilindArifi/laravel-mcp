<?php

namespace App\Concerns;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait TaskValidationRules
{
    /**
     * Get the validation rules used for creating and updating tasks.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function taskRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
            'labels' => ['sometimes', 'array', 'max:5'],
            'labels.*' => ['string', 'max:30'],
        ];
    }

    /**
     * Get the validation rules used when filtering task lists.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function taskFilterRules(): array
    {
        return [
            'project_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'overdue' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * Get the validation rules for a project id owned by the given user.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function ownedProjectRules(int $userId): array
    {
        return [
            'required',
            'integer',
            Rule::exists('projects', 'id')->where('user_id', $userId),
        ];
    }
}
