<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import HeroCarousel from '@/components/HeroCarousel.vue';
import SearchBar from '@/components/SearchBar.vue';
import ProductCard from '@/components/ProductCard.vue';
import NewsletterSignup from '@/components/NewsletterSignup.vue';
import { Button } from '@/components/ui/button';
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
    carouselSlides?: Array<{ title: string; description: string; link: string; image: string }>;
    carouselProducts?: Array<{
        id: number;
        title: string;
        slug: string;
        abstract: string | null;
        images: Array<{ id: number; path: string }>;
        user: { id: number; name: string };
        faculty: { id: number; name: string } | null;
    }>;
}>();

const page = usePage();
const settings = computed(() => (page.props.settings as Record<string, string>) || {});
</script>

<template>
    <Head :title="settings.site_name || 'ProjectRim'" />

    <PublicLayout>
        <!-- Hero Carousel -->
        <HeroCarousel :slides="carouselProducts || []" :settings="settings" :carousel-slides="carouselSlides || []" />

        <!-- Search Bar Section -->
        <section class="-mt-6 relative z-10 px-4">
            <div class="mx-auto max-w-2xl">
                <div class="rounded-lg bg-background p-2 shadow-lg">
                    <SearchBar show-button placeholder="Search projects, authors, institutions..." />
                </div>
                <div class="mt-2 text-center">
                    <Link href="/search" class="text-sm text-muted-foreground hover:text-primary hover:underline">
                        Advanced Search &rarr;
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
                    <ProductCard v-for="product in featuredProducts" :key="product.id" :product="product" />
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
                    <ProductCard v-for="product in recentProducts" :key="product.id" :product="product" />
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

        <!-- Newsletter Section -->
        <section class="bg-muted/50 py-16">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-2xl font-bold">Stay Updated</h2>
                <p class="mx-auto mt-3 max-w-xl text-muted-foreground">
                    Get notified when new research projects are published. No spam, unsubscribe anytime.
                </p>
                <div class="mt-6">
                    <NewsletterSignup />
                </div>
            </div>
        </section>

        <!-- Become a Seller CTA -->
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
