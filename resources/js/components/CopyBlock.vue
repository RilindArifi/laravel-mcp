<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

defineProps<{
    code: string;
    label?: string;
}>();

const copied = ref(false);

const copy = async (code: string) => {
    await navigator.clipboard.writeText(code);
    copied.value = true;

    window.setTimeout(() => {
        copied.value = false;
    }, 2000);
};
</script>

<template>
    <div class="space-y-2">
        <div v-if="label" class="flex items-center justify-between gap-2">
            <p class="text-sm font-medium">{{ label }}</p>
            <Button type="button" size="sm" variant="ghost" @click="copy(code)">
                <component :is="copied ? Check : Copy" class="size-4" />
                {{ copied ? 'Copied' : 'Copy' }}
            </Button>
        </div>

        <pre
            class="overflow-x-auto rounded-md bg-muted p-3 font-mono text-xs"
        ><code>{{ code }}</code></pre>
    </div>
</template>
