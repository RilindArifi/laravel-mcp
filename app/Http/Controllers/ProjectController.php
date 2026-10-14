<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Projects\ProjectStoreRequest;
use App\Http\Requests\Projects\ProjectUpdateRequest;
use App\Models\Label;
use App\Models\Project;
use App\Models\Task;
use App\Support\TaskFlowPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Show a paginated list of the user's projects.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $projects = $request->user()->projects()
            ->withTaskCounts()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Project $project): array => TaskFlowPayload::project($project));

        return Inertia::render('projects/Index', [
            'projects' => $projects,
            'filters' => ['status' => $status, 'search' => $search],
            'statusOptions' => ProjectStatus::options(),
        ]);
    }

    /**
     * Show the form for creating a project.
     */
    public function create(): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('projects/Create', [
            'statusOptions' => ProjectStatus::options(),
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(ProjectStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = $request->user()->projects()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Show a single project with its tasks.
     */
    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $project->loadCount([
            'tasks',
            'tasks as open_tasks_count' => fn ($query) => $query->whereIn('status', TaskStatus::openValues()),
            'tasks as completed_tasks_count' => fn ($query) => $query->where('status', TaskStatus::Done),
        ]);

        return Inertia::render('projects/Show', [
            'project' => TaskFlowPayload::project($project),
            'tasks' => $project->tasks()
                ->with('labels')
                ->orderByRaw("case status when 'in_progress' then 0 when 'blocked' then 1 when 'todo' then 2 else 3 end")
                ->orderByRaw('due_date is null, due_date')
                ->get()
                ->map(fn (Task $task): array => TaskFlowPayload::task($task))
                ->all(),
            'labels' => $request->user()->labels()->orderBy('name')->get()
                ->map(fn (Label $label): array => TaskFlowPayload::label($label))
                ->all(),
            'statusOptions' => TaskStatus::options(),
            'priorityOptions' => TaskPriority::options(),
        ]);
    }

    /**
     * Show the form for editing a project.
     */
    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        return Inertia::render('projects/Edit', [
            'project' => TaskFlowPayload::project($project),
            'statusOptions' => ProjectStatus::options(),
        ]);
    }

    /**
     * Update the given project.
     */
    public function update(ProjectUpdateRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Delete the given project and its tasks.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted.')]);

        return to_route('projects.index');
    }
}
