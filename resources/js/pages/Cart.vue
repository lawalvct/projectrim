<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ShoppingCart, Trash2, ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

interface CartItemData {
    id: number;
    price: number;
    product: {
        id: number;
        title: string;
        slug: string;
        price: number;
        is_paid: boolean;
        image: string | null;
        author: string;
    };
}

const props = defineProps<{
    items: CartItemData[];
    total: number;
}>();

const page = usePage();
const settings = computed(() => (page.props.settings as Record<string, string>) || {});
const currencySymbol = computed(() => settings.value.currency_symbol || '$');
const fmt = (n: number) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const removeItem = (itemId: number) => {
    router.delete(`/cart/${itemId}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Shopping Cart" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <h1 class="mb-6 text-2xl font-bold">Shopping Cart</h1>

            <div v-if="items.length" class="grid gap-8 lg:grid-cols-3">
                <!-- Cart Items -->
                <div class="lg:col-span-2 space-y-4">
                    <Card v-for="item in items" :key="item.id">
                        <CardContent class="flex items-center gap-4 p-4">
                            <!-- Product Image -->
                            <Link :href="`/products/${item.product.slug}`" class="shrink-0">
                                <div class="h-20 w-20 overflow-hidden rounded-lg bg-muted">
                                    <img
                                        v-if="item.product.image"
                                        :src="`/storage/${item.product.image}`"
                                        :alt="item.product.title"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full items-center justify-center text-muted-foreground">
                                        <ShoppingCart class="h-6 w-6" />
                                    </div>
                                </div>
                            </Link>

                            <!-- Product Details -->
                            <div class="min-w-0 flex-1">
                                <Link :href="`/products/${item.product.slug}`" class="text-sm font-semibold hover:text-primary line-clamp-2">
                                    {{ item.product.title }}
                                </Link>
                                <p class="mt-0.5 text-xs text-muted-foreground">by {{ item.product.author }}</p>
                            </div>

                            <!-- Price -->
                            <div class="text-right">
                                <div class="text-sm font-bold">{{ currencySymbol }}{{ fmt(item.price) }}</div>
                            </div>

                            <!-- Remove -->
                            <Button variant="ghost" size="icon" class="shrink-0 text-red-500 hover:text-red-700" @click="removeItem(item.id)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Order Summary -->
                <div>
                    <Card>
                        <CardContent class="p-6">
                            <h2 class="text-lg font-semibold">Order Summary</h2>

                            <Separator class="my-4" />

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Items ({{ items.length }})</span>
                                    <span>{{ currencySymbol }}{{ fmt(total) }}</span>
                                </div>
                            </div>

                            <Separator class="my-4" />

                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span>{{ currencySymbol }}{{ fmt(total) }}</span>
                            </div>

                            <Link href="/checkout" class="mt-6 block">
                                <Button class="w-full" size="lg">
                                    Proceed to Checkout
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </Link>

                            <Link href="/products" class="mt-3 block text-center text-sm text-muted-foreground hover:text-primary">
                                Continue Browsing
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Empty Cart -->
            <div v-else class="flex flex-col items-center gap-4 py-20">
                <ShoppingCart class="h-16 w-16 text-muted-foreground/30" />
                <h2 class="text-xl font-semibold">Your cart is empty</h2>
                <p class="text-muted-foreground">Browse our projects and add some to your cart.</p>
                <Link href="/products">
                    <Button>Browse Projects</Button>
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
