<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Download, Package, ExternalLink } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { computed } from 'vue';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const settings = computed(() => (page.props.settings as Record<string, string>) || {});

function triggerSmartLink() {
    const enabled = settings.value.smart_link_enabled === '1';
    const url = settings.value.smart_link_url;
    if (!enabled || !url) return;
    window.open(url, '_blank', 'noopener,noreferrer');
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'My Downloads', href: '/dashboard/downloads' },
];

defineProps<{
    downloads: {
        data: Array<{
            id: number;
            product: {
                id: number;
                title: string;
                slug: string;
                price: number;
                is_paid: boolean;
                faculty: { id: number; name: string } | null;
                author_name: string | null;
                image: string | null;
            } | null;
            created_at: string;
            created_at_diff: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
}>();
</script>

<template>
    <Head title="My Downloads" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">My Downloads</h1>
                    <p class="text-sm text-muted-foreground">{{ downloads.total }} total download(s)</p>
                </div>
            </div>

            <Card>
                <CardContent class="p-0">
                    <div v-if="downloads.data.length">
                        <!-- Table header -->
                        <div class="hidden border-b bg-muted/50 px-6 py-3 md:grid md:grid-cols-12 md:gap-4">
                            <span class="col-span-4 text-xs font-medium uppercase text-muted-foreground">Product</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Author</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Faculty</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground">Downloaded</span>
                            <span class="col-span-2 text-xs font-medium uppercase text-muted-foreground text-right">Action</span>
                        </div>

                        <!-- Rows -->
                        <div v-for="dl in downloads.data" :key="dl.id" class="border-b last:border-0">
                            <div class="grid items-center gap-4 px-6 py-4 md:grid-cols-12">
                                <!-- Product -->
                                <div class="col-span-4 flex items-center gap-3">
                                    <div class="hidden h-12 w-12 flex-shrink-0 overflow-hidden rounded-lg bg-muted md:block">
                                        <img v-if="dl.product?.image" :src="`/storage/${dl.product.image}`" :alt="dl.product?.title" class="h-full w-full object-cover" />
                                        <div v-else class="flex h-full w-full items-center justify-center">
                                            <Package class="h-5 w-5 text-muted-foreground/40" />
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <Link v-if="dl.product" :href="`/products/${dl.product.slug}`" class="text-sm font-medium hover:text-primary line-clamp-1">
                                            {{ dl.product.title }}
                                        </Link>
                                        <span v-else class="text-sm text-muted-foreground italic">Product removed</span>
                                        <div v-if="dl.product?.is_paid" class="mt-0.5">
                                            <Badge variant="secondary" class="text-xs">Paid</Badge>
                                        </div>
                                        <div v-else class="mt-0.5">
                                            <Badge variant="outline" class="text-xs text-green-600">Free</Badge>
                                        </div>
                                    </div>
                                </div>

                                <!-- Author -->
                                <div class="col-span-2">
                                    <span v-if="dl.product?.author_name" class="text-sm text-muted-foreground">{{ dl.product.author_name }}</span>
                                    <span v-else class="text-sm text-muted-foreground">—</span>
                                </div>

                                <!-- Faculty -->
                                <div class="col-span-2">
                                    <span v-if="dl.product?.faculty" class="text-sm text-muted-foreground">{{ dl.product.faculty.name }}</span>
                                    <span v-else class="text-sm text-muted-foreground">—</span>
                                </div>

                                <!-- Date -->
                                <div class="col-span-2">
                                    <span class="text-sm text-muted-foreground" :title="dl.created_at">{{ dl.created_at_diff }}</span>
                                </div>

                                <!-- Action -->
                                <div class="col-span-2 text-right">
                                    <Link v-if="dl.product" :href="`/products/${dl.product.slug}`" class="inline-flex items-center gap-1 text-sm text-primary hover:underline" @click="triggerSmartLink">
                                        View <ExternalLink class="h-3 w-3" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-center gap-3 py-16">
                        <Download class="h-12 w-12 text-muted-foreground/30" />
                        <p class="text-muted-foreground">You haven't downloaded anything yet.</p>
                        <Link href="/products" class="text-sm text-primary hover:underline">Browse products</Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <nav v-if="downloads.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in downloads.links" :key="link.label">
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
    </AppLayout>
</template>
