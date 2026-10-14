<script setup lang="ts">
import { computed } from 'vue';
import type { LabelItem } from '@/types';

const props = defineProps<{
    label: LabelItem;
}>();

const colors: Record<string, string> = {
    blue: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    violet: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
    rose: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    amber: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    green: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    slate: 'bg-muted text-muted-foreground',
};

const classes = computed(() => colors[props.label.color] ?? colors.slate);
</script>

<template>
    <span
        class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium"
        :class="classes"
    >
        {{ label.name }}
    </span>
</template>
