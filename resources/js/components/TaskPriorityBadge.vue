<script setup lang="ts">
import { computed } from 'vue';
import type { TaskPriorityValue } from '@/types';

const props = defineProps<{
    priority: TaskPriorityValue;
    label: string;
}>();

const classes = computed(() => {
    switch (props.priority) {
        case 'urgent':
            return 'bg-rose-500/10 text-rose-700 dark:text-rose-300';
        case 'high':
            return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
        case 'medium':
            return 'bg-sky-500/10 text-sky-700 dark:text-sky-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
        :class="classes"
    >
        {{ label }}
    </span>
</template>
