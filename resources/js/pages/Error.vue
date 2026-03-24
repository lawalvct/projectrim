<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import SearchBar from '@/components/SearchBar.vue';
import { Button } from '@/components/ui/button';
import { computed } from 'vue';

const props = defineProps<{
    status: number;
}>();

const page = usePage();

const title = computed(() => {
    const titles: Record<number, string> = {
        404: 'Page Not Found',
        403: 'Access Denied',
        500: 'Server Error',
        503: 'Service Unavailable',
    };
    return titles[props.status] || 'Error';
});

const description = computed(() => {
    const descriptions: Record<number, string> = {
        404: "The page you're looking for doesn't exist or has been moved. Try searching for what you need.",
        403: "You don't have permission to access this resource.",
        500: 'Something went wrong on our end. Please try again later.',
        503: "We're temporarily offline for maintenance. Please check back soon.",
    };
    return descriptions[props.status] || 'An unexpected error occurred.';
});
</script>

<template>
    <Head :title="title" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-20">
            <div class="mx-auto max-w-lg text-center">
                <!-- Error Code -->
                <div class="text-8xl font-bold text-primary/20">{{ status }}</div>

                <!-- Logo -->
                <div class="mt-4">
                    <img src="/images/logo.png" alt="ProjectRim" class="mx-auto h-10" />
                </div>

                <!-- Message -->
                <h1 class="mt-6 text-2xl font-bold">{{ title }}</h1>
                <p class="mt-3 text-muted-foreground">{{ description }}</p>

                <!-- Search -->
                <div v-if="status === 404" class="mt-8">
                    <SearchBar show-button placeholder="Search for projects..." />
                </div>

                <!-- Actions -->
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link href="/">
                        <Button>Go Home</Button>
                    </Link>
                    <Link href="/products">
                        <Button variant="outline">Browse Projects</Button>
                    </Link>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
