<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';

const props = defineProps<{
    country: string;
    products: any;
}>();
</script>

<template>
    <Head :title="`Projects from ${country}`" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <nav class="mb-6 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-primary">Home</Link>
                <span class="mx-2">/</span>
                <Link href="/products" class="hover:text-primary">Browse</Link>
                <span class="mx-2">/</span>
                <span>{{ country }}</span>
            </nav>

            <h1 class="mb-2 text-3xl font-bold">Projects from {{ country }}</h1>
            <p class="mb-8 text-muted-foreground">
                Browse all academic projects from {{ country }}.
            </p>

            <div class="mb-4 text-sm text-muted-foreground">
                {{ products.total }} project{{ products.total !== 1 ? 's' : '' }}
            </div>

            <ProductGrid :products="products" :empty-message="`No projects found from ${country}.`" />
        </div>
    </PublicLayout>
</template>
