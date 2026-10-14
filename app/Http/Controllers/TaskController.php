<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Tasks\TaskIndexRequest;
use App\Http\Requests\Tasks\TaskStatusRequest;
use App\Http\Requests\Tasks\TaskStoreRequest;
use App\Http\Requests\Tasks\TaskUpdateRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskFlowPayload;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Show every task across the user's projects.
     */
    public function index(TaskIndexRequest $request): Response
    {
        $filters = $request->validated();
        $user = $request->user();

        $tasks = $user->tasks()
            ->with(['project:id,name', 'labels'])
            ->when($filters['project_id'] ?? null, fn ($query, $projectId) => $query->where('tasks.project_id', $projectId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('tasks.status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('tasks.priority', $priority))
            ->when($filters['overdue'] ?? false, fn ($query) => $query->overdue())
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('tasks.title', 'like', "%{$search}%"))
            ->orderByRaw('tasks.due_date is null, tasks.due_date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Task $task): array => TaskFlowPayload::task($task, withProject: true));

        return Inertia::render('tasks/Index', [
            'tasks' => $tasks,
            'filters' => [
                'project_id' => $filters['project_id'] ?? null,
                'status' => $filters['status'] ?? null,
                'priority' => $filters['priority'] ?? null,
                'overdue' => (bool) ($filters['overdue'] ?? false),
                'search' => $filters['search'] ?? null,
            ],
            'projects' => $user->projects()->orderBy('name')->get(['id', 'name'])->all(),
            'statusOptions' => TaskStatus::options(),
            'priorityOptions' => TaskPriority::options(),
        ]);
    }

    /**
     * Store a new task on the given project.
     */
    public function store(TaskStoreRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $task = $project->tasks()->create($request->safe()->except('labels'));

        $this->syncLabels($task, $request->user(), $request->validated('labels', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return back();
    }

    /**
     * Update the given task.
     */
    public function update(TaskUpdateRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update($request->safe()->except('labels'));

        if ($request->has('labels')) {
            $this->syncLabels($task, $request->user(), $request->validated('labels', []));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return back();
    }

    /**
     * Move the given task to another status.
     */
    public function status(TaskStatusRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->moveTo(TaskStatus::from($request->validated('status')));

        return back();
    }

    /**
     * Delete the given task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted.')]);

        return back();
    }

    /**
     * Attach the given label names to the task, creating the user's labels as needed.
     *
     * @param  array<int, string>  $names
     */
    protected function syncLabels(Task $task, User $user, array $names): void
    {
        $ids = [];

        foreach ($names as $name) {
            $ids[] = $user->labels()->firstOrCreate(['name' => $name])->id;
        }

        $task->labels()->sync($ids);
    }
}
