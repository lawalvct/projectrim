<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { DollarSign, CreditCard } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Payments Received', href: '/dashboard/seller/payments' },
];

const props = defineProps<{
    payments: {
        data: Array<{
            id: number;
            amount_usd: number;
            payment_method: string | null;
            reference: string | null;
            paid_at: string | null;
            paid_at_diff: string | null;
            payout_request: {
                id: number;
                amount_usd: number;
                status: string;
            } | null;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    totalReceived: number;
}>();

const page = usePage();
const currencySymbol = computed(() => {
    const settings = (page.props.settings as Record<string, string>) || {};
    return settings.currency_symbol || '$';
});
const fmt = (n: number) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Payments Received" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Received</CardTitle>
                        <DollarSign class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ currencySymbol }}{{ fmt(totalReceived) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Payments</CardTitle>
                        <CreditCard class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ payments.total }}</div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Payment History</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div v-if="payments.data.length">
                        <div class="hidden border-b bg-muted/50 px-6 py-3 md:grid md:grid-cols-12 md:gap-4">
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Amount</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Method</span>
                            <span class="col-span-3 text-xs font-medium uppercase text-muted-foreground">Reference</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Payout Req</span>
                            <span class="col-span-3 text-xs font-medium uppercase text-muted-foreground">Paid At</span>
                        </div>

                        <div v-for="p in payments.data" :key="p.id" class="border-b last:border-0">
                            <div class="grid items-center gap-4 px-6 py-4 md:grid-cols-12">
                                <div class="col-span-2">
                                    <span class="text-sm font-semibold text-green-600">{{ currencySymbol }}{{ fmt(p.amount_usd) }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-sm text-muted-foreground">{{ p.payment_method || '—' }}</span>
                                </div>
                                <div class="col-span-3">
                                    <span class="text-sm text-muted-foreground font-mono line-clamp-1">{{ p.reference || '—' }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span v-if="p.payout_request" class="text-sm text-muted-foreground">#{{ p.payout_request.id }}</span>
                                    <span v-else class="text-sm text-muted-foreground">—</span>
                                </div>
                                <div class="col-span-3">
                                    <span class="text-sm text-muted-foreground">{{ p.paid_at || '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center gap-3 py-16">
                        <CreditCard class="h-12 w-12 text-muted-foreground/30" />
                        <p class="text-muted-foreground">No payments received yet.</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <nav v-if="payments.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in payments.links" :key="link.label">
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
