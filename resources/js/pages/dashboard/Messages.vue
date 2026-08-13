<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import type { AxiosError } from 'axios';
import {
    CheckCheck,
    Mail,
    MailOpen,
    Package,
    Reply,
    Send,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

interface MessageEntry {
    id: number;
    sender_user_id: number | null;
    sender_name: string;
    sender_email: string;
    body: string;
    created_at: string;
    created_at_diff: string;
    is_original: boolean;
}

interface MessageThread {
    id: number;
    subject: string;
    product: { id: number; title: string; slug: string } | null;
    entries: MessageEntry[];
}

interface MessageRecipient {
    id: number;
    is_read: boolean;
    can_reply: boolean;
    thread: MessageThread;
}

interface ReplyResponse {
    message: string;
    reply: MessageEntry;
}

interface ReplyErrorResponse {
    message?: string;
    errors?: Record<string, string[]>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Messages', href: '/dashboard/messages' },
];

const props = defineProps<{
    currentUserId: number;
    messages: {
        data: MessageRecipient[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    unreadCount: number;
    filters: {
        filter: string | null;
    };
}>();

const filterValue = ref(props.filters.filter || '');
const expandedMessage = ref<number | null>(null);
const replyBodies = ref<Record<number, string>>({});
const replying = ref<Record<number, boolean>>({});
const replyErrors = ref<Record<number, string>>({});
const replySuccesses = ref<Record<number, string>>({});

watch(filterValue, (val) => {
    router.get('/dashboard/messages', val ? { filter: val } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
});

const toggleExpand = (msg: MessageRecipient) => {
    if (expandedMessage.value === msg.id) {
        expandedMessage.value = null;

        return;
    }

    expandedMessage.value = msg.id;

    if (!msg.is_read) {
        router.patch(
            `/dashboard/messages/${msg.id}/read`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    msg.is_read = true;
                },
            },
        );
    }
};

const markAllRead = () => {
    router.post(
        '/dashboard/messages/mark-all-read',
        {},
        {
            preserveScroll: true,
        },
    );
};

const senderInitial = (name: string) => name.charAt(0).toUpperCase() || '?';

const latestEntry = (thread: MessageThread) => thread.entries.at(-1);

const replyBodyFor = (recipientId: number) =>
    replyBodies.value[recipientId] || '';

const paginationLabel = (label: string) =>
    label
        .replace(/&laquo;/g, String.fromCharCode(171))
        .replace(/&raquo;/g, String.fromCharCode(187));

async function sendReply(msg: MessageRecipient) {
    const body = replyBodyFor(msg.id).trim();

    if (!body || replying.value[msg.id]) {
        return;
    }

    replying.value[msg.id] = true;
    replyErrors.value[msg.id] = '';
    replySuccesses.value[msg.id] = '';

    try {
        const { data } = await axios.post<ReplyResponse>(
            `/dashboard/messages/${msg.id}/reply`,
            { body },
        );

        if (!msg.thread.entries.some((entry) => entry.id === data.reply.id)) {
            msg.thread.entries.push(data.reply);
        }

        replyBodies.value[msg.id] = '';
        replySuccesses.value[msg.id] =
            data.message || 'Reply sent successfully.';
    } catch (error) {
        const response = (error as AxiosError<ReplyErrorResponse>).response;
        replyErrors.value[msg.id] =
            response?.data?.errors?.body?.[0] ||
            response?.data?.message ||
            'We could not send your reply. Please try again.';
    } finally {
        replying.value[msg.id] = false;
    }
}
</script>

<template>
    <Head title="Messages" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold">Messages</h1>
                    <p class="text-sm text-muted-foreground">
                        {{
                            unreadCount > 0
                                ? `${unreadCount} unread message(s)`
                                : 'No unread messages'
                        }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="filterValue"
                        class="rounded-md border bg-background px-3 py-2 text-sm"
                        aria-label="Filter messages"
                    >
                        <option value="">All Messages</option>
                        <option value="unread">Unread Only</option>
                    </select>
                    <Button
                        v-if="unreadCount > 0"
                        variant="outline"
                        size="sm"
                        @click="markAllRead"
                    >
                        <CheckCheck class="mr-1 h-4 w-4" />
                        Mark all read
                    </Button>
                </div>
            </div>

            <div v-if="messages.data.length" class="space-y-3">
                <Card
                    v-for="msg in messages.data"
                    :key="msg.id"
                    class="overflow-hidden transition-colors"
                    :class="!msg.is_read && 'border-primary/30 bg-primary/5'"
                >
                    <CardContent class="p-0">
                        <button
                            class="flex w-full items-start gap-4 px-5 py-4 text-left transition-colors hover:bg-muted/50 sm:px-6"
                            :aria-expanded="expandedMessage === msg.id"
                            :aria-controls="`message-thread-${msg.id}`"
                            @click="toggleExpand(msg)"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                                :class="
                                    msg.is_read
                                        ? 'bg-muted text-muted-foreground'
                                        : 'bg-primary text-primary-foreground'
                                "
                            >
                                {{
                                    senderInitial(
                                        msg.thread.entries[0]?.sender_name ||
                                            '',
                                    )
                                }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="truncate text-sm font-semibold"
                                        :class="!msg.is_read && 'text-primary'"
                                    >
                                        {{
                                            msg.thread.entries[0]
                                                ?.sender_name ||
                                            'Unknown sender'
                                        }}
                                    </span>
                                    <span
                                        class="shrink-0 text-xs text-muted-foreground"
                                    >
                                        {{
                                            latestEntry(msg.thread)
                                                ?.created_at_diff
                                        }}
                                    </span>
                                    <component
                                        :is="msg.is_read ? MailOpen : Mail"
                                        class="ml-auto h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                </div>
                                <p
                                    class="mt-0.5 truncate text-sm font-medium"
                                    :class="!msg.is_read && 'font-bold'"
                                >
                                    {{ msg.thread.subject }}
                                </p>
                                <p
                                    class="mt-0.5 line-clamp-1 text-xs text-muted-foreground"
                                >
                                    {{ latestEntry(msg.thread)?.body }}
                                </p>
                                <div
                                    class="mt-2 flex flex-wrap items-center gap-2"
                                >
                                    <Badge
                                        v-if="msg.thread.product"
                                        variant="outline"
                                        class="max-w-full text-xs"
                                    >
                                        <Package
                                            class="mr-1 h-3 w-3 shrink-0"
                                        />
                                        <span class="truncate">{{
                                            msg.thread.product.title
                                        }}</span>
                                    </Badge>
                                    <span
                                        v-if="msg.thread.entries.length > 1"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ msg.thread.entries.length - 1 }}
                                        {{
                                            msg.thread.entries.length === 2
                                                ? 'reply'
                                                : 'replies'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </button>

                        <div
                            v-if="expandedMessage === msg.id"
                            :id="`message-thread-${msg.id}`"
                            class="border-t bg-muted/20 px-5 py-5 sm:px-6"
                        >
                            <div
                                class="space-y-4"
                                role="log"
                                :aria-label="`Conversation: ${msg.thread.subject}`"
                            >
                                <article
                                    v-for="entry in msg.thread.entries"
                                    :key="entry.id"
                                    class="flex"
                                    :class="
                                        entry.sender_user_id === currentUserId
                                            ? 'justify-end'
                                            : 'justify-start'
                                    "
                                >
                                    <div
                                        class="max-w-[88%] rounded-2xl border px-4 py-3 shadow-sm sm:max-w-[75%]"
                                        :class="
                                            entry.sender_user_id ===
                                            currentUserId
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-border bg-background text-foreground'
                                        "
                                    >
                                        <div
                                            class="mb-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs"
                                            :class="
                                                entry.sender_user_id ===
                                                currentUserId
                                                    ? 'text-primary-foreground/80'
                                                    : 'text-muted-foreground'
                                            "
                                        >
                                            <span
                                                class="font-semibold"
                                                :class="
                                                    entry.sender_user_id ===
                                                        currentUserId &&
                                                    'text-primary-foreground'
                                                "
                                            >
                                                {{
                                                    entry.sender_user_id ===
                                                    currentUserId
                                                        ? 'You'
                                                        : entry.sender_name
                                                }}
                                            </span>
                                            <span
                                                v-if="entry.is_original"
                                                class="rounded-full border px-1.5 py-0.5 text-[10px] font-medium"
                                            >
                                                Original message
                                            </span>
                                            <span>{{
                                                entry.created_at_diff ||
                                                entry.created_at
                                            }}</span>
                                        </div>
                                        <p
                                            class="text-sm leading-6 break-words whitespace-pre-wrap"
                                        >
                                            {{ entry.body }}
                                        </p>
                                    </div>
                                </article>
                            </div>

                            <div v-if="msg.thread.product" class="mt-5">
                                <Link
                                    :href="`/products/${msg.thread.product.slug}`"
                                    class="text-sm font-medium text-primary hover:underline"
                                >
                                    View related product:
                                    {{ msg.thread.product.title }}
                                </Link>
                            </div>

                            <form
                                v-if="msg.can_reply"
                                class="mt-6 rounded-xl border bg-background p-4 shadow-sm"
                                :aria-busy="Boolean(replying[msg.id])"
                                @submit.prevent="sendReply(msg)"
                            >
                                <div class="mb-3 flex items-center gap-2">
                                    <Reply class="h-4 w-4 text-primary" />
                                    <h2 class="text-sm font-semibold">
                                        Reply to sender
                                    </h2>
                                </div>
                                <p class="mb-3 text-xs text-muted-foreground">
                                    Your reply will appear in the sender’s
                                    Messages dashboard and they will receive an
                                    email notification.
                                </p>
                                <label
                                    :for="`reply-body-${msg.id}`"
                                    class="sr-only"
                                    >Your reply</label
                                >
                                <textarea
                                    :id="`reply-body-${msg.id}`"
                                    v-model="replyBodies[msg.id]"
                                    rows="4"
                                    maxlength="5000"
                                    placeholder="Write a helpful reply..."
                                    class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="Boolean(replying[msg.id])"
                                />
                                <p
                                    v-if="replyErrors[msg.id]"
                                    role="alert"
                                    class="mt-2 text-sm text-destructive"
                                >
                                    {{ replyErrors[msg.id] }}
                                </p>
                                <p
                                    v-if="replySuccesses[msg.id]"
                                    role="status"
                                    class="mt-2 text-sm text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ replySuccesses[msg.id] }}
                                </p>
                                <div class="mt-3 flex justify-end">
                                    <Button
                                        type="submit"
                                        :disabled="
                                            !replyBodyFor(msg.id).trim() ||
                                            Boolean(replying[msg.id])
                                        "
                                    >
                                        <Send class="mr-2 h-4 w-4" />
                                        {{
                                            replying[msg.id]
                                            ? 'Sending...'
                                                : 'Send reply'
                                        }}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="flex flex-col items-center gap-3 py-16">
                <Mail class="h-12 w-12 text-muted-foreground/30" />
                <p class="text-muted-foreground">No messages found.</p>
            </div>

            <nav
                v-if="messages.last_page > 1"
                class="flex items-center justify-center gap-1"
                aria-label="Message pages"
            >
                <template v-for="link in messages.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'hover:bg-muted'
                        "
                        preserve-scroll
                    >
                        {{ paginationLabel(link.label) }}
                    </Link>
                    <span
                        v-else
                        class="inline-flex h-9 min-w-9 items-center justify-center text-sm text-muted-foreground"
                    >
                        {{ paginationLabel(link.label) }}
                    </span>
                </template>
            </nav>
        </div>
    </AppLayout>
</template>
