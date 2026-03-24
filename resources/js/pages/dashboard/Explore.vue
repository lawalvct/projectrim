<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Explore Products', href: '/dashboard/explore' },
];

const props = defineProps<{
    products: {
        data: Array<any>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    faculties: Array<{ id: number; name: string; slug: string }>;
    filters: {
        q: string | null;
        faculty: string | null;
        document_type: string | null;
        price: string | null;
        sort: string;
    };
}>();

const q = ref(props.filters.q || '');
const faculty = ref(props.filters.faculty || '');
const documentType = ref(props.filters.document_type || '');
const price = ref(props.filters.price || '');
const sort = ref(props.filters.sort || 'newest');

const applyFilters = () => {
    const params: Record<string, string> = {};
    if (q.value) params.q = q.value;
    if (faculty.value) params.faculty = faculty.value;
    if (documentType.value) params.document_type = documentType.value;
    if (price.value) params.price = price.value;
    if (sort.value && sort.value !== 'newest') params.sort = sort.value;

    router.get('/dashboard/explore', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    q.value = '';
    faculty.value = '';
    documentType.value = '';
    price.value = '';
    sort.value = 'newest';
    router.get('/dashboard/explore', {}, { preserveState: true });
};

const removeFilter = (key: string) => {
    if (key === 'q') q.value = '';
    if (key === 'faculty') faculty.value = '';
    if (key === 'document_type') documentType.value = '';
    if (key === 'price') price.value = '';
    applyFilters();
};

watch([faculty, documentType, price, sort], applyFilters);

const hasActiveFilters = () => q.value || faculty.value || documentType.value || price.value;
</script>

<template>
    <Head title="Explore Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold">Explore Products</h1>
                <p class="text-sm text-muted-foreground">Discover research projects and publications</p>
            </div>

            <!-- Search + Filters -->
            <div class="flex flex-col gap-4">
                <!-- Search bar -->
                <form @submit.prevent="applyFilters" class="flex gap-2">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="q" placeholder="Search by title, abstract, institution..." class="pl-9" />
                    </div>
                    <Button type="submit">Search</Button>
                </form>

                <!-- Filter row -->
                <div class="flex flex-wrap items-center gap-3">
                    <select v-model="faculty" class="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="">All Faculties</option>
                        <option v-for="f in faculties" :key="f.id" :value="f.slug">{{ f.name }}</option>
                    </select>

                    <select v-model="documentType" class="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="">All Types</option>
                        <option value="project">Project</option>
                        <option value="thesis">Thesis</option>
                        <option value="dissertation">Dissertation</option>
                        <option value="seminar_paper">Seminar Paper</option>
                        <option value="journal_article">Journal Article</option>
                    </select>

                    <select v-model="price" class="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="">All Prices</option>
                        <option value="free">Free</option>
                        <option value="paid">Paid</option>
                    </select>

                    <select v-model="sort" class="rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="newest">Newest</option>
                        <option value="oldest">Oldest</option>
                        <option value="downloads">Most Downloads</option>
                        <option value="views">Most Views</option>
                        <option value="title_asc">Title A–Z</option>
                    </select>

                    <Button v-if="hasActiveFilters()" variant="ghost" size="sm" @click="clearFilters">
                        <X class="mr-1 h-3 w-3" /> Clear
                    </Button>
                </div>

                <!-- Active filters -->
                <div v-if="hasActiveFilters()" class="flex flex-wrap gap-2">
                    <Badge v-if="q" variant="secondary" class="gap-1">
                        Search: "{{ q }}"
                        <button @click="removeFilter('q')" class="ml-1"><X class="h-3 w-3" /></button>
                    </Badge>
                    <Badge v-if="faculty" variant="secondary" class="gap-1">
                        Faculty: {{ faculties.find(f => f.slug === faculty)?.name || faculty }}
                        <button @click="removeFilter('faculty')" class="ml-1"><X class="h-3 w-3" /></button>
                    </Badge>
                    <Badge v-if="documentType" variant="secondary" class="gap-1">
                        Type: {{ documentType.replace('_', ' ') }}
                        <button @click="removeFilter('document_type')" class="ml-1"><X class="h-3 w-3" /></button>
                    </Badge>
                    <Badge v-if="price" variant="secondary" class="gap-1">
                        Price: {{ price }}
                        <button @click="removeFilter('price')" class="ml-1"><X class="h-3 w-3" /></button>
                    </Badge>
                </div>
            </div>

            <!-- Results -->
            <div>
                <p class="mb-4 text-sm text-muted-foreground">{{ products.total }} result(s)</p>
                <ProductGrid :products="products" empty-message="No products match your search criteria." />
            </div>
        </div>
    </AppLayout>
</template>
