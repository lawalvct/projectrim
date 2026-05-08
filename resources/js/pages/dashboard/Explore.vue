<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import ProductGrid from '@/components/ProductGrid.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Explore Products', href: '/dashboard/explore' },
];

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
    products: {
        data: Array<any>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    faculties: Faculty[];
    filters: {
        q: string | null;
        faculty: string | null;
        department: string | null;
        document_type: string | null;
        class_of_degree: string | null;
        price: string | null;
        sort: string;
    };
}>();

const q = ref(props.filters.q || '');
const faculty = ref(props.filters.faculty || '');
const department = ref(props.filters.department || '');
const documentType = ref(props.filters.document_type || '');
const classOfDegree = ref(props.filters.class_of_degree || '');
const price = ref(props.filters.price || '');
const sort = ref(props.filters.sort || 'newest');
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

const applyFilters = () => {
    const params: Record<string, string> = {};

    if (q.value) {
        params.q = q.value;
    }

    if (faculty.value) {
        params.faculty = faculty.value;
    }

    if (department.value) {
        params.department = department.value;
    }

    if (documentType.value) {
        params.document_type = documentType.value;
    }

    if (classOfDegree.value) {
        params.class_of_degree = classOfDegree.value;
    }

    if (price.value) {
        params.price = price.value;
    }

    if (sort.value && sort.value !== 'newest') {
        params.sort = sort.value;
    }

    router.get('/dashboard/explore', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    q.value = '';
    faculty.value = '';
    department.value = '';
    documentType.value = '';
    classOfDegree.value = '';
    price.value = '';
    sort.value = 'newest';
    router.get('/dashboard/explore', {}, { preserveState: true });
};

const removeFilter = (key: string) => {
    if (key === 'q') {
        q.value = '';
    }

    if (key === 'faculty') {
        faculty.value = '';
    }

    if (key === 'department') {
        department.value = '';
    }

    if (key === 'document_type') {
        documentType.value = '';
    }

    if (key === 'class_of_degree') {
        classOfDegree.value = '';
    }

    if (key === 'price') {
        price.value = '';
    }

    applyFilters();
};

watch(faculty, async (facultyId, oldFacultyId) => {
    const selectedDepartment = department.value;
    departments.value = [];

    if (!facultyId) {
        department.value = '';

        return;
    }

    try {
        const { data } = await axios.get(`/api/departments/${facultyId}`);
        departments.value = data;

        const departmentExists = departments.value.some((item) => String(item.id) === selectedDepartment);

        if ((oldFacultyId && oldFacultyId !== facultyId) || (selectedDepartment && !departmentExists)) {
            department.value = '';
        }
    } catch {
        department.value = '';
    }
}, { immediate: true });

watch([faculty, department, documentType, classOfDegree, price, sort], applyFilters);

const hasActiveFilters = () => q.value || faculty.value || department.value || documentType.value || classOfDegree.value || price.value;
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
                    <Select v-model="faculty">
                        <SelectTrigger class="w-45">
                            <SelectValue placeholder="All Faculties" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">
                                {{ f.name }} ({{ f.products_count }})
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="department" :disabled="!faculty">
                        <SelectTrigger class="w-45">
                            <SelectValue :placeholder="faculty ? 'All Departments' : 'Select Faculty first'" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">
                                {{ d.name }} ({{ d.products_count }})
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="documentType">
                        <SelectTrigger class="w-45">
                            <SelectValue placeholder="All Types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="type in documentTypes" :key="type" :value="type">
                                {{ type }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="classOfDegree">
                        <SelectTrigger class="w-45">
                            <SelectValue placeholder="All Classes" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="degree in degreeOptions" :key="degree" :value="degree">
                                {{ degree }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="price">
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
                        <Select v-model="sort">
                            <SelectTrigger class="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in sortOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

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
                        Faculty: {{ faculties.find(f => String(f.id) === faculty)?.name || faculty }}
                        <button @click="removeFilter('faculty')" class="ml-1"><X class="h-3 w-3" /></button>
                    </Badge>
                    <Badge v-if="department" variant="secondary" class="gap-1">
                        Department: {{ departments.find(d => String(d.id) === department)?.name || department }}
                        <button @click="removeFilter('department')" class="ml-1"><X class="h-3 w-3" /></button>
                    </Badge>
                    <Badge v-if="documentType" variant="secondary" class="gap-1">
                        Type: {{ documentType }}
                        <button @click="removeFilter('document_type')" class="ml-1"><X class="h-3 w-3" /></button>
                    </Badge>
                    <Badge v-if="classOfDegree" variant="secondary" class="gap-1">
                        Class: {{ classOfDegree }}
                        <button @click="removeFilter('class_of_degree')" class="ml-1"><X class="h-3 w-3" /></button>
                    </Badge>
                    <Badge v-if="price" variant="secondary" class="gap-1">
                        Price: {{ price === 'free' ? 'Free' : 'Paid' }}
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
