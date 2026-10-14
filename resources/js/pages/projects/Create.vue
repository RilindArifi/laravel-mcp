<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import ProjectController from '@/actions/App/Http/Controllers/ProjectController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import type { SelectOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Projects', href: projectRoutes.index() },
            { title: 'New project', href: projectRoutes.create() },
        ],
    },
});

defineProps<{
    statusOptions: SelectOption[];
}>();

const status = ref('active');
</script>

<template>
    <Head title="New project" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold">New project</h1>
            <p class="text-sm text-muted-foreground">
                Projects group the tasks you and your agents work on.
            </p>
        </div>

        <Card class="max-w-2xl">
            <CardContent>
                <Form
                    v-bind="ProjectController.store.form()"
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
                            autofocus
                            placeholder="Website Redesign"
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
                            placeholder="What is this project about?"
                        ></textarea>
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
                            <Input id="due_date" type="date" name="due_date" />
                            <InputError :message="errors.due_date" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="processing">
                            Create project
                        </Button>
                        <Button as-child type="button" variant="ghost">
                            <Link :href="projectRoutes.index()">Cancel</Link>
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
