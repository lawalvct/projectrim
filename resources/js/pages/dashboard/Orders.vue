<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ShoppingCart, Package, ChevronDown } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'My Orders', href: '/dashboard/orders' },
];

const props = defineProps<{
    orders: {
        data: Array<{
            id: number;
            order_number: string;
            status: string;
            subtotal: number;
            total: number;
            payment_gateway: string | null;
            paid_at: string | null;
            created_at: string;
            created_at_diff: string;
            items: Array<{
                id: number;
                price: number;
                product: { id: number; title: string; slug: string } | null;
            }>;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        status: string | null;
    };
}>();

const page = usePage();
const currencySymbol = computed(() => {
    const settings = (page.props.settings as Record<string, string>) || {};
    return settings.currency_symbol || '$';
});

const statusFilter = ref(props.filters.status || '');
const expandedOrder = ref<number | null>(null);

watch(statusFilter, (val) => {
    router.get('/dashboard/orders', val ? { status: val } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
});

const toggleExpand = (id: number) => {
    expandedOrder.value = expandedOrder.value === id ? null : id;
};

const statusColor = (status: string) => {
    const map: Record<string, string> = {
        completed: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        refunded: 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300',
    };
    return map[status] || map.pending;
};
</script>

<template>
    <Head title="My Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">My Orders</h1>
                    <p class="text-sm text-muted-foreground">{{ orders.total }} order(s)</p>
                </div>

                <!-- Status filter -->
                <div class="flex items-center gap-2">
                    <select
                        v-model="statusFilter"
                        class="rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
            </div>

            <div v-if="orders.data.length" class="space-y-3">
                <Card v-for="order in orders.data" :key="order.id">
                    <CardContent class="p-0">
                        <!-- Order header -->
                        <button
                            class="flex w-full items-center justify-between px-6 py-4 text-left hover:bg-muted/50 transition-colors"
                            @click="toggleExpand(order.id)"
                        >
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-4">
                                <span class="font-mono text-sm font-semibold">#{{ order.order_number }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusColor(order.status)">
                                    {{ order.status }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ order.created_at }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold">{{ currencySymbol }}{{ Number(order.total).toLocaleString() }}</span>
                                <ChevronDown class="h-4 w-4 text-muted-foreground transition-transform" :class="expandedOrder === order.id && 'rotate-180'" />
                            </div>
                        </button>

                        <!-- Expanded items -->
                        <div v-if="expandedOrder === order.id" class="border-t bg-muted/20 px-6 py-4">
                            <div class="space-y-2">
                                <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between rounded-lg border bg-background px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <Package class="h-4 w-4 text-muted-foreground" />
                                        <Link v-if="item.product" :href="`/products/${item.product.slug}`" class="text-sm font-medium hover:text-primary">
                                            {{ item.product.title }}
                                        </Link>
                                        <span v-else class="text-sm text-muted-foreground italic">Product removed</span>
                                    </div>
                                    <span class="text-sm font-medium">{{ currencySymbol }}{{ Number(item.price).toLocaleString() }}</span>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-4 text-xs text-muted-foreground">
                                <span v-if="order.payment_gateway">Payment: {{ order.payment_gateway }}</span>
                                <span v-if="order.paid_at">Paid: {{ order.paid_at }}</span>
                                <span>Placed: {{ order.created_at_diff }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="flex flex-col items-center gap-3 py-16">
                <ShoppingCart class="h-12 w-12 text-muted-foreground/30" />
                <p class="text-muted-foreground">No orders found.</p>
                <Link href="/products" class="text-sm text-primary hover:underline">Browse products</Link>
            </div>

            <!-- Pagination -->
            <nav v-if="orders.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in orders.links" :key="link.label">
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
