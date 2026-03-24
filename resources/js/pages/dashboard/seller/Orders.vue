<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ShoppingCart, Package } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Seller Orders', href: '/dashboard/seller/orders' },
];

const props = defineProps<{
    orderItems: {
        data: Array<{
            id: number;
            price: number;
            order: {
                id: number;
                order_number: string;
                status: string;
                total: number;
                payment_gateway: string | null;
                paid_at: string | null;
                created_at: string;
                buyer: { name: string; email: string } | null;
            } | null;
            product: { id: number; title: string; slug: string } | null;
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

watch(statusFilter, (val) => {
    router.get('/dashboard/seller/orders', val ? { status: val } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
});

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
    <Head title="Seller Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Orders</h1>
                    <p class="text-sm text-muted-foreground">{{ orderItems.total }} order item(s) for your products</p>
                </div>
                <select v-model="statusFilter" class="rounded-md border bg-background px-3 py-2 text-sm">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>

            <Card>
                <CardContent class="p-0">
                    <div v-if="orderItems.data.length">
                        <!-- Header -->
                        <div class="hidden border-b bg-muted/50 px-6 py-3 md:grid md:grid-cols-12 md:gap-4">
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Order #</span>
                            <span class="col-span-3 text-xs font-medium uppercase text-muted-foreground">Product</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Buyer</span>
                            <span class="col-span-1 text-xs font-medium uppercase text-muted-foreground">Price</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Status</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Date</span>
                        </div>

                        <div v-for="item in orderItems.data" :key="item.id" class="border-b last:border-0">
                            <div class="grid items-center gap-4 px-6 py-4 md:grid-cols-12">
                                <div class="col-span-2">
                                    <span class="font-mono text-sm">#{{ item.order?.order_number }}</span>
                                </div>
                                <div class="col-span-3">
                                    <Link v-if="item.product" :href="`/products/${item.product.slug}`" class="text-sm font-medium hover:text-primary line-clamp-1">
                                        {{ item.product.title }}
                                    </Link>
                                    <span v-else class="text-sm text-muted-foreground italic">Removed</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-sm">{{ item.order?.buyer?.name || '—' }}</span>
                                </div>
                                <div class="col-span-1">
                                    <span class="text-sm font-semibold">{{ currencySymbol }}{{ Number(item.price).toLocaleString() }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusColor(item.order?.status || 'pending')">
                                        {{ item.order?.status }}
                                    </span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-sm text-muted-foreground">{{ item.order?.created_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center gap-3 py-16">
                        <ShoppingCart class="h-12 w-12 text-muted-foreground/30" />
                        <p class="text-muted-foreground">No orders received yet.</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <nav v-if="orderItems.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in orderItems.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm transition-colors"
                        :class="link.active ? 'border-primary bg-primary text-white' : 'hover:bg-muted'"
                        preserve-scroll
                        v-html="link.label"
                    />
                    <span v-else class="inline-flex h-9 min-w-9 items-center justify-center text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </nav>
        </div>
    </AppLayout>
</template>
