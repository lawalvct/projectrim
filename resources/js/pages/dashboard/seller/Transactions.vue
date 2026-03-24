<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { DollarSign, TrendingUp, Eye, Download } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Transactions', href: '/dashboard/seller/transactions' },
];

const props = defineProps<{
    transactions: {
        data: Array<{
            id: number;
            type: string;
            amount_usd: number;
            product: { id: number; title: string; slug: string } | null;
            created_at: string;
            created_at_diff: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    summary: {
        total: number;
        sales: number;
        views: number;
        downloads: number;
    };
    filters: {
        type: string | null;
    };
}>();

const page = usePage();
const currencySymbol = computed(() => {
    const settings = (page.props.settings as Record<string, string>) || {};
    return settings.currency_symbol || '$';
});

const fmt = (n: number) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const typeFilter = ref(props.filters.type || '');

watch(typeFilter, (val) => {
    router.get('/dashboard/seller/transactions', val ? { type: val } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
});

const typeLabel = (type: string) => ({ sale: 'Sale', view: 'View', download: 'Download' }[type] || type);
const typeColor = (type: string) => ({
    sale: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    view: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    download: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
}[type] || '');
</script>

<template>
    <Head title="Transactions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Summary -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Revenue</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ currencySymbol }}{{ fmt(summary.total) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">From Sales</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ currencySymbol }}{{ fmt(summary.sales) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">From Views</CardTitle>
                        <Eye class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">{{ currencySymbol }}{{ fmt(summary.views) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">From Downloads</CardTitle>
                        <Download class="h-4 w-4 text-purple-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-purple-600">{{ currencySymbol }}{{ fmt(summary.downloads) }}</div>
                    </CardContent>
                </Card>
            </div>

            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Transaction History</h2>
                <select v-model="typeFilter" class="rounded-md border bg-background px-3 py-2 text-sm">
                    <option value="">All Types</option>
                    <option value="sale">Sales</option>
                    <option value="view">Views</option>
                    <option value="download">Downloads</option>
                </select>
            </div>

            <Card>
                <CardContent class="p-0">
                    <div v-if="transactions.data.length">
                        <div class="hidden border-b bg-muted/50 px-6 py-3 md:grid md:grid-cols-12 md:gap-4">
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Type</span>
                            <span class="col-span-5 text-xs font-medium uppercase text-muted-foreground">Product</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Amount</span>
                            <span class="col-span-3 text-xs font-medium uppercase text-muted-foreground">Date</span>
                        </div>

                        <div v-for="t in transactions.data" :key="t.id" class="border-b last:border-0">
                            <div class="grid items-center gap-4 px-6 py-3 md:grid-cols-12">
                                <div class="col-span-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="typeColor(t.type)">
                                        {{ typeLabel(t.type) }}
                                    </span>
                                </div>
                                <div class="col-span-5">
                                    <Link v-if="t.product" :href="`/products/${t.product.slug}`" class="text-sm hover:text-primary line-clamp-1">
                                        {{ t.product.title }}
                                    </Link>
                                    <span v-else class="text-sm text-muted-foreground">—</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">+{{ currencySymbol }}{{ fmt(t.amount_usd) }}</span>
                                </div>
                                <div class="col-span-3">
                                    <span class="text-sm text-muted-foreground" :title="t.created_at">{{ t.created_at_diff }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center gap-3 py-16">
                        <DollarSign class="h-12 w-12 text-muted-foreground/30" />
                        <p class="text-muted-foreground">No transactions yet.</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <nav v-if="transactions.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in transactions.links" :key="link.label">
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
