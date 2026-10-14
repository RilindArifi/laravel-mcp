<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarClock,
    CircleCheck,
    FolderKanban,
    ListChecks,
    TriangleAlert,
} from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import LabelBadge from '@/components/LabelBadge.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import projects from '@/routes/projects';
import tasks from '@/routes/tasks';
import type { Project, Task, WorkspaceStats } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<{
    stats: WorkspaceStats;
    statusBreakdown: Record<string, number>;
    dueSoon: Task[];
    recentProjects: Project[];
}>();

const totalTasks = computed(() =>
    Object.values(props.statusBreakdown).reduce(
        (total, count) => total + count,
        0,
    ),
);

const segments = computed(() => [
    { key: 'done', label: 'Done', class: 'bg-emerald-500' },
    { key: 'in_progress', label: 'In progress', class: 'bg-sky-500' },
    { key: 'blocked', label: 'Blocked', class: 'bg-rose-500' },
    { key: 'todo', label: 'To do', class: 'bg-muted-foreground/40' },
]);

const share = (key: string): number => {
    if (totalTasks.value === 0) {
        return 0;
    }

    return Math.round(
        ((props.statusBreakdown[key] ?? 0) / totalTasks.value) * 100,
    );
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                label="Active projects"
                :value="stats.active_projects"
                :icon="FolderKanban"
            />
            <StatCard
                label="Open tasks"
                :value="stats.open_tasks"
                :icon="ListChecks"
            />
            <StatCard
                label="Overdue"
                :value="stats.overdue_tasks"
                :icon="TriangleAlert"
                :emphasis="stats.overdue_tasks > 0"
                hint="Unfinished and past the due date"
            />
            <StatCard
                label="Completed this week"
                :value="stats.completed_this_week"
                :icon="CircleCheck"
            />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Due in the next 7 days</CardTitle>
                    <CardDescription>
                        Unfinished tasks across every project, soonest first.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="dueSoon.length === 0"
                        title="Nothing due this week"
                        description="Enjoy it, or pull something forward from the backlog."
                        :icon="CalendarClock"
                    >
                        <Button as-child variant="outline" size="sm">
                            <Link :href="tasks.index()">Browse all tasks</Link>
                        </Button>
                    </EmptyState>

                    <ul v-else class="divide-y">
                        <li
                            v-for="task in dueSoon"
                            :key="task.id"
                            class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0 space-y-1">
                                <Link
                                    :href="projects.show(task.project_id)"
                                    class="block truncate font-medium hover:underline"
                                >
                                    {{ task.title }}
                                </Link>
                                <div
                                    class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <span v-if="task.project">{{
                                        task.project.name
                                    }}</span>
                                    <LabelBadge
                                        v-for="label in task.labels"
                                        :key="label.id"
                                        :label="label"
                                    />
                                </div>
                            </div>
                            <span
                                class="shrink-0 text-sm"
                                :class="
                                    task.is_overdue
                                        ? 'font-medium text-destructive'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ task.due_date }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Task mix</CardTitle>
                    <CardDescription>
                        {{ totalTasks }} tasks in total.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            v-for="segment in segments"
                            :key="segment.key"
                            :class="segment.class"
                            :style="{ width: `${share(segment.key)}%` }"
                        />
                    </div>

                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="segment in segments"
                            :key="segment.key"
                            class="flex items-center justify-between gap-2"
                        >
                            <span class="flex items-center gap-2">
                                <span
                                    class="size-2 rounded-full"
                                    :class="segment.class"
                                />
                                {{ segment.label }}
                            </span>
                            <span class="text-muted-foreground tabular-nums">
                                {{ statusBreakdown[segment.key] ?? 0 }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Recently updated projects</CardTitle>
                <CardDescription>
                    Progress is the share of tasks marked done.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <EmptyState
                    v-if="recentProjects.length === 0"
                    title="No projects yet"
                    description="Create your first project, or ask an AI agent to do it over MCP."
                    :icon="FolderKanban"
                >
                    <Button as-child size="sm">
                        <Link :href="projects.create()">New project</Link>
                    </Button>
                </EmptyState>

                <div v-else class="grid gap-4 sm:grid-cols-2">
                    <Link
                        v-for="project in recentProjects"
                        :key="project.id"
                        :href="projects.show(project.id)"
                        class="space-y-3 rounded-xl border p-4 transition-colors hover:bg-accent/50"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <p class="truncate font-medium">
                                {{ project.name }}
                            </p>
                            <span class="text-xs text-muted-foreground">
                                {{ project.status_label }}
                            </span>
                        </div>

                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{ width: `${project.progress}%` }"
                            />
                        </div>

                        <p class="text-xs text-muted-foreground">
                            {{ project.completed_tasks_count ?? 0 }} of
                            {{ project.tasks_count ?? 0 }} tasks done ·
                            {{ project.progress }}%
                        </p>
                    </Link>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
