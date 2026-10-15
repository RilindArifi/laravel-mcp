<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Check, Copy, KeyRound, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import TokenController from '@/actions/App/Http/Controllers/Mcp/TokenController';
import CopyBlock from '@/components/CopyBlock.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
import mcpTokens from '@/routes/mcp-tokens';

type McpToken = {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string | null;
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'MCP tokens', href: mcpTokens.index() }],
    },
});

const props = defineProps<{
    tokens: McpToken[];
    abilityOptions: string[];
    endpoint: string;
    newToken: string | null;
    phpBinary: string;
    artisanPath: string;
    userEmail: string;
}>();

const expiry = ref('never');
const copied = ref<'token' | 'config' | null>(null);

const token = computed(() => props.newToken ?? '<your-token>');

const cliCommand = computed(
    () =>
        `claude mcp add --transport http taskflow ${props.endpoint} --header "Authorization: Bearer ${token.value}"`,
);

const clientConfig = computed(() =>
    JSON.stringify(
        {
            mcpServers: {
                taskflow: {
                    type: 'http',
                    url: props.endpoint,
                    headers: { Authorization: `Bearer ${token.value}` },
                },
            },
        },
        null,
        2,
    ),
);

const stdioConfig = computed(() =>
    JSON.stringify(
        {
            mcpServers: {
                taskflow: {
                    command: props.phpBinary,
                    args: [
                        props.artisanPath,
                        'mcp:serve',
                        `--user=${props.userEmail}`,
                    ],
                },
            },
        },
        null,
        2,
    ),
);

const copy = async (what: 'token' | 'config', value: string) => {
    await navigator.clipboard.writeText(value);
    copied.value = what;

    window.setTimeout(() => {
        copied.value = null;
    }, 2000);
};

const revoking = ref<McpToken | null>(null);

const confirmRevoke = () => {
    const token = revoking.value;

    if (token === null) {
        return;
    }

    router.delete(mcpTokens.destroy(token.id).url, {
        preserveScroll: true,
        onFinish: () => {
            revoking.value = null;
        },
    });
};
</script>

<template>
    <Head title="MCP tokens" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold">MCP tokens</h1>
            <p class="text-sm text-muted-foreground">
                Tokens let an AI client reach the HTTP MCP server at
                <code class="rounded bg-muted px-1 py-0.5 text-xs">{{
                    endpoint
                }}</code>
                as you.
            </p>
        </div>

        <Alert v-if="newToken">
            <ShieldCheck class="size-4" />
            <AlertTitle>Copy your token now</AlertTitle>
            <AlertDescription class="space-y-3">
                <p>
                    This is the only time it is shown. It carries your
                    permissions, so treat it like a password.
                </p>

                <div class="flex w-full items-center gap-2">
                    <code
                        class="min-w-0 flex-1 truncate rounded-md bg-muted px-3 py-2 font-mono text-xs"
                        >{{ newToken }}</code
                    >
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="copy('token', newToken)"
                    >
                        <component
                            :is="copied === 'token' ? Check : Copy"
                            class="size-4"
                        />
                        {{ copied === 'token' ? 'Copied' : 'Copy' }}
                    </Button>
                </div>

                <CopyBlock
                    label="Claude Code — one command"
                    :code="cliCommand"
                />
            </AlertDescription>
        </Alert>

        <div class="grid gap-4 lg:grid-cols-[2fr_3fr]">
            <Card>
                <CardHeader>
                    <CardTitle>Issue a token</CardTitle>
                    <CardDescription>
                        Give read-only access to agents that should never change
                        your data.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="TokenController.store.form()"
                        class="space-y-5"
                        :options="{ preserveScroll: true }"
                        reset-on-success
                        v-slot="{ errors, processing }"
                    >
                        <input
                            type="hidden"
                            name="expires_in_days"
                            :value="expiry === 'never' ? '' : expiry"
                        />

                        <div class="grid gap-2">
                            <Label for="token-name">Name</Label>
                            <Input
                                id="token-name"
                                name="name"
                                required
                                placeholder="Claude Code"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label>Abilities</Label>
                            <div class="flex flex-wrap gap-4">
                                <label
                                    v-for="ability in abilityOptions"
                                    :key="ability"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        name="abilities[]"
                                        class="size-4 rounded border-input accent-primary"
                                        :value="ability"
                                        :checked="ability === 'mcp:read'"
                                    />
                                    <code class="text-xs">{{ ability }}</code>
                                </label>
                            </div>
                            <InputError :message="errors.abilities" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="token-expiry">Expires</Label>
                            <Select v-model="expiry">
                                <SelectTrigger id="token-expiry">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="never">Never</SelectItem>
                                    <SelectItem value="7">In 7 days</SelectItem>
                                    <SelectItem value="30">
                                        In 30 days
                                    </SelectItem>
                                    <SelectItem value="90">
                                        In 90 days
                                    </SelectItem>
                                    <SelectItem value="365">
                                        In a year
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.expires_in_days" />
                        </div>

                        <Button type="submit" :disabled="processing">
                            Issue token
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Active tokens</CardTitle>
                    <CardDescription>
                        Revoking a token takes effect on the next request.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <EmptyState
                        v-if="tokens.length === 0"
                        title="No tokens yet"
                        description="Issue one to connect Claude Code, Cursor or the MCP Inspector over HTTP."
                        :icon="KeyRound"
                    />

                    <ul v-else class="divide-y">
                        <li
                            v-for="token in tokens"
                            :key="token.id"
                            class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0 space-y-1">
                                <p class="truncate font-medium">
                                    {{ token.name }}
                                </p>
                                <div
                                    class="flex flex-wrap items-center gap-1.5"
                                >
                                    <Badge
                                        v-for="ability in token.abilities"
                                        :key="ability"
                                        variant="secondary"
                                        class="font-mono text-[11px]"
                                    >
                                        {{ ability }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    Created {{ token.created_at }} ·
                                    {{
                                        token.last_used_at
                                            ? `last used ${token.last_used_at}`
                                            : 'never used'
                                    }}
                                    <template v-if="token.expires_at">
                                        · expires {{ token.expires_at }}
                                    </template>
                                </p>
                            </div>

                            <Button
                                variant="ghost"
                                size="sm"
                                class="shrink-0 text-destructive hover:text-destructive"
                                @click="revoking = token"
                            >
                                Revoke
                            </Button>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <Dialog
            :open="revoking !== null"
            @update:open="(open) => (revoking = open ? revoking : null)"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Revoke this token?</DialogTitle>
                    <DialogDescription>
                        Any client using
                        <span class="font-medium">{{ revoking?.name }}</span>
                        stops working immediately. This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="revoking = null"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="confirmRevoke"
                    >
                        Revoke token
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Card>
            <CardHeader>
                <CardTitle>Connect a client</CardTitle>
                <CardDescription>
                    Issue a token above and these fill themselves in.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <CopyBlock label="Claude Code (CLI)" :code="cliCommand" />

                <CopyBlock
                    label="Claude Desktop — claude_desktop_config.json"
                    :code="clientConfig"
                />

                <div class="space-y-2">
                    <p class="text-sm text-muted-foreground">
                        On this machine you can skip tokens entirely: the stdio
                        transport runs the server as a local process, so there
                        is no URL, no token and no certificate to trust.
                    </p>
                    <CopyBlock
                        label="Any MCP client — stdio, no token"
                        :code="stdioConfig"
                    />
                </div>
            </CardContent>
        </Card>
    </div>
</template>
