<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    products: {
        data: Array<{
            id: number;
            title: string;
            slug: string;
            status: string;
            price: number;
            is_paid: boolean;
            views_count: number;
            downloads_count: number;
            created_at: string;
            faculty: { id: number; name: string } | null;
            images: Array<{ id: number; path: string }>;
            downloads_count_aggregate?: number;
            reviews_count?: number;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: '/dashboard/seller/products' },
];

function statusColor(status: string) {
    switch (status) {
        case 'published': return 'default';
        case 'pending': return 'secondary';
        case 'rejected': return 'destructive';
        default: return 'outline';
    }
}

function deleteProduct(id: number) {
    if (confirm('Are you sure you want to delete this product?')) {
        router.delete(`/dashboard/seller/products/${id}`);
    }
}
</script>

<template>
    <Head title="My Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 sm:p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold">My Products</h1>
                <Link href="/dashboard/seller/products/create">
                    <Button class="gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New Product
                    </Button>
                </Link>
            </div>

            <div v-if="!products.data.length" class="rounded-lg border border-dashed p-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-muted-foreground/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-4 text-muted-foreground">No products yet</p>
                <Link href="/dashboard/seller/products/create">
                    <Button class="mt-4">Create Your First Product</Button>
                </Link>
            </div>

            <div v-else class="space-y-4">
                <Card v-for="product in products.data" :key="product.id" class="overflow-hidden">
                    <CardContent class="flex gap-4 p-4">
                        <!-- Thumbnail -->
                        <div class="h-20 w-20 flex-shrink-0 rounded-lg bg-muted">
                            <img
                                v-if="product.images?.length"
                                :src="`/storage/${product.images[0].path}`"
                                :alt="product.title"
                                class="h-full w-full rounded-lg object-cover"
                            />
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-semibold truncate">{{ product.title }}</h3>
                                    <p v-if="product.faculty" class="text-xs text-muted-foreground">{{ product.faculty.name }}</p>
                                </div>
                                <Badge :variant="statusColor(product.status)">{{ product.status }}</Badge>
                            </div>
                            <div class="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                                <span>{{ product.is_paid ? `$${product.price}` : 'Free' }}</span>
                                <span>{{ product.views_count }} views</span>
                                <span>{{ product.downloads_count }} downloads</span>
                            </div>
                            <div class="mt-2 flex gap-2">
                                <Link :href="`/dashboard/seller/products/${product.id}/edit`">
                                    <Button variant="outline" size="sm">Edit</Button>
                                </Link>
                                <Button variant="ghost" size="sm" class="text-destructive" @click="deleteProduct(product.id)">Delete</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <div v-if="products.last_page > 1" class="flex justify-center gap-1 pt-4">
                    <template v-for="link in products.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded border px-3 py-1 text-sm"
                            :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            v-html="link.label"
                        />
                        <span v-else class="rounded border px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
