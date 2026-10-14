<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Policies\ProjectPolicy;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property ProjectStatus $status
 * @property CarbonImmutable|null $due_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read int|null $open_tasks_count
 * @property-read int|null $completed_tasks_count
 */
#[Fillable(['name', 'description', 'status', 'due_date'])]
#[UsePolicy(ProjectPolicy::class)]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'due_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the project.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tasks that belong to the project.
     *
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope the query to projects that are still being worked on.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), ProjectStatus::Active);
    }

    /**
     * Eager load the task counts used by the dashboard and MCP server.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withTaskCounts(Builder $query): void
    {
        $query->withCount([
            'tasks',
            'tasks as open_tasks_count' => fn (Builder $query): Builder => $query->whereIn($query->qualifyColumn('status'), TaskStatus::openValues()),
            'tasks as completed_tasks_count' => fn (Builder $query): Builder => $query->where($query->qualifyColumn('status'), TaskStatus::Done),
        ]);
    }

    /**
     * Get the percentage of tasks that are done.
     */
    public function progress(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $this->completed_tasks_count ?? $this->tasks()->where('status', TaskStatus::Done)->count();

        return (int) round($completed / $total * 100);
    }
}
