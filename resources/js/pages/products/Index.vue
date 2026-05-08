<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref, watch } from 'vue';
import ProductGrid from '@/components/ProductGrid.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Faculty {
    id: number;
    name: string;
    slug: string;
    products_count: number;
}

interface Department {
    id: number;
    name: string;
    products_count: number;
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
const selectedClassOfDegree = ref(props.filters.class_of_degree || '');
const departments = ref<Department[]>([]);

const documentTypes = [
    'Article',
    'Case Study',
    'Dissertation',
    'Opinion',
    'Research Project',
    'Report',
    'Seminar',
    'Thesis',
    'Tutorial',
    'White Paper',
];

const degreeOptions = [
    'OND',
    'HND',
    'Associate Degree',
    "Bachelor's Degree",
    "Master's Degree",
    'Doctorate Degree',
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

    if (selectedFaculty.value) {
        params.faculty = selectedFaculty.value;
    }

    if (selectedDepartment.value) {
        params.department = selectedDepartment.value;
    }

    if (selectedSort.value && selectedSort.value !== 'newest') {
        params.sort = selectedSort.value;
    }

    if (selectedPrice.value) {
        params.price = selectedPrice.value;
    }

    if (selectedDocType.value) {
        params.document_type = selectedDocType.value;
    }

    if (selectedClassOfDegree.value) {
        params.class_of_degree = selectedClassOfDegree.value;
    }

    router.get('/products', params, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    selectedFaculty.value = '';
    selectedDepartment.value = '';
    selectedSort.value = 'newest';
    selectedPrice.value = '';
    selectedDocType.value = '';
    selectedClassOfDegree.value = '';
    router.get('/products', {}, { preserveState: true });
}

const hasFilters = () => selectedFaculty.value || selectedDepartment.value || selectedPrice.value || selectedDocType.value || selectedClassOfDegree.value;

// Fetch departments when faculty changes
watch(selectedFaculty, async (facultyId) => {
    selectedDepartment.value = '';
    departments.value = [];

    if (!facultyId) {
        return;
    }

    try {
        const { data } = await axios.get(`/api/departments/${facultyId}`);
        departments.value = data;
    } catch {}
}, { immediate: true });

watch([selectedFaculty, selectedDepartment, selectedSort, selectedPrice, selectedDocType, selectedClassOfDegree], () => {
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
                    <SelectTrigger class="w-45">
                        <SelectValue placeholder="All Faculties" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">
                            {{ f.name }} ({{ f.products_count }})
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedDepartment" :disabled="!selectedFaculty">
                    <SelectTrigger class="w-45">
                        <SelectValue :placeholder="selectedFaculty ? 'All Departments' : 'Select Faculty first'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">
                            {{ d.name }} ({{ d.products_count }})
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedDocType">
                    <SelectTrigger class="w-45">
                        <SelectValue placeholder="All Types" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="dt in documentTypes" :key="dt" :value="dt">
                            {{ dt }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedClassOfDegree">
                    <SelectTrigger class="w-45">
                        <SelectValue placeholder="All Classes" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="degree in degreeOptions" :key="degree" :value="degree">
                            {{ degree }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="selectedPrice">
                    <SelectTrigger class="w-35">
                        <SelectValue placeholder="All Prices" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="free">Free</SelectItem>
                        <SelectItem value="paid">Paid</SelectItem>
                    </SelectContent>
                </Select>

                <div class="ml-auto flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Sort:</span>
                    <Select v-model="selectedSort">
                        <SelectTrigger class="w-40">
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
                <Badge v-if="selectedClassOfDegree" variant="secondary" class="gap-1">
                    {{ selectedClassOfDegree }}
                    <button @click="selectedClassOfDegree = ''" class="ml-1 hover:text-destructive">&times;</button>
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
