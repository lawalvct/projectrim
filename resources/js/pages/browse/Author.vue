<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';

const props = defineProps<{
    author: { id: number; name: string; email: string; avatar: string | null };
    products: any;
}>();
</script>

<template>
    <Head :title="`${author.name}'s Projects`" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <nav class="mb-6 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-primary">Home</Link>
                <span class="mx-2">/</span>
                <Link href="/products" class="hover:text-primary">Browse</Link>
                <span class="mx-2">/</span>
                <span>{{ author.name }}</span>
            </nav>

            <div class="mb-8 flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary text-2xl font-bold text-white">
                    {{ author.name.charAt(0) }}
                </div>
                <div>
                    <h1 class="text-3xl font-bold">{{ author.name }}</h1>
                    <p class="text-muted-foreground">Researcher & Author</p>
                </div>
            </div>

            <div class="mb-4 text-sm text-muted-foreground">
                {{ products.total }} project{{ products.total !== 1 ? 's' : '' }}
            </div>

            <ProductGrid :products="products" :empty-message="`${author.name} has no published projects yet.`" />
        </div>
    </PublicLayout>
</template>
