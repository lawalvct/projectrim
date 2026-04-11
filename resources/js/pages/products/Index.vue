<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';
import SearchBar from '@/components/SearchBar.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ref, watch } from 'vue';
import axios from 'axios';

interface Faculty {
    id: number;
    name: string;
    slug: string;
    products_count: number;
}

interface Department {
    id: number;
    name: string;
}

const props = defineProps<{
    products: any;
    faculties: Faculty[];
    filters: Record<string, string>;
}>();

const selectedFaculty = ref(props.filters.faculty || '');
const selectedDepartment = ref(props.filters.department || '');
const selectedSort = ref(props.filters.sort || 'newest');
const selectedPrice = ref(props.filters.price || '');
const selectedDocType = ref(props.filters.document_type || '');
const departments = ref<Department[]>([]);

const documentTypes = [
    'Project', 'Thesis', 'Dissertation', 'Journal Article', 'Conference Paper',
    'Book', 'Book Chapter', 'Report', 'Case Study', 'Other',
];

const sortOptions = [
    { value: 'newest', label: 'Newest' },
    { value: 'oldest', label: 'Oldest' },
    { value: 'downloads', label: 'Most Downloaded' },
    { value: 'views', label: 'Most Viewed' },
    { value: 'likes', label: 'Most Liked' },
    { value: 'title_asc', label: 'Title A-Z' },
    { value: 'title_desc', label: 'Title Z-A' },
];

function applyFilters() {
    const params: Record<string, string> = {};
    if (selectedFaculty.value) params.faculty = selectedFaculty.value;
    if (selectedDepartment.value) params.department = selectedDepartment.value;
    if (selectedSort.value && selectedSort.value !== 'newest') params.sort = selectedSort.value;
    if (selectedPrice.value) params.price = selectedPrice.value;
    if (selectedDocType.value) params.document_type = selectedDocType.value;

    router.get('/products', params, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    selectedFaculty.value = '';
    selectedDepartment.value = '';
    selectedSort.value = 'newest';
    selectedPrice.value = '';
    selectedDocType.value = '';
    router.get('/products', {}, { preserveState: true });
}

const hasFilters = () => selectedFaculty.value || selectedDepartment.value || selectedPrice.value || selectedDocType.value;

// Fetch departments when faculty changes
watch(selectedFaculty, async (facultyId) => {
    selectedDepartment.value = '';
    departments.value = [];
    if (!facultyId) return;
    try {
        const { data } = await axios.get(`/api/departments/${facultyId}`);
        departments.value = data;
    } catch {}
}, { immediate: true });

watch([selectedFaculty, selectedDepartment, selectedSort, selectedPrice, selectedDocType], () => {
    applyFilters();
});
</script>

<template>
    <Head title="Browse Projects" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold">Browse Projects</h1>
                <p class="mt-2 text-muted-foreground">
                    Discover research papers, projects, and academic materials from authors worldwide.
                </p>
            </div>

            <!-- Filters Bar -->
            <div class="mb-6 flex flex-wrap items-center gap-3 rounded-lg border bg-card p-4">
                <Select v-model="selectedFaculty">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="All Faculties" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Faculties</SelectItem>
                        <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">
                            {{ f.name }} ({{ f.products_count }})
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedDepartment" :disabled="!selectedFaculty">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue :placeholder="selectedFaculty ? 'All Departments' : 'Select Faculty first'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Departments</SelectItem>
                        <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">
                            {{ d.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedDocType">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="All Types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Types</SelectItem>
                        <SelectItem v-for="dt in documentTypes" :key="dt" :value="dt">
                            {{ dt }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedPrice">
                    <SelectTrigger class="w-[140px]">
                        <SelectValue placeholder="All Prices" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All Prices</SelectItem>
                        <SelectItem value="free">Free</SelectItem>
                        <SelectItem value="paid">Paid</SelectItem>
                    </SelectContent>
                </Select>

                <div class="ml-auto flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Sort:</span>
                    <Select v-model="selectedSort">
                        <SelectTrigger class="w-[160px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Button v-if="hasFilters()" variant="ghost" size="sm" @click="clearFilters">
                    Clear Filters
                </Button>
            </div>

            <!-- Active Filters -->
            <div v-if="hasFilters()" class="mb-4 flex flex-wrap gap-2">
                <Badge v-if="selectedFaculty" variant="secondary" class="gap-1">
                    {{ faculties.find(f => String(f.id) === selectedFaculty)?.name }}
                    <button @click="selectedFaculty = ''" class="ml-1 hover:text-destructive">&times;</button>
                </Badge>
                <Badge v-if="selectedDocType" variant="secondary" class="gap-1">
                    {{ selectedDocType }}
                    <button @click="selectedDocType = ''" class="ml-1 hover:text-destructive">&times;</button>
                </Badge>
                <Badge v-if="selectedDepartment" variant="secondary" class="gap-1">
                    {{ departments.find(d => String(d.id) === selectedDepartment)?.name }}
                    <button @click="selectedDepartment = ''" class="ml-1 hover:text-destructive">&times;</button>
                </Badge>
                <Badge v-if="selectedPrice" variant="secondary" class="gap-1">
                    {{ selectedPrice === 'free' ? 'Free' : 'Paid' }}
                    <button @click="selectedPrice = ''" class="ml-1 hover:text-destructive">&times;</button>
                </Badge>
            </div>

            <!-- Results Count -->
            <div class="mb-4 text-sm text-muted-foreground">
                {{ products.total }} project{{ products.total !== 1 ? 's' : '' }} found
            </div>

            <!-- Product Grid with Pagination -->
            <ProductGrid :products="products" empty-message="No projects match your filters." />
        </div>
    </PublicLayout>
</template>
