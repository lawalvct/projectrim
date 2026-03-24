<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircle, Download } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface OrderItemData {
    product_title: string;
    product_slug: string;
    price: number;
}

const props = defineProps<{
    order: {
        order_number: string;
        status: string;
        total: number;
        payment_gateway: string;
        paid_at: string | null;
        items: OrderItemData[];
    };
}>();

const page = usePage();
const settings = computed(() => (page.props.settings as Record<string, string>) || {});
const currencySymbol = computed(() => settings.value.currency_symbol || '$');
const fmt = (n: number) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Order Successful" />

    <PublicLayout>
        <div class="container mx-auto flex flex-col items-center px-4 py-16">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                <CheckCircle class="h-8 w-8 text-green-600" />
            </div>

            <h1 class="mt-4 text-2xl font-bold">
                {{ order.status === 'completed' ? 'Payment Successful!' : 'Order Placed!' }}
            </h1>

            <p class="mt-2 text-muted-foreground">
                <template v-if="order.status === 'completed'">
                    Your payment has been confirmed and your downloads are ready.
                </template>
                <template v-else>
                    Your order has been placed. Please complete the bank transfer to finalize.
                </template>
            </p>

            <Card class="mt-8 w-full max-w-lg">
                <CardContent class="p-6 space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Order Number</span>
                        <span class="font-mono font-semibold">{{ order.order_number }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Status</span>
                        <span class="font-medium capitalize">{{ order.status }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Payment</span>
                        <span class="font-medium capitalize">{{ order.payment_gateway.replace('_', ' ') }}</span>
                    </div>
                    <div v-if="order.paid_at" class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Paid At</span>
                        <span>{{ order.paid_at }}</span>
                    </div>

                    <hr />

                    <div v-for="(item, idx) in order.items" :key="idx" class="flex justify-between text-sm">
                        <Link :href="`/products/${item.product_slug}`" class="hover:text-primary line-clamp-1 flex-1">
                            {{ item.product_title }}
                        </Link>
                        <span class="ml-4 font-medium">{{ currencySymbol }}{{ fmt(item.price) }}</span>
                    </div>

                    <hr />

                    <div class="flex justify-between font-bold">
                        <span>Total</span>
                        <span>{{ currencySymbol }}{{ fmt(order.total) }}</span>
                    </div>
                </CardContent>
            </Card>

            <div class="mt-6 flex gap-3">
                <Link v-if="order.status === 'completed'" href="/dashboard/downloads">
                    <Button>
                        <Download class="mr-2 h-4 w-4" /> My Downloads
                    </Button>
                </Link>
                <Link href="/dashboard/orders">
                    <Button variant="outline">View Orders</Button>
                </Link>
                <Link href="/products">
                    <Button variant="ghost">Continue Browsing</Button>
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
