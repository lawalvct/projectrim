<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Download, ShoppingCart, Mail, DollarSign, ArrowRight, Package, Store } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

const props = defineProps<{
    stats: {
        total_downloads: number;
        total_orders: number;
        unread_messages: number;
        total_spent: number;
    };
    recentDownloads: Array<{
        id: number;
        product: { title: string; slug: string } | null;
        created_at: string;
    }>;
    recentOrders: Array<{
        id: number;
        order_number: string;
        status: string;
        total: number;
        items_count: number;
        created_at: string;
    }>;
    isSeller: boolean;
}>();

const page = usePage();
const currencySymbol = computed(() => {
    const settings = (page.props.settings as Record<string, string>) || {};
    return settings.currency_symbol || '$';
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
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Stats Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Downloads</CardTitle>
                        <Download class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_downloads }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Orders</CardTitle>
                        <ShoppingCart class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_orders }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Unread Messages</CardTitle>
                        <Mail class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.unread_messages }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Spent</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ currencySymbol }}{{ Number(stats.total_spent).toLocaleString() }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Become a Seller Banner -->
            <Card v-if="!isSeller" class="border-primary/20 bg-primary/5">
                <CardContent class="flex items-center justify-between p-6">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                            <Store class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <h3 class="font-semibold">Become a Seller</h3>
                            <p class="text-sm text-muted-foreground">Start selling your research projects and earn revenue.</p>
                        </div>
                    </div>
                    <Button as-child>
                        <Link href="/dashboard/apply-seller">Apply Now</Link>
                    </Button>
                </CardContent>
            </Card>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Downloads -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-base">Recent Downloads</CardTitle>
                        <Link href="/dashboard/downloads" class="text-sm text-primary hover:underline">
                            View all <ArrowRight class="ml-1 inline h-3 w-3" />
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentDownloads.length" class="space-y-3">
                            <div v-for="dl in recentDownloads" :key="dl.id" class="flex items-center justify-between rounded-lg border p-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded bg-muted">
                                        <Package class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <div>
                                        <Link v-if="dl.product" :href="`/products/${dl.product.slug}`" class="text-sm font-medium hover:text-primary">
                                            {{ dl.product.title }}
                                        </Link>
                                        <span v-else class="text-sm text-muted-foreground">Deleted product</span>
                                    </div>
                                </div>
                                <span class="text-xs text-muted-foreground">{{ dl.created_at }}</span>
                            </div>
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">No downloads yet.</p>
                    </CardContent>
                </Card>

                <!-- Recent Orders -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-base">Recent Orders</CardTitle>
                        <Link href="/dashboard/orders" class="text-sm text-primary hover:underline">
                            View all <ArrowRight class="ml-1 inline h-3 w-3" />
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentOrders.length" class="space-y-3">
                            <div v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between rounded-lg border p-3">
                                <div>
                                    <p class="text-sm font-medium">#{{ order.order_number }}</p>
                                    <p class="text-xs text-muted-foreground">{{ order.items_count }} item(s) &middot; {{ order.created_at }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold">{{ currencySymbol }}{{ Number(order.total).toLocaleString() }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusColor(order.status)">
                                        {{ order.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">No orders yet.</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Links -->
            <div class="grid gap-4 sm:grid-cols-3">
                <Link href="/dashboard/explore" class="group">
                    <Card class="transition-shadow hover:shadow-md">
                        <CardContent class="flex items-center gap-3 p-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950">
                                <Package class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium group-hover:text-primary">Explore Products</p>
                                <p class="text-xs text-muted-foreground">Browse research projects</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
                <Link href="/dashboard/messages" class="group">
                    <Card class="transition-shadow hover:shadow-md">
                        <CardContent class="flex items-center gap-3 p-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 dark:bg-green-950">
                                <Mail class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium group-hover:text-primary">Messages</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ stats.unread_messages > 0 ? `${stats.unread_messages} unread` : 'No new messages' }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
                <Link href="/products" class="group">
                    <Card class="transition-shadow hover:shadow-md">
                        <CardContent class="flex items-center gap-3 p-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-950">
                                <Download class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium group-hover:text-primary">Browse Catalog</p>
                                <p class="text-xs text-muted-foreground">Find new projects</p>
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
