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
import { computed, ref } from 'vue';
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

const api = ref<CarouselApi>();
const current = ref(0);

const hasAdminSlides = computed(() => (props.carouselSlides?.length ?? 0) > 0);
const totalSlides = computed(() => {
    const adminCount = hasAdminSlides.value ? props.carouselSlides!.length : 1; // 1 = default branding slide
    return adminCount + Math.min(props.slides.length, 4);
});

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
                <!-- Admin carousel slides (from Settings) -->
                <CarouselItem v-for="(aSlide, idx) in carouselSlides" :key="'admin-' + idx">
                    <div class="relative flex min-h-[420px] items-center justify-center text-white lg:min-h-[480px]" :class="(aSlide.title || aSlide.description) ? 'px-4 py-16' : ''">
                        <div
                            v-if="aSlide.image"
                            class="absolute inset-0 bg-cover bg-center"
                            :style="{ backgroundImage: `url(/storage/${aSlide.image})` }"
                        />
                        <!-- Dark overlay only when text content is present -->
                        <div v-if="aSlide.title || aSlide.description" class="absolute inset-0 bg-black/50" />
                        <!-- Text content only when title or description is provided -->
                        <div v-if="aSlide.title || aSlide.description" class="relative mx-auto max-w-3xl text-center">
                            <h1 v-if="aSlide.title" class="text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                                {{ aSlide.title }}
                            </h1>
                            <p v-if="aSlide.description" class="mx-auto mt-4 max-w-2xl text-lg text-white/90">
                                {{ aSlide.description }}
                            </p>
                            <div v-if="aSlide.link" class="mt-8">
                                <Link :href="aSlide.link">
                                    <Button size="lg" variant="secondary" class="text-primary">
                                        Learn More
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </CarouselItem>

                <!-- Default branding slide (only if no admin slides) -->
                <CarouselItem v-if="!hasAdminSlides">
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
                </CarouselItem>

                <!-- Featured product slides -->
                <CarouselItem v-for="slide in slides.slice(0, 4)" :key="slide.id">
                    <div class="relative flex min-h-[420px] items-center px-4 py-16 text-white lg:min-h-[480px]">
                        <div
                            v-if="slide.images?.length"
                            class="absolute inset-0 bg-cover bg-center opacity-20"
                            :style="{ backgroundImage: `url(/storage/${slide.images[0].path})` }"
                        />
                        <div class="relative mx-auto grid max-w-6xl gap-8 lg:grid-cols-2">
                            <div class="flex flex-col justify-center">
                                <span v-if="slide.faculty" class="mb-2 text-sm font-medium text-white/70">
                                    {{ slide.faculty.name }}
                                </span>
                                <h2 class="text-3xl font-bold sm:text-4xl">{{ slide.title }}</h2>
                                <p class="mt-3 text-white/80">{{ stripHtml(slide.abstract) }}</p>
                                <p class="mt-2 text-sm text-white/60">by {{ slide.user.name }}</p>
                                <div class="mt-6">
                                    <Link :href="`/products/${slide.slug}`">
                                        <Button size="lg" variant="secondary" class="text-primary">
                                            View Project
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                            <div v-if="slide.images?.length" class="hidden overflow-hidden rounded-lg lg:block">
                                <img
                                    :src="`/storage/${slide.images[0].path}`"
                                    :alt="slide.title"
                                    class="h-full max-h-[320px] w-full object-cover"
                                />
                            </div>
                        </div>
                    </div>
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
