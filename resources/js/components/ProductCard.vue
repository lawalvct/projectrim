<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

const props = defineProps<{
    product: {
        id: number;
        title: string;
        slug: string;
        price?: number;
        is_paid?: boolean;
        downloads_count?: number;
        views_count?: number;
        faculty?: { id: number; name: string; slug?: string } | null;
        images?: Array<{ id: number; path: string }>;
        user?: { id: number; name: string };
    };
}>();

const page = usePage();
const settings = computed(() => (page.props.settings as Record<string, string>) || {});
const currencySymbol = computed(() => settings.value.currency_symbol || '$');

function triggerSmartLink() {
    const enabled = settings.value.smart_link_enabled === '1';
    const url = settings.value.smart_link_url;
    if (!enabled || !url) return;
    window.open(url, '_blank', 'noopener,noreferrer');
}
</script>

<template>
    <Link :href="`/products/${product.slug}`" class="group" @click="triggerSmartLink">
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
                <p v-if="product.user" class="mt-1 text-xs text-muted-foreground">by {{ product.user.name }}</p>
                <div class="mt-3 flex items-center justify-between">
                    <Badge v-if="product.is_paid" variant="secondary">
                        {{ currencySymbol }}{{ product.price }}
                    </Badge>
                    <Badge v-else variant="outline" class="text-green-600">Free</Badge>
                    <span v-if="product.downloads_count !== undefined" class="flex items-center gap-1 text-xs text-muted-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ product.downloads_count }}
                    </span>
                </div>
            </CardContent>
        </Card>
    </Link>
</template>
