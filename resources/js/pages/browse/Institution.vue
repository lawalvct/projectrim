<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';

const props = defineProps<{
    institution: string;
    products: any;
}>();
</script>

<template>
    <Head :title="institution" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <nav class="mb-6 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-primary">Home</Link>
                <span class="mx-2">/</span>
                <Link href="/products" class="hover:text-primary">Browse</Link>
                <span class="mx-2">/</span>
                <span>{{ institution }}</span>
            </nav>

            <h1 class="mb-2 text-3xl font-bold">{{ institution }}</h1>
            <p class="mb-8 text-muted-foreground">
                Browse all projects from {{ institution }}.
            </p>

            <div class="mb-4 text-sm text-muted-foreground">
                {{ products.total }} project{{ products.total !== 1 ? 's' : '' }}
            </div>

            <ProductGrid :products="products" :empty-message="`No projects found from ${institution}.`" />
        </div>
    </PublicLayout>
</template>
