<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogClose, DialogFooter } from '@/components/ui/dialog';
import type { BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import axios from 'axios';

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

function togglePublication(id: number, status: string) {
    const action = status === 'published' ? 'unpublish' : 'publish';

    if (confirm(`Are you sure you want to ${action} this product?`)) {
        router.patch(`/dashboard/seller/products/${id}/toggle-publication`);
    }
}

// --- Downloads dialog ---
const downloadsOpen = ref(false);
const downloadsLoading = ref(false);
const downloadsProductTitle = ref('');
const downloadsList = ref<Array<{ id: number; user_name: string; user_email: string; downloaded_at: string }>>([]);

async function showDownloads(productId: number, title: string) {
    downloadsProductTitle.value = title;
    downloadsList.value = [];
    downloadsOpen.value = true;
    downloadsLoading.value = true;
    try {
        const { data } = await axios.get(`/dashboard/seller/products/${productId}/downloads`);
        downloadsList.value = data.downloads;
    } catch {
        downloadsList.value = [];
    } finally {
        downloadsLoading.value = false;
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
                                :src="product.images?.length ? `/storage/${product.images[0].path}` : '/storage/products/images/projectrim_cover_page.png'"
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
                                <span>{{ product.downloads_count }} <button type="button" class="underline hover:text-foreground" @click="showDownloads(product.id, product.title)">downloads</button></span>
                            </div>
                            <div class="mt-2 flex gap-2">
                                <Link :href="`/dashboard/seller/products/${product.id}/edit`">
                                    <Button variant="outline" size="sm">Edit</Button>
                                </Link>
                                <Button variant="ghost" size="sm" class="text-destructive" @click="deleteProduct(product.id)">Delete</Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="togglePublication(product.id, product.status)"
                                >
                                    {{ product.status === 'published' ? 'Unpublish' : 'Publish' }}
                                </Button>
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

        <!-- Downloads Dialog -->
        <Dialog v-model:open="downloadsOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Downloads</DialogTitle>
                    <DialogDescription>Users who downloaded "{{ downloadsProductTitle }}"</DialogDescription>
                </DialogHeader>

                <div v-if="downloadsLoading" class="py-8 text-center text-sm text-muted-foreground">Loading...</div>

                <div v-else-if="!downloadsList.length" class="py-8 text-center text-sm text-muted-foreground">No downloads yet.</div>

                <div v-else class="max-h-80 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b text-left text-xs font-medium uppercase text-muted-foreground sticky top-0 bg-background">
                            <tr>
                                <th class="px-3 py-2">User</th>
                                <th class="px-3 py-2">Email</th>
                                <th class="px-3 py-2">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="dl in downloadsList" :key="dl.id">
                                <td class="px-3 py-2 font-medium">{{ dl.user_name }}</td>
                                <td class="px-3 py-2 text-muted-foreground">{{ dl.user_email }}</td>
                                <td class="px-3 py-2 text-muted-foreground text-xs">{{ dl.downloaded_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline" size="sm">Close</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
