<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

const props = defineProps<{
    canRegister: boolean;
    featuredProducts?: Array<{
        id: number;
        title: string;
        slug: string;
        abstract: string | null;
        price: number;
        is_paid: boolean;
        views_count: number;
        downloads_count: number;
        institution: string | null;
        faculty: { id: number; name: string } | null;
        images: Array<{ id: number; path: string }>;
        user: { id: number; name: string };
    }>;
    recentProducts?: Array<{
        id: number;
        title: string;
        slug: string;
        abstract: string | null;
        price: number;
        is_paid: boolean;
        views_count: number;
        downloads_count: number;
        institution: string | null;
        faculty: { id: number; name: string } | null;
        images: Array<{ id: number; path: string }>;
        user: { id: number; name: string };
    }>;
    faculties?: Array<{ id: number; name: string; slug: string; products_count: number }>;
    stats?: { products: number; authors: number; downloads: number };
}>();

const page = usePage();
const settings = computed(() => (page.props.settings as Record<string, string>) || {});

function truncate(text: string | null, length: number): string {
    if (!text) return '';
    const stripped = text.replace(/<[^>]*>/g, '');
    return stripped.length > length ? stripped.substring(0, length) + '...' : stripped;
}
</script>

<template>
    <Head :title="settings.site_name || 'ProjectRim'" />

    <PublicLayout>
        <!-- Hero Section -->
        <section class="bg-gradient-to-br from-primary to-primary/80 py-20 text-white">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    {{ settings.site_name || 'ProjectRim' }}
                </h1>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-white/90">
                    {{ settings.site_description || 'Your trusted marketplace for research papers, projects, and academic materials. Browse, buy, or download for free.' }}
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <Link href="/products">
                        <Button size="lg" variant="secondary" class="gap-2 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Browse Projects
                        </Button>
                    </Link>
                    <Link v-if="!$page.props.auth?.user" href="/register">
                        <Button size="lg" variant="outline" class="gap-2 border-white text-white hover:bg-white hover:text-primary">
                            Get Started Free
                        </Button>
                    </Link>
                    <Link v-else href="/dashboard">
                        <Button size="lg" variant="outline" class="gap-2 border-white text-white hover:bg-white hover:text-primary">
                            Go to Dashboard
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section v-if="stats" class="border-b bg-muted/50 py-12">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-3">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary">{{ stats.products.toLocaleString() }}</div>
                        <div class="mt-1 text-sm text-muted-foreground">Research Projects</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary">{{ stats.authors.toLocaleString() }}</div>
                        <div class="mt-1 text-sm text-muted-foreground">Authors</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary">{{ stats.downloads.toLocaleString() }}</div>
                        <div class="mt-1 text-sm text-muted-foreground">Downloads</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section v-if="featuredProducts && featuredProducts.length" class="py-16">
            <div class="container mx-auto px-4">
                <div class="mb-8 flex items-center justify-between">
                    <h2 class="text-2xl font-bold">Featured Projects</h2>
                    <Link href="/products" class="text-sm font-medium text-primary hover:underline">View All &rarr;</Link>
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Link v-for="product in featuredProducts" :key="product.id" :href="`/products/${product.slug}`" class="group">
                        <Card class="h-full overflow-hidden transition-shadow hover:shadow-lg">
                            <div class="aspect-[4/3] bg-muted">
                                <img
                                    v-if="product.images?.length"
                                    :src="`/storage/${product.images[0].path}`"
                                    :alt="product.title"
                                    class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                />
                                <div v-else class="flex h-full items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-muted-foreground/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <CardContent class="p-4">
                                <h3 class="line-clamp-2 text-sm font-semibold group-hover:text-primary">{{ product.title }}</h3>
                                <p v-if="product.faculty" class="mt-1 text-xs text-muted-foreground">{{ product.faculty.name }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">by {{ product.user.name }}</p>
                                <div class="mt-3 flex items-center justify-between">
                                    <Badge v-if="product.is_paid" variant="secondary">
                                        {{ settings.currency_symbol || '$' }}{{ product.price }}
                                    </Badge>
                                    <Badge v-else variant="outline" class="text-green-600">Free</Badge>
                                    <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        {{ product.downloads_count }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Recent Products -->
        <section v-if="recentProducts && recentProducts.length" class="bg-muted/30 py-16">
            <div class="container mx-auto px-4">
                <div class="mb-8 flex items-center justify-between">
                    <h2 class="text-2xl font-bold">Recently Added</h2>
                    <Link href="/products" class="text-sm font-medium text-primary hover:underline">View All &rarr;</Link>
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Link v-for="product in recentProducts" :key="product.id" :href="`/products/${product.slug}`" class="group">
                        <Card class="h-full overflow-hidden transition-shadow hover:shadow-lg">
                            <div class="aspect-[4/3] bg-muted">
                                <img
                                    v-if="product.images?.length"
                                    :src="`/storage/${product.images[0].path}`"
                                    :alt="product.title"
                                    class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                />
                                <div v-else class="flex h-full items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-muted-foreground/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <CardContent class="p-4">
                                <h3 class="line-clamp-2 text-sm font-semibold group-hover:text-primary">{{ product.title }}</h3>
                                <p v-if="product.faculty" class="mt-1 text-xs text-muted-foreground">{{ product.faculty.name }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">by {{ product.user.name }}</p>
                                <div class="mt-3 flex items-center justify-between">
                                    <Badge v-if="product.is_paid" variant="secondary">
                                        {{ settings.currency_symbol || '$' }}{{ product.price }}
                                    </Badge>
                                    <Badge v-else variant="outline" class="text-green-600">Free</Badge>
                                    <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        {{ product.downloads_count }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Browse by Faculty -->
        <section v-if="faculties && faculties.length" class="py-16">
            <div class="container mx-auto px-4">
                <h2 class="mb-8 text-2xl font-bold">Browse by Faculty</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    <Link
                        v-for="faculty in faculties"
                        :key="faculty.id"
                        :href="`/faculty/${faculty.slug}`"
                        class="rounded-lg border bg-card p-4 text-center transition-colors hover:border-primary hover:bg-primary/5"
                    >
                        <div class="text-sm font-medium">{{ faculty.name }}</div>
                        <div class="mt-1 text-xs text-muted-foreground">{{ faculty.products_count }} projects</div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="bg-primary/5 py-16">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-2xl font-bold">Share Your Research with the World</h2>
                <p class="mx-auto mt-3 max-w-xl text-muted-foreground">
                    Join thousands of researchers and authors. Upload your projects, reach a global audience, and earn from your work.
                </p>
                <div class="mt-6">
                    <Link v-if="!$page.props.auth?.user" href="/register">
                        <Button size="lg">Create Your Account</Button>
                    </Link>
                    <Link v-else href="/dashboard/apply-seller">
                        <Button size="lg">Become a Seller</Button>
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
