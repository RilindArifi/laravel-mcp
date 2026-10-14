<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

const props = defineProps<{
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}>();

const hasPages = props.links.length > 3;
</script>

<template>
    <div
        v-if="total > 0"
        class="flex flex-wrap items-center justify-between gap-3"
    >
        <p class="text-sm text-muted-foreground">
            Showing {{ from ?? 0 }}–{{ to ?? 0 }} of {{ total }}
        </p>

        <nav v-if="hasPages" class="flex flex-wrap items-center gap-1">
            <template v-for="link in links" :key="link.label">
                <span
                    v-if="link.url === null"
                    class="rounded-md px-2.5 py-1.5 text-sm text-muted-foreground"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    class="rounded-md px-2.5 py-1.5 text-sm transition-colors hover:bg-accent"
                    :class="
                        link.active
                            ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                            : ''
                    "
                    preserve-scroll
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
