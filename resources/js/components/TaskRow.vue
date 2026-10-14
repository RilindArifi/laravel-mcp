<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Ellipsis, Pencil, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import LabelBadge from '@/components/LabelBadge.vue';
import TaskPriorityBadge from '@/components/TaskPriorityBadge.vue';
import TaskStatusBadge from '@/components/TaskStatusBadge.vue';
import { destroy, status } from '@/routes/tasks';
import type { SelectOption, Task } from '@/types';

const props = defineProps<{
    task: Task;
    statusOptions: SelectOption[];
    showProject?: boolean;
}>();

defineEmits<{
    edit: [task: Task];
}>();

const moveTo = (value: string) => {
    router.patch(
        status(props.task.id).url,
        { status: value },
        { preserveScroll: true },
    );
};

const toggleDone = () => {
    moveTo(props.task.status === 'done' ? 'todo' : 'done');
};

const remove = () => {
    router.delete(destroy(props.task.id).url, { preserveScroll: true });
};
</script>

<template>
    <div class="flex items-start gap-3 px-4 py-3">
        <Button
            variant="outline"
            size="icon"
            class="mt-0.5 size-6 shrink-0 rounded-full"
            :class="
                task.status === 'done'
                    ? 'border-emerald-500 text-emerald-600'
                    : 'text-transparent hover:text-muted-foreground'
            "
            :aria-label="
                task.status === 'done' ? 'Reopen task' : 'Mark task as done'
            "
            @click="toggleDone"
        >
            <Check class="size-3.5" />
        </Button>

        <div class="min-w-0 flex-1 space-y-1">
            <p
                class="truncate font-medium"
                :class="
                    task.status === 'done'
                        ? 'text-muted-foreground line-through'
                        : ''
                "
            >
                {{ task.title }}
            </p>

            <div
                class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
            >
                <span v-if="showProject && task.project">
                    {{ task.project.name }}
                </span>
                <span
                    v-if="task.due_date"
                    :class="
                        task.is_overdue ? 'font-medium text-destructive' : ''
                    "
                >
                    Due {{ task.due_date }}
                </span>
                <LabelBadge
                    v-for="label in task.labels"
                    :key="label.id"
                    :label="label"
                />
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <TaskPriorityBadge
                :priority="task.priority"
                :label="task.priority_label"
            />
            <TaskStatusBadge :status="task.status" :label="task.status_label" />

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label="Task actions"
                    >
                        <Ellipsis class="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-44">
                    <DropdownMenuLabel>Move to</DropdownMenuLabel>
                    <DropdownMenuItem
                        v-for="option in statusOptions"
                        :key="option.value"
                        :disabled="option.value === task.status"
                        @select="moveTo(option.value)"
                    >
                        {{ option.label }}
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @select="$emit('edit', task)">
                        <Pencil class="size-4" />
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuItem variant="destructive" @select="remove">
                        <Trash2 class="size-4" />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
