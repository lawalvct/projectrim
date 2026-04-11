<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import { Button } from '@/components/ui/button';
import Autoplay from 'embla-carousel-autoplay';
import { computed, ref, onMounted } from 'vue';
import type { CarouselApi } from '@/components/ui/carousel';

interface Slide {
    id: number;
    title: string;
    slug: string;
    abstract: string | null;
    images: Array<{ id: number; path: string }>;
    user: { id: number; name: string };
    faculty?: { id: number; name: string } | null;
}

interface AdminSlide {
    title: string;
    description: string;
    link: string;
    image: string;
}

const props = defineProps<{
    slides: Slide[];
    settings?: Record<string, string>;
    carouselSlides?: AdminSlide[];
}>();

const CACHE_KEY = 'carousel_products';

const api = ref<CarouselApi>();
const current = ref(0);
const cachedProducts = ref<Slide[]>([]);

onMounted(() => {
    try {
        const stored = sessionStorage.getItem(CACHE_KEY);
        if (stored) {
            cachedProducts.value = JSON.parse(stored);
            return;
        }
    } catch {}
    cachedProducts.value = props.slides;
    try {
        sessionStorage.setItem(CACHE_KEY, JSON.stringify(props.slides));
    } catch {}
});

type UnifiedSlide =
    | { type: 'admin'; data: AdminSlide }
    | { type: 'branding' }
    | { type: 'product'; data: Slide };

function shuffle<T>(arr: T[]): T[] {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

const hasAdminSlides = computed(() => (props.carouselSlides?.length ?? 0) > 0);

const allSlides = computed<UnifiedSlide[]>(() => {
    const items: UnifiedSlide[] = [];
    if (hasAdminSlides.value) {
        for (const s of props.carouselSlides!) {
            items.push({ type: 'admin', data: s });
        }
    } else {
        items.push({ type: 'branding' });
    }
    for (const s of cachedProducts.value) {
        items.push({ type: 'product', data: s });
    }
    return shuffle(items);
});

const totalSlides = computed(() => allSlides.value.length);

function setApi(val: CarouselApi) {
    api.value = val;
    if (!val) return;
    current.value = val.selectedScrollSnap();
    val.on('select', () => {
        current.value = val.selectedScrollSnap();
    });
}

function stripHtml(html: string | null): string {
    if (!html) return '';
    const stripped = html.replace(/<[^>]*>/g, '');
    return stripped.length > 150 ? stripped.substring(0, 150) + '...' : stripped;
}
</script>

<template>
    <section class="relative overflow-hidden bg-gradient-to-br from-primary to-primary/80">
        <Carousel
            class="w-full"
            :opts="{ loop: true }"
            :plugins="[Autoplay({ delay: 5000, stopOnInteraction: true, stopOnMouseEnter: false })]"
            @init-api="setApi"
        >
            <CarouselContent>
                <CarouselItem v-for="(item, idx) in allSlides" :key="idx">
                    <!-- Admin slide -->
                    <template v-if="item.type === 'admin'">
                        <div class="relative flex min-h-[420px] items-center justify-center text-white lg:min-h-[480px]" :class="(item.data.title || item.data.description) ? 'px-4 py-16' : ''">
                            <div
                                v-if="item.data.image"
                                class="absolute inset-0 bg-cover bg-center"
                                :style="{ backgroundImage: `url(/storage/${item.data.image})` }"
                            />
                            <div v-if="item.data.title || item.data.description" class="absolute inset-0 bg-black/50" />
                            <div v-if="item.data.title || item.data.description" class="relative mx-auto max-w-3xl text-center">
                                <h1 v-if="item.data.title" class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                                    {{ item.data.title }}
                                </h1>
                                <p v-if="item.data.description" class="mx-auto mt-4 max-w-2xl text-lg text-white/90">
                                    {{ item.data.description }}
                                </p>
                                <div v-if="item.data.link" class="mt-8">
                                    <Link :href="item.data.link">
                                        <Button size="lg" variant="secondary" class="text-primary">
                                            Learn More
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Default branding slide -->
                    <template v-else-if="item.type === 'branding'">
                        <div class="flex min-h-[420px] items-center justify-center px-4 py-16 text-white lg:min-h-[480px]">
                            <div class="mx-auto max-w-3xl text-center">
                                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                                    {{ settings?.site_name || 'ProjectRim' }}
                                </h1>
                                <p class="mx-auto mt-4 max-w-2xl text-lg text-white/90">
                                    {{ settings?.site_description || 'Your trusted marketplace for research papers, projects, and academic materials.' }}
                                </p>
                                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                                    <Link href="/products">
                                        <Button size="lg" variant="secondary" class="gap-2 text-primary">
                                            Browse Projects
                                        </Button>
                                    </Link>
                                    <Link href="/search">
                                        <Button size="lg" variant="outline" class="gap-2 border-white text-white hover:bg-white hover:text-primary">
                                            Advanced Search
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Featured product slide -->
                    <template v-else-if="item.type === 'product'">
                        <div class="relative flex min-h-[420px] items-center px-4 py-16 text-white lg:min-h-[480px]">
                            <div
                                class="absolute inset-0 bg-cover bg-center opacity-20"
                                :style="{ backgroundImage: item.data.images?.length ? `url(/storage/${item.data.images[0].path})` : `url(/storage/products/images/projectrim_cover_page.png)` }"
                            />
                            <div class="relative mx-auto grid max-w-6xl gap-8 lg:grid-cols-2">
                                <div class="flex flex-col justify-center">
                                    <span v-if="item.data.faculty" class="mb-2 text-sm font-medium text-white/70">
                                        {{ item.data.faculty.name }}
                                    </span>
                                    <h2 class="text-3xl font-bold sm:text-4xl">{{ item.data.title }}</h2>
                                    <p class="mt-3 text-white/80">{{ stripHtml(item.data.abstract) }}</p>
                                    <p class="mt-2 text-sm text-white/60">by {{ item.data.user.name }}</p>
                                    <div class="mt-6">
                                        <Link :href="`/products/${item.data.slug}`">
                                            <Button
                                                size="lg"
                                                variant="secondary"
                                                class="bg-white font-semibold text-primary shadow-lg hover:bg-white/95"
                                            >
                                                View Project
                                            </Button>
                                        </Link>
                                    </div>
                                </div>
                                <div class="hidden overflow-hidden rounded-lg lg:block">
                                    <img
                                        :src="item.data.images?.length ? `/storage/${item.data.images[0].path}` : '/storage/products/images/projectrim_cover_page.png'"
                                        :alt="item.data.title"
                                        class="h-full max-h-[320px] w-full object-cover"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </CarouselItem>
            </CarouselContent>

            <CarouselPrevious class="absolute left-4 top-1/2 -translate-y-1/2 border-white/30 bg-white/10 text-white hover:bg-white/20" />
            <CarouselNext class="absolute right-4 top-1/2 -translate-y-1/2 border-white/30 bg-white/10 text-white hover:bg-white/20" />

            <!-- Dots -->
            <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
                <button
                    v-for="(_, idx) in totalSlides"
                    :key="idx"
                    class="h-2 w-2 rounded-full transition-colors"
                    :class="idx === current ? 'bg-white' : 'bg-white/40'"
                    @click="api?.scrollTo(idx)"
                />
            </div>
        </Carousel>
    </section>
</template>
