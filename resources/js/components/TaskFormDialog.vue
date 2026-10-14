<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { LabelItem, SelectOption, Task } from '@/types';

const props = defineProps<{
    projectId: number;
    statusOptions: SelectOption[];
    priorityOptions: SelectOption[];
    labels: LabelItem[];
    task?: Task | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const status = ref('todo');
const priority = ref('medium');

watch(
    () => props.task,
    (task) => {
        status.value = task?.status ?? 'todo';
        priority.value = task?.priority ?? 'medium';
    },
    { immediate: true },
);

const isEditing = computed(() => props.task != null);

const formProps = computed(() =>
    props.task
        ? TaskController.update.form(props.task.id)
        : TaskController.store.form(props.projectId),
);

const isChecked = (name: string): boolean =>
    (props.task?.labels ?? []).some((label) => label.name === name);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? 'Edit task' : 'New task' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Update the task details.'
                            : 'Add a task to this project.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="formProps"
                class="space-y-4"
                :options="{ preserveScroll: true }"
                @success="open = false"
                v-slot="{ errors, processing }"
            >
                <input type="hidden" name="status" :value="status" />
                <input type="hidden" name="priority" :value="priority" />

                <div class="grid gap-2">
                    <Label for="task-title">Title</Label>
                    <Input
                        id="task-title"
                        name="title"
                        :default-value="task?.title"
                        required
                        placeholder="What needs to be done?"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="task-description">Description</Label>
                    <textarea
                        id="task-description"
                        name="description"
                        rows="3"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >{{ task?.description ?? '' }}</textarea>
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="task-status">Status</Label>
                        <Select v-model="status">
                            <SelectTrigger id="task-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.status" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="task-priority">Priority</Label>
                        <Select v-model="priority">
                            <SelectTrigger id="task-priority">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in priorityOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.priority" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="task-due-date">Due date</Label>
                        <Input
                            id="task-due-date"
                            type="date"
                            name="due_date"
                            :default-value="task?.due_date ?? ''"
                        />
                        <InputError :message="errors.due_date" />
                    </div>
                </div>

                <div v-if="labels.length > 0" class="grid gap-2">
                    <Label>Labels</Label>
                    <div class="flex flex-wrap gap-3">
                        <label
                            v-for="label in labels"
                            :key="label.id"
                            class="flex items-center gap-2 text-sm"
                        >
                            <input
                                type="checkbox"
                                name="labels[]"
                                class="size-4 rounded border-input accent-primary"
                                :value="label.name"
                                :checked="isChecked(label.name)"
                            />
                            {{ label.name }}
                        </label>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        {{ isEditing ? 'Save task' : 'Create task' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
