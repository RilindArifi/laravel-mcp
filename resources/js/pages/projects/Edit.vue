<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
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
import projectRoutes from '@/routes/projects';
import type { Project, SelectOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: projectRoutes.index() }],
    },
});

const props = defineProps<{
    project: Project;
    statusOptions: SelectOption[];
}>();

const status = ref(props.project.status as string);

const destroy = () => {
    router.delete(projectRoutes.destroy(props.project.id).url);
};
</script>

<template>
    <Head :title="`Edit ${project.name}`" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold">Edit project</h1>
            <p class="text-sm text-muted-foreground">{{ project.name }}</p>
        </div>

        <Card class="max-w-2xl">
            <CardContent>
                <Form
                    v-bind="ProjectController.update.form(project.id)"
                    class="space-y-5"
                    v-slot="{ errors, processing }"
                >
                    <input type="hidden" name="status" :value="status" />

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            :default-value="project.name"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >{{ project.description ?? '' }}</textarea>
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="status">
                                <SelectTrigger id="status">
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
                            <Label for="due_date">Due date</Label>
                            <Input
                                id="due_date"
                                type="date"
                                name="due_date"
                                :default-value="project.due_date ?? ''"
                            />
                            <InputError :message="errors.due_date" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="processing">
                            Save changes
                        </Button>
                        <Button as-child type="button" variant="ghost">
                            <Link :href="projectRoutes.show(project.id)">
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <Card class="max-w-2xl border-destructive/40">
            <CardHeader>
                <CardTitle class="text-base">Delete project</CardTitle>
                <CardDescription>
                    Deleting a project also deletes its tasks. This cannot be
                    undone.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button variant="destructive">Delete project</Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>
                                Delete {{ project.name }}?
                            </DialogTitle>
                            <DialogDescription>
                                Every task in this project is deleted with it.
                                This cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="destructive"
                                @click="destroy"
                            >
                                Delete project
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </CardContent>
        </Card>
    </div>
</template>
