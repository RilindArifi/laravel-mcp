<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ListChecks, Pencil, Plus } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import TaskFormDialog from '@/components/TaskFormDialog.vue';
import TaskRow from '@/components/TaskRow.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import projectRoutes from '@/routes/projects';
import type { LabelItem, Project, SelectOption, Task } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: projectRoutes.index() }],
    },
});

const props = defineProps<{
    project: Project;
    tasks: Task[];
    labels: LabelItem[];
    statusOptions: SelectOption[];
    priorityOptions: SelectOption[];
}>();

const dialogOpen = ref(false);
const editing = ref<Task | null>(null);

const openCreate = () => {
    editing.value = null;
    dialogOpen.value = true;
};

const openEdit = (task: Task) => {
    editing.value = task;
    dialogOpen.value = true;
};
</script>

<template>
    <Head :title="project.name" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-semibold">{{ project.name }}</h1>
                    <Badge variant="outline">{{ project.status_label }}</Badge>
                </div>
                <p
                    v-if="project.description"
                    class="max-w-2xl text-sm text-muted-foreground"
                >
                    {{ project.description }}
                </p>
                <p
                    v-if="project.due_date"
                    class="text-sm text-muted-foreground"
                >
                    Due {{ project.due_date }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Button as-child variant="outline">
                    <Link :href="projectRoutes.edit(project.id)">
                        <Pencil class="size-4" />
                        Edit
                    </Link>
                </Button>
                <Button @click="openCreate">
                    <Plus class="size-4" />
                    New task
                </Button>
            </div>
        </div>

        <Card>
            <CardContent class="space-y-3">
                <div
                    class="flex items-center justify-between text-sm text-muted-foreground"
                >
                    <span>
                        {{ project.completed_tasks_count ?? 0 }} of
                        {{ project.tasks_count ?? 0 }} tasks done
                    </span>
                    <span class="tabular-nums">{{ project.progress }}%</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary"
                        :style="{ width: `${project.progress}%` }"
                    />
                </div>
            </CardContent>
        </Card>

        <EmptyState
            v-if="tasks.length === 0"
            title="No tasks yet"
            description="Add the first task, or let an agent create them over MCP."
            :icon="ListChecks"
        >
            <Button size="sm" @click="openCreate">New task</Button>
        </EmptyState>

        <Card v-else class="overflow-hidden p-0">
            <CardContent class="p-0">
                <div class="divide-y">
                    <TaskRow
                        v-for="task in tasks"
                        :key="task.id"
                        :task="task"
                        :status-options="statusOptions"
                        @edit="openEdit"
                    />
                </div>
            </CardContent>
        </Card>

        <TaskFormDialog
            v-model:open="dialogOpen"
            :project-id="project.id"
            :task="editing"
            :labels="labels"
            :status-options="statusOptions"
            :priority-options="priorityOptions"
        />
    </div>
</template>
