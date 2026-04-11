<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ShoppingCart, CreditCard, Building2, ArrowLeft } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

interface CheckoutItem {
    id: number;
    price: number;
    product: {
        id: number;
        title: string;
        slug: string;
        image: string | null;
        author: string;
    };
}

interface Gateway {
    key: string;
    name: string;
    enabled: boolean;
}

const props = defineProps<{
    items: CheckoutItem[];
    total: number;
    gateways: Gateway[];
    bankDetails: string;
}>();

const page = usePage();
const settings = computed(() => (page.props.settings as Record<string, string>) || {});
const currencySymbol = computed(() => settings.value.currency_symbol || '$');
const fmt = (n: number) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const selectedGateway = ref(props.gateways[0]?.key || '');
const processing = ref(false);

const gatewayIcon = (key: string) => {
    const icons: Record<string, string> = {
        stripe: '💳',
        paypal: '🅿️',
        paystack: '💴',
        flutterwave: '🦋',
        bank_transfer: '🏦',
    };
    return icons[key] || '💳';
};

function submitCheckout() {
    if (!selectedGateway.value) return;
    processing.value = true;
    router.post('/checkout', { gateway: selectedGateway.value }, {
        onFinish: () => { processing.value = false; },
    });
}
</script>

<template>
    <Head title="Checkout" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <Link href="/cart" class="mb-6 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary">
                <ArrowLeft class="h-4 w-4" /> Back to Cart
            </Link>

            <h1 class="mb-6 text-2xl font-bold">Checkout</h1>

            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Payment Method Selection -->
                <div class="lg:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <CreditCard class="h-5 w-5" />
                                Select Payment Method
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <label
                                v-for="gw in gateways"
                                :key="gw.key"
                                class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 transition-colors"
                                :class="selectedGateway === gw.key ? 'border-primary bg-primary/5' : 'hover:bg-muted/50'"
                            >
                                <input
                                    type="radio"
                                    :value="gw.key"
                                    v-model="selectedGateway"
                                    class="h-4 w-4 text-primary accent-[#0a4b76]"
                                />
                                <span class="text-lg">{{ gatewayIcon(gw.key) }}</span>
                                <span class="text-sm font-medium">{{ gw.name }}</span>
                            </label>

                            <div v-if="gateways.length === 0" class="py-6 text-center text-muted-foreground">
                                No payment methods are currently available. Please try again later.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Bank Transfer Details -->
                    <Card v-if="selectedGateway === 'bank_transfer' && bankDetails">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Building2 class="h-5 w-5" />
                                Bank Transfer Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="rounded-lg bg-muted p-4 text-sm whitespace-pre-line">{{ bankDetails }}</div>
                            <p class="mt-3 text-xs text-muted-foreground">
                                After placing your order, please transfer the exact amount shown and your order number to the account above. Admin will confirm your payment.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Order Summary -->
                <div>
                    <Card>
                        <CardHeader>
                            <CardTitle>Order Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-for="item in items" :key="item.id" class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 overflow-hidden rounded bg-muted">
                                        <img
                                            :src="item.product.image ? `/storage/${item.product.image}` : '/storage/products/images/projectrim_cover_page.png'"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-medium line-clamp-1">{{ item.product.title }}</p>
                                    </div>
                                    <span class="text-xs font-semibold">{{ currencySymbol }}{{ fmt(item.price) }}</span>
                                </div>
                            </div>

                            <Separator class="my-4" />

                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span>{{ currencySymbol }}{{ fmt(total) }}</span>
                            </div>

                            <Button
                                class="mt-6 w-full"
                                size="lg"
                                :disabled="!selectedGateway || processing || gateways.length === 0"
                                @click="submitCheckout"
                            >
                                {{ processing ? 'Processing...' : selectedGateway === 'bank_transfer' ? 'Place Order' : 'Pay Now' }}
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
