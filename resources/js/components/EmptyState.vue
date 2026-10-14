<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    title: string;
    description?: string;
    icon?: LucideIcon;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed p-10 text-center"
    >
        <component
            v-if="icon"
            :is="icon"
            class="size-8 text-muted-foreground"
            aria-hidden="true"
        />
        <div class="space-y-1">
            <p class="font-medium">{{ title }}</p>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <slot />
    </div>
</template>
