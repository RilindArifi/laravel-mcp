<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Policies\TaskPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property CarbonImmutable|null $due_date
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project $project
 * @property-read Collection<int, Label> $labels
 */
#[Fillable(['title', 'description', 'status', 'priority', 'due_date'])]
#[UsePolicy(TaskPolicy::class)]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the project the task belongs to.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the labels attached to the task.
     *
     * @return BelongsToMany<Label, $this>
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }

    /**
     * Scope the query to tasks that are not done yet.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn($query->qualifyColumn('status'), TaskStatus::openValues());
    }

    /**
     * Scope the query to unfinished tasks whose due date has passed.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->whereIn($query->qualifyColumn('status'), TaskStatus::openValues())
            ->whereNotNull($query->qualifyColumn('due_date'))
            ->whereDate($query->qualifyColumn('due_date'), '<', today());
    }

    /**
     * Determine if the task is unfinished and past its due date.
     */
    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->due_date !== null
            && $this->due_date->isBefore(today());
    }

    /**
     * Move the task to the given status, keeping the completion timestamp in sync.
     */
    public function moveTo(TaskStatus $status): void
    {
        $this->status = $status;
        $this->completed_at = $status === TaskStatus::Done ? now() : null;

        $this->save();
    }
}
