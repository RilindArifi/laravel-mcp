<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';

defineProps<{
    label: string;
    value: number | string;
    hint?: string;
    icon?: LucideIcon;
    emphasis?: boolean;
}>();
</script>

<template>
    <Card>
        <CardContent class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <p class="text-sm text-muted-foreground">{{ label }}</p>
                <p
                    class="text-3xl font-semibold tabular-nums"
                    :class="emphasis ? 'text-destructive' : ''"
                >
                    {{ value }}
                </p>
                <p v-if="hint" class="text-xs text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
            <component
                v-if="icon"
                :is="icon"
                class="size-5 shrink-0 text-muted-foreground"
            />
        </CardContent>
    </Card>
</template>
