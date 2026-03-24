<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ProductCard from '@/components/ProductCard.vue';

interface PaginatedProducts {
    data: Array<{
        id: number;
        title: string;
        slug: string;
        price?: number;
        is_paid?: boolean;
        downloads_count?: number;
        views_count?: number;
        faculty?: { id: number; name: string; slug?: string } | null;
        images?: Array<{ id: number; path: string }>;
        user?: { id: number; name: string };
    }>;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    total: number;
}

defineProps<{
    products: PaginatedProducts;
    emptyMessage?: string;
}>();
</script>

<template>
    <div>
        <div v-if="products.data.length" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <ProductCard v-for="product in products.data" :key="product.id" :product="product" />
        </div>
        <div v-else class="py-16 text-center text-muted-foreground">
            {{ emptyMessage || 'No projects found.' }}
        </div>

        <!-- Pagination -->
        <nav v-if="products.last_page > 1" class="mt-8 flex items-center justify-center gap-1">
            <template v-for="link in products.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm transition-colors"
                    :class="link.active ? 'border-primary bg-primary text-white' : 'hover:bg-muted'"
                    preserve-scroll
                    v-html="link.label"
                />
                <span
                    v-else
                    class="inline-flex h-9 min-w-9 items-center justify-center text-sm text-muted-foreground"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
