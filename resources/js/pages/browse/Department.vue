<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';

const props = defineProps<{
    department: {
        id: number;
        name: string;
        slug: string;
        faculty: { id: number; name: string; slug: string } | null;
    };
    products: any;
}>();
</script>

<template>
    <Head :title="department.name" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <nav class="mb-6 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-primary">Home</Link>
                <span class="mx-2">/</span>
                <Link href="/products" class="hover:text-primary">Browse</Link>
                <span v-if="department.faculty" class="mx-2">/</span>
                <Link v-if="department.faculty" :href="`/faculty/${department.faculty.slug}`" class="hover:text-primary">
                    {{ department.faculty.name }}
                </Link>
                <span class="mx-2">/</span>
                <span>{{ department.name }}</span>
            </nav>

            <h1 class="mb-2 text-3xl font-bold">{{ department.name }}</h1>
            <p class="mb-8 text-muted-foreground">
                Browse all projects in {{ department.name }}
                <span v-if="department.faculty">under {{ department.faculty.name }}</span>.
            </p>

            <div class="mb-4 text-sm text-muted-foreground">
                {{ products.total }} project{{ products.total !== 1 ? 's' : '' }}
            </div>

            <ProductGrid :products="products" :empty-message="`No projects found in ${department.name}.`" />
        </div>
    </PublicLayout>
</template>
