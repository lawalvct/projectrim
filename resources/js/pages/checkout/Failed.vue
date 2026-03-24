<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { XCircle } from 'lucide-vue-next';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    orderNumber: string;
}>();
</script>

<template>
    <Head title="Payment Failed" />

    <PublicLayout>
        <div class="container mx-auto flex flex-col items-center px-4 py-16">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <XCircle class="h-8 w-8 text-red-600" />
            </div>

            <h1 class="mt-4 text-2xl font-bold">Payment Failed</h1>
            <p class="mt-2 text-muted-foreground">
                We were unable to verify your payment for order <strong class="font-mono">{{ orderNumber }}</strong>.
            </p>
            <p class="mt-1 text-sm text-muted-foreground">
                Your cart items are still saved. You can try again or choose a different payment method.
            </p>

            <div class="mt-6 flex gap-3">
                <Link href="/cart">
                    <Button>Try Again</Button>
                </Link>
                <Link href="/products">
                    <Button variant="outline">Browse Projects</Button>
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
