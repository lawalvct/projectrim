<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    tag: { id: number; name: string; slug: string };
    products: any;
}>();
</script>

<template>
    <Head :title="`Tag: ${tag.name}`" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <nav class="mb-6 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-primary">Home</Link>
                <span class="mx-2">/</span>
                <Link href="/products" class="hover:text-primary">Browse</Link>
                <span class="mx-2">/</span>
                <span>Tag: {{ tag.name }}</span>
            </nav>

            <div class="mb-8 flex items-center gap-3">
                <h1 class="text-3xl font-bold">Projects tagged</h1>
                <Badge variant="secondary" class="text-lg px-3 py-1">{{ tag.name }}</Badge>
            </div>

            <div class="mb-4 text-sm text-muted-foreground">
                {{ products.total }} project{{ products.total !== 1 ? 's' : '' }}
            </div>

            <ProductGrid :products="products" :empty-message="`No projects found with tag '${tag.name}'.`" />
        </div>
    </PublicLayout>
</template>
