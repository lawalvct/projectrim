<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';

const props = defineProps<{
    faculty: { id: number; name: string; slug: string };
    products: any;
    departments: Array<{ id: number; name: string; slug: string; products_count: number }>;
}>();
</script>

<template>
    <Head :title="faculty.name" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Breadcrumb -->
            <nav class="mb-6 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-primary">Home</Link>
                <span class="mx-2">/</span>
                <Link href="/products" class="hover:text-primary">Browse</Link>
                <span class="mx-2">/</span>
                <span>{{ faculty.name }}</span>
            </nav>

            <h1 class="mb-2 text-3xl font-bold">{{ faculty.name }}</h1>
            <p class="mb-8 text-muted-foreground">
                Browse all projects in the {{ faculty.name }} faculty.
            </p>

            <!-- Departments -->
            <div v-if="departments.length" class="mb-8 flex flex-wrap gap-2">
                <Link
                    v-for="dept in departments"
                    :key="dept.id"
                    :href="`/department/${dept.slug}`"
                    class="rounded-full border px-3 py-1 text-sm transition-colors hover:border-primary hover:text-primary"
                >
                    {{ dept.name }} ({{ dept.products_count }})
                </Link>
            </div>

            <div class="mb-4 text-sm text-muted-foreground">
                {{ products.total }} project{{ products.total !== 1 ? 's' : '' }}
            </div>

            <ProductGrid :products="products" :empty-message="`No projects found in ${faculty.name}.`" />
        </div>
    </PublicLayout>
</template>
