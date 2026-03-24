<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Mail, MailOpen, Package, CheckCheck } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Messages', href: '/dashboard/messages' },
];

const props = defineProps<{
    messages: {
        data: Array<{
            id: number;
            message_id: number;
            is_read: boolean;
            message: {
                id: number;
                sender_name: string;
                sender_email: string;
                subject: string;
                body: string;
                created_at: string;
                created_at_diff: string;
                product: { id: number; title: string; slug: string } | null;
            } | null;
        }>;
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

watch(filterValue, (val) => {
    router.get('/dashboard/messages', val ? { filter: val } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
});

const toggleExpand = (msg: typeof props.messages.data[0]) => {
    if (expandedMessage.value === msg.id) {
        expandedMessage.value = null;
        return;
    }
    expandedMessage.value = msg.id;
    if (!msg.is_read) {
        router.patch(`/dashboard/messages/${msg.id}/read`, {}, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                msg.is_read = true;
            },
        });
    }
};

const markAllRead = () => {
    router.post('/dashboard/messages/mark-all-read', {}, {
        preserveScroll: true,
    });
};

const senderInitial = (name: string) => name.charAt(0).toUpperCase();
</script>

<template>
    <Head title="Messages" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Messages</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ unreadCount > 0 ? `${unreadCount} unread message(s)` : 'No unread messages' }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="filterValue"
                        class="rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All Messages</option>
                        <option value="unread">Unread Only</option>
                    </select>
                    <Button v-if="unreadCount > 0" variant="outline" size="sm" @click="markAllRead">
                        <CheckCheck class="mr-1 h-4 w-4" />
                        Mark all read
                    </Button>
                </div>
            </div>

            <div v-if="messages.data.length" class="space-y-2">
                <Card
                    v-for="msg in messages.data"
                    :key="msg.id"
                    class="transition-colors"
                    :class="!msg.is_read && 'border-primary/30 bg-primary/5'"
                >
                    <CardContent class="p-0">
                        <button
                            class="flex w-full items-start gap-4 px-6 py-4 text-left hover:bg-muted/50 transition-colors"
                            @click="toggleExpand(msg)"
                        >
                            <!-- Avatar -->
                            <div
                                class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                                :class="msg.is_read ? 'bg-muted text-muted-foreground' : 'bg-primary text-white'"
                            >
                                {{ msg.message ? senderInitial(msg.message.sender_name) : '?' }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold" :class="!msg.is_read && 'text-primary'">
                                        {{ msg.message?.sender_name || 'Unknown' }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">{{ msg.message?.created_at_diff }}</span>
                                    <component :is="msg.is_read ? MailOpen : Mail" class="ml-auto h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                </div>
                                <p class="text-sm font-medium" :class="!msg.is_read && 'font-bold'">{{ msg.message?.subject }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground line-clamp-1">{{ msg.message?.body }}</p>
                                <div v-if="msg.message?.product" class="mt-1">
                                    <Badge variant="outline" class="text-xs">
                                        <Package class="mr-1 h-3 w-3" />
                                        {{ msg.message.product.title }}
                                    </Badge>
                                </div>
                            </div>
                        </button>

                        <!-- Expanded body -->
                        <div v-if="expandedMessage === msg.id" class="border-t bg-muted/20 px-6 py-4">
                            <div class="mb-3 flex flex-wrap gap-4 text-xs text-muted-foreground">
                                <span>From: {{ msg.message?.sender_name }} &lt;{{ msg.message?.sender_email }}&gt;</span>
                                <span>Date: {{ msg.message?.created_at }}</span>
                            </div>
                            <div class="prose prose-sm max-w-none dark:prose-invert whitespace-pre-wrap">{{ msg.message?.body }}</div>
                            <div v-if="msg.message?.product" class="mt-4">
                                <Link :href="`/products/${msg.message.product.slug}`" class="text-sm text-primary hover:underline">
                                    View related product: {{ msg.message.product.title }}
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="flex flex-col items-center gap-3 py-16">
                <Mail class="h-12 w-12 text-muted-foreground/30" />
                <p class="text-muted-foreground">No messages found.</p>
            </div>

            <!-- Pagination -->
            <nav v-if="messages.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in messages.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm transition-colors"
                        :class="link.active ? 'border-primary bg-primary text-white' : 'hover:bg-muted'"
                        preserve-scroll
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="inline-flex h-9 min-w-9 items-center justify-center text-sm text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AppLayout>
</template>
