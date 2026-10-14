<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ListChecks, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import TaskRow from '@/components/TaskRow.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import projectRoutes from '@/routes/projects';
import taskRoutes from '@/routes/tasks';
import type { Paginated, ProjectOption, SelectOption, Task } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tasks', href: taskRoutes.index() }],
    },
});

const props = defineProps<{
    tasks: Paginated<Task>;
    filters: {
        project_id: number | null;
        status: string | null;
        priority: string | null;
        overdue: boolean;
        search: string | null;
    };
    projects: ProjectOption[];
    statusOptions: SelectOption[];
    priorityOptions: SelectOption[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'all');
const priority = ref(props.filters.priority ?? 'all');
const project = ref(
    props.filters.project_id === null
        ? 'all'
        : String(props.filters.project_id),
);
const overdue = ref(props.filters.overdue);

const applyFilters = () => {
    router.get(
        taskRoutes.index().url,
        {
            search: search.value === '' ? undefined : search.value,
            status: status.value === 'all' ? undefined : status.value,
            priority: priority.value === 'all' ? undefined : priority.value,
            project_id: project.value === 'all' ? undefined : project.value,
            overdue: overdue.value ? 1 : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch([status, priority, project, overdue], applyFilters);

const openProject = (task: Task) => {
    router.get(projectRoutes.show(task.project_id).url);
};
</script>

<template>
    <Head title="Tasks" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold">Tasks</h1>
            <p class="text-sm text-muted-foreground">
                Every task across your projects.
            </p>
        </div>

        <form
            class="flex flex-wrap items-center gap-3"
            @submit.prevent="applyFilters"
        >
            <div class="relative w-full sm:w-64">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search tasks"
                    aria-label="Search tasks"
                />
            </div>

            <Select v-model="project">
                <SelectTrigger class="w-48" aria-label="Filter by project">
                    <SelectValue placeholder="All projects" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All projects</SelectItem>
                    <SelectItem
                        v-for="option in projects"
                        :key="option.id"
                        :value="String(option.id)"
                    >
                        {{ option.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="status">
                <SelectTrigger class="w-40" aria-label="Filter by status">
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem
                        v-for="option in statusOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="priority">
                <SelectTrigger class="w-40" aria-label="Filter by priority">
                    <SelectValue placeholder="All priorities" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All priorities</SelectItem>
                    <SelectItem
                        v-for="option in priorityOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="overdue"
                    type="checkbox"
                    class="size-4 rounded border-input accent-primary"
                />
                Overdue only
            </label>

            <Button type="submit" variant="outline">Apply</Button>
        </form>

        <EmptyState
            v-if="tasks.data.length === 0"
            title="No tasks found"
            description="Try clearing the filters."
            :icon="ListChecks"
        />

        <Card v-else class="overflow-hidden p-0">
            <CardContent class="p-0">
                <div class="divide-y">
                    <TaskRow
                        v-for="task in tasks.data"
                        :key="task.id"
                        :task="task"
                        :status-options="statusOptions"
                        show-project
                        @edit="openProject"
                    />
                </div>
            </CardContent>
        </Card>

        <Pagination
            :links="tasks.links"
            :from="tasks.from"
            :to="tasks.to"
            :total="tasks.total"
        />
    </div>
</template>
