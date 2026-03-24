<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { DollarSign, TrendingUp, Package, ShoppingCart, Eye, Download, ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Seller Overview', href: '/dashboard/seller' },
];

const props = defineProps<{
    stats: {
        total_revenue: number;
        sale_revenue: number;
        view_revenue: number;
        download_revenue: number;
        total_paid_out: number;
        pending_payout: number;
        available_balance: number;
        total_products: number;
        published_products: number;
        total_orders: number;
    };
    monthlyRevenue: Array<{ month: string; total: number }>;
    topProducts: Array<{
        id: number;
        title: string;
        slug: string;
        views_count: number;
        downloads_count: number;
        likes_count: number;
    }>;
    recentRevenue: Array<{
        id: number;
        type: string;
        amount_usd: number;
        product: { title: string; slug: string } | null;
        created_at: string;
    }>;
}>();

const page = usePage();
const currencySymbol = computed(() => {
    const settings = (page.props.settings as Record<string, string>) || {};
    return settings.currency_symbol || '$';
});

const fmt = (n: number) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const typeLabel = (type: string) => {
    const map: Record<string, string> = { sale: 'Sale', view: 'View', download: 'Download' };
    return map[type] || type;
};

const typeColor = (type: string) => {
    const map: Record<string, string> = {
        sale: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        view: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        download: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
    };
    return map[type] || '';
};

// Simple bar chart via max-height scaling
const maxMonthly = computed(() => Math.max(...props.monthlyRevenue.map(m => m.total), 1));
</script>

<template>
    <Head title="Seller Overview" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Revenue Stats -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Revenue</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ currencySymbol }}{{ fmt(stats.total_revenue) }}</div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Sales: {{ currencySymbol }}{{ fmt(stats.sale_revenue) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Available Balance</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ currencySymbol }}{{ fmt(stats.available_balance) }}</div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Pending: {{ currencySymbol }}{{ fmt(stats.pending_payout) }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Products</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_products }}</div>
                        <p class="text-xs text-muted-foreground mt-1">{{ stats.published_products }} published</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Orders Received</CardTitle>
                        <ShoppingCart class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_orders }}</div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Paid out: {{ currencySymbol }}{{ fmt(stats.total_paid_out) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Monthly Revenue Chart -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Revenue (Last 6 Months)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="monthlyRevenue.length" class="flex items-end gap-2 h-40">
                            <div v-for="m in monthlyRevenue" :key="m.month" class="flex flex-1 flex-col items-center gap-1">
                                <span class="text-xs font-medium">{{ currencySymbol }}{{ fmt(m.total) }}</span>
                                <div
                                    class="w-full rounded-t bg-primary transition-all"
                                    :style="{ height: `${Math.max((m.total / maxMonthly) * 100, 4)}%` }"
                                />
                                <span class="text-xs text-muted-foreground">{{ m.month.split('-')[1] }}</span>
                            </div>
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">No revenue data yet.</p>
                    </CardContent>
                </Card>

                <!-- Top Products -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-base">Top Products</CardTitle>
                        <Link href="/dashboard/seller/products" class="text-sm text-primary hover:underline">
                            View all <ArrowRight class="ml-1 inline h-3 w-3" />
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div v-if="topProducts.length" class="space-y-3">
                            <div v-for="p in topProducts" :key="p.id" class="flex items-center justify-between rounded-lg border p-3">
                                <Link :href="`/products/${p.slug}`" class="text-sm font-medium hover:text-primary line-clamp-1 flex-1 mr-4">
                                    {{ p.title }}
                                </Link>
                                <div class="flex items-center gap-3 text-xs text-muted-foreground flex-shrink-0">
                                    <span class="flex items-center gap-1"><Eye class="h-3 w-3" /> {{ p.views_count }}</span>
                                    <span class="flex items-center gap-1"><Download class="h-3 w-3" /> {{ p.downloads_count }}</span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="py-8 text-center text-sm text-muted-foreground">No products yet.</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Revenue -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="text-base">Recent Revenue</CardTitle>
                    <Link href="/dashboard/seller/transactions" class="text-sm text-primary hover:underline">
                        View all <ArrowRight class="ml-1 inline h-3 w-3" />
                    </Link>
                </CardHeader>
                <CardContent>
                    <div v-if="recentRevenue.length" class="space-y-2">
                        <div v-for="r in recentRevenue" :key="r.id" class="flex items-center justify-between rounded-lg border p-3">
                            <div class="flex items-center gap-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="typeColor(r.type)">
                                    {{ typeLabel(r.type) }}
                                </span>
                                <div>
                                    <Link v-if="r.product" :href="`/products/${r.product.slug}`" class="text-sm font-medium hover:text-primary">
                                        {{ r.product.title }}
                                    </Link>
                                    <span v-else class="text-sm text-muted-foreground">Unknown product</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="font-semibold text-green-600 dark:text-green-400">+{{ currencySymbol }}{{ fmt(r.amount_usd) }}</span>
                                <span class="text-xs text-muted-foreground">{{ r.created_at }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-8 text-center text-sm text-muted-foreground">No revenue earned yet. Start uploading products!</p>
                </CardContent>
            </Card>

            <!-- Quick Actions -->
            <div class="grid gap-4 sm:grid-cols-3">
                <Link href="/dashboard/seller/products/create" class="group">
                    <Card class="transition-shadow hover:shadow-md">
                        <CardContent class="flex items-center gap-3 p-4">
                            <Package class="h-5 w-5 text-primary" />
                            <span class="text-sm font-medium group-hover:text-primary">Upload New Product</span>
                        </CardContent>
                    </Card>
                </Link>
                <Link href="/dashboard/seller/payouts" class="group">
                    <Card class="transition-shadow hover:shadow-md">
                        <CardContent class="flex items-center gap-3 p-4">
                            <DollarSign class="h-5 w-5 text-primary" />
                            <span class="text-sm font-medium group-hover:text-primary">Request Payout</span>
                        </CardContent>
                    </Card>
                </Link>
                <Link href="/dashboard/seller/profile" class="group">
                    <Card class="transition-shadow hover:shadow-md">
                        <CardContent class="flex items-center gap-3 p-4">
                            <TrendingUp class="h-5 w-5 text-primary" />
                            <span class="text-sm font-medium group-hover:text-primary">Edit Profile</span>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
