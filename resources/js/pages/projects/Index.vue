<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderKanban, Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
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
import type { Paginated, Project, SelectOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Projects',
                href: projectRoutes.index(),
            },
        ],
    },
});

const props = defineProps<{
    projects: Paginated<Project>;
    filters: { status: string; search: string };
    statusOptions: SelectOption[];
}>();

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status === '' ? 'all' : props.filters.status);

const applyFilters = () => {
    router.get(
        projectRoutes.index().url,
        {
            search: search.value === '' ? undefined : search.value,
            status: status.value === 'all' ? undefined : status.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(status, applyFilters);
</script>

<template>
    <Head title="Projects" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">Projects</h1>
                <p class="text-sm text-muted-foreground">
                    {{ projects.total }} project(s) in your workspace.
                </p>
            </div>

            <Button as-child>
                <Link :href="projectRoutes.create()">
                    <Plus class="size-4" />
                    New project
                </Link>
            </Button>
        </div>

        <form
            class="flex flex-wrap items-center gap-3"
            @submit.prevent="applyFilters"
        >
            <div class="relative w-full sm:w-72">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search projects"
                    aria-label="Search projects"
                />
            </div>

            <Select v-model="status">
                <SelectTrigger class="w-44" aria-label="Filter by status">
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

            <Button type="submit" variant="outline">Apply</Button>
        </form>

        <EmptyState
            v-if="projects.data.length === 0"
            title="No projects found"
            description="Adjust the filters, or create a new project."
            :icon="FolderKanban"
        >
            <Button as-child size="sm">
                <Link :href="projectRoutes.create()">New project</Link>
            </Button>
        </EmptyState>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card
                v-for="project in projects.data"
                :key="project.id"
                class="transition-colors hover:bg-accent/40"
            >
                <CardContent class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <Link
                            :href="projectRoutes.show(project.id)"
                            class="truncate font-medium hover:underline"
                        >
                            {{ project.name }}
                        </Link>
                        <span class="shrink-0 text-xs text-muted-foreground">
                            {{ project.status_label }}
                        </span>
                    </div>

                    <p
                        v-if="project.description"
                        class="line-clamp-2 text-sm text-muted-foreground"
                    >
                        {{ project.description }}
                    </p>

                    <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: `${project.progress}%` }"
                        />
                    </div>

                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>
                            {{ project.open_tasks_count ?? 0 }} open ·
                            {{ project.tasks_count ?? 0 }} total
                        </span>
                        <span v-if="project.due_date">
                            Due {{ project.due_date }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Pagination
            :links="projects.links"
            :from="projects.from"
            :to="projects.to"
            :total="projects.total"
        />
    </div>
</template>
