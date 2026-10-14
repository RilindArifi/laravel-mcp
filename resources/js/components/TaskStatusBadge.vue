<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { TaskStatusValue } from '@/types';

const props = defineProps<{
    status: TaskStatusValue;
    label: string;
}>();

const variant = computed(() => {
    switch (props.status) {
        case 'done':
            return 'secondary';
        case 'blocked':
            return 'destructive';
        case 'in_progress':
            return 'default';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Badge :variant="variant">{{ label }}</Badge>
</template>
