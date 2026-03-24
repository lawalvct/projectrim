<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

const props = defineProps<{
    page: {
        id: number;
        title: string;
        slug: string;
        body: string;
        meta_description: string | null;
        meta_keywords: string | null;
    };
}>();
</script>

<template>
    <Head :title="page.title">
        <meta v-if="page.meta_description" name="description" :content="page.meta_description" />
        <meta v-if="page.meta_keywords" name="keywords" :content="page.meta_keywords" />
    </Head>

    <PublicLayout>
        <div class="container mx-auto px-4 py-12">
            <div class="mx-auto max-w-3xl">
                <h1 class="mb-8 text-3xl font-bold">{{ page.title }}</h1>
                <div class="prose prose-sm max-w-none lg:prose-base" v-html="page.body" />
            </div>
        </div>
    </PublicLayout>
</template>
