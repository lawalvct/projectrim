<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Ban } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    orderNumber: string;
}>();
</script>

<template>
    <Head title="Payment Cancelled" />

    <PublicLayout>
        <div class="container mx-auto flex flex-col items-center px-4 py-16">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                <Ban class="h-8 w-8 text-yellow-600" />
            </div>

            <h1 class="mt-4 text-2xl font-bold">Payment Cancelled</h1>
            <p class="mt-2 text-muted-foreground">
                You cancelled the payment for order <strong class="font-mono">{{ orderNumber }}</strong>.
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                Your cart items are still saved. You can complete checkout whenever you're ready.
            </p>

            <div class="mt-6 flex gap-3">
                <Link href="/cart">
                    <Button>Back to Cart</Button>
                </Link>
                <Link href="/products">
                    <Button variant="outline">Continue Browsing</Button>
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
