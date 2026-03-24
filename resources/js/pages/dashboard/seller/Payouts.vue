<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Wallet, DollarSign, Clock, CheckCircle, Plus } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Payout Requests', href: '/dashboard/seller/payouts' },
];

const props = defineProps<{
    payouts: {
        data: Array<{
            id: number;
            amount_usd: number;
            status: string;
            payment_method: string | null;
            payment_details: string | null;
            admin_note: string | null;
            processed_at: string | null;
            created_at: string;
            created_at_diff: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    balance: {
        total_revenue: number;
        total_paid_out: number;
        pending_payout: number;
        available: number;
    };
    paymentMethods: Array<{ id: number; name: string }>;
    filters: { status: string | null };
}>();

const page = usePage();
const currencySymbol = computed(() => {
    const settings = (page.props.settings as Record<string, string>) || {};
    return settings.currency_symbol || '$';
});
const fmt = (n: number) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const flash = computed(() => (page.props.flash as any)?.success);

const statusFilter = ref(props.filters.status || '');
const showDialog = ref(false);

watch(statusFilter, (val) => {
    router.get('/dashboard/seller/payouts', val ? { status: val } : {}, { preserveState: true, preserveScroll: true });
});

const form = useForm({
    amount_usd: '',
    payment_method_id: '',
    payment_details: '',
});

const submitPayout = () => {
    form.post('/dashboard/seller/payouts', {
        preserveScroll: true,
        onSuccess: () => {
            showDialog.value = false;
            form.reset();
        },
    });
};

const statusColor = (status: string) => ({
    pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    approved: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
    paid: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
}[status] || '');
</script>

<template>
    <Head title="Payout Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Balance Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Revenue</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ currencySymbol }}{{ fmt(balance.total_revenue) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Available</CardTitle>
                        <Wallet class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ currencySymbol }}{{ fmt(balance.available) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Pending</CardTitle>
                        <Clock class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-yellow-600">{{ currencySymbol }}{{ fmt(balance.pending_payout) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Paid Out</CardTitle>
                        <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ currencySymbol }}{{ fmt(balance.total_paid_out) }}</div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="flash" class="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">
                {{ flash }}
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold">Payout History</h2>
                    <select v-model="statusFilter" class="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="paid">Paid</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <Dialog v-model:open="showDialog">
                    <DialogTrigger as-child>
                        <Button :disabled="balance.available <= 0">
                            <Plus class="mr-1 h-4 w-4" /> Request Payout
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Request Payout</DialogTitle>
                        </DialogHeader>
                        <form @submit.prevent="submitPayout" class="space-y-4">
                            <p class="text-sm text-muted-foreground">
                                Available balance: <strong class="text-green-600">{{ currencySymbol }}{{ fmt(balance.available) }}</strong>
                            </p>

                            <div>
                                <Label for="amount">Amount (USD)</Label>
                                <Input id="amount" v-model="form.amount_usd" type="number" step="0.01" min="1" :max="balance.available" placeholder="0.00" />
                                <p v-if="form.errors.amount_usd" class="mt-1 text-xs text-red-500">{{ form.errors.amount_usd }}</p>
                            </div>

                            <div>
                                <Label for="pm">Payment Method</Label>
                                <select id="pm" v-model="form.payment_method_id" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                                    <option value="">Select method</option>
                                    <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">{{ pm.name }}</option>
                                </select>
                                <p v-if="form.errors.payment_method_id" class="mt-1 text-xs text-red-500">{{ form.errors.payment_method_id }}</p>
                            </div>

                            <div>
                                <Label for="details">Payment Details (optional)</Label>
                                <textarea
                                    id="details"
                                    v-model="form.payment_details"
                                    rows="3"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    placeholder="Bank name, account number, etc."
                                />
                            </div>

                            <Button type="submit" :disabled="form.processing" class="w-full">
                                {{ form.processing ? 'Submitting...' : 'Submit Request' }}
                            </Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Card>
                <CardContent class="p-0">
                    <div v-if="payouts.data.length">
                        <div class="hidden border-b bg-muted/50 px-6 py-3 md:grid md:grid-cols-12 md:gap-4">
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Amount</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Status</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Method</span>
                            <span class="col-span-3 text-xs font-medium uppercase text-muted-foreground">Note</span>
                            <span class="col-span-3 text-xs font-medium uppercase text-muted-foreground">Date</span>
                        </div>

                        <div v-for="p in payouts.data" :key="p.id" class="border-b last:border-0">
                            <div class="grid items-center gap-4 px-6 py-4 md:grid-cols-12">
                                <div class="col-span-2">
                                    <span class="text-sm font-semibold">{{ currencySymbol }}{{ fmt(p.amount_usd) }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusColor(p.status)">
                                        {{ p.status }}
                                    </span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-sm text-muted-foreground">{{ p.payment_method || '—' }}</span>
                                </div>
                                <div class="col-span-3">
                                    <span class="text-sm text-muted-foreground line-clamp-1">{{ p.admin_note || '—' }}</span>
                                </div>
                                <div class="col-span-3">
                                    <span class="text-sm text-muted-foreground">{{ p.created_at }}</span>
                                    <span v-if="p.processed_at" class="block text-xs text-muted-foreground">Processed: {{ p.processed_at }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center gap-3 py-16">
                        <Wallet class="h-12 w-12 text-muted-foreground/30" />
                        <p class="text-muted-foreground">No payout requests yet.</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <nav v-if="payouts.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in payouts.links" :key="link.label">
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
