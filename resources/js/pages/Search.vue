<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import ProductGrid from '@/components/ProductGrid.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, watch } from 'vue';

interface Faculty {
    id: number;
    name: string;
    slug: string;
    products_count: number;
}

const props = defineProps<{
    products: any;
    faculties: Faculty[];
    filters: Record<string, string>;
}>();

const form = ref({
    q: props.filters.q || '',
    author_name: props.filters.author_name || '',
    author_email: props.filters.author_email || '',
    faculty: props.filters.faculty || '',
    department: props.filters.department || '',
    institution: props.filters.institution || '',
    class_of_degree: props.filters.class_of_degree || '',
    document_type: props.filters.document_type || '',
    country: props.filters.country || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    sort: props.filters.sort || 'relevance',
});

const departments = ref<Array<{ id: number; name: string }>>([]);
const showAdvanced = ref(
    !!(form.value.author_name || form.value.author_email || form.value.institution ||
       form.value.class_of_degree || form.value.document_type || form.value.country ||
       form.value.date_from || form.value.date_to)
);

const degreeOptions = [
    'First Class', 'Second Class Upper', 'Second Class Lower',
    'Third Class', 'Pass', 'Distinction', 'Merit', 'PhD',
];

const documentTypes = [
    'Project', 'Thesis', 'Dissertation', 'Journal Article', 'Conference Paper',
    'Book', 'Book Chapter', 'Report', 'Case Study', 'Other',
];

const sortOptions = [
    { value: 'relevance', label: 'Relevance' },
    { value: 'newest', label: 'Newest' },
    { value: 'downloads', label: 'Most Downloaded' },
    { value: 'views', label: 'Most Viewed' },
    { value: 'likes', label: 'Most Liked' },
];

// Fetch departments when faculty changes
watch(() => form.value.faculty, async (val) => {
    departments.value = [];
    form.value.department = '';
    if (!val) return;
    try {
        const res = await fetch(`/api/departments/${val}`);
        if (res.ok) departments.value = await res.json();
    } catch { /* ignore */ }
});

function search() {
    const params: Record<string, string> = {};
    for (const [key, value] of Object.entries(form.value)) {
        if (value && !(key === 'sort' && value === 'relevance')) {
            params[key] = value;
        }
    }
    router.get('/search', params, { preserveState: true });
}

function clearAll() {
    form.value = {
        q: '', author_name: '', author_email: '', faculty: '', department: '',
        institution: '', class_of_degree: '', document_type: '', country: '',
        date_from: '', date_to: '', sort: 'relevance',
    };
    router.get('/search', {}, { preserveState: true });
}
</script>

<template>
    <Head title="Search Projects" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <h1 class="mb-6 text-3xl font-bold">Search Projects</h1>

            <!-- Search Form -->
            <form @submit.prevent="search" class="mb-8 rounded-lg border bg-card p-6">
                <!-- Keyword Search -->
                <div class="mb-4">
                    <Label for="q">Keyword</Label>
                    <div class="mt-1 flex gap-2">
                        <Input
                            id="q"
                            v-model="form.q"
                            placeholder="Search by title, abstract, keywords..."
                            class="flex-1"
                        />
                        <Button type="submit">Search</Button>
                    </div>
                </div>

                <!-- Quick Filters Row -->
                <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <Label>Faculty</Label>
                        <Select v-model="form.faculty">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="All Faculties" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Faculties</SelectItem>
                                <SelectItem v-for="f in faculties" :key="f.id" :value="String(f.id)">
                                    {{ f.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Department</Label>
                        <Select v-model="form.department" :disabled="!departments.length">
                            <SelectTrigger class="mt-1">
                                <SelectValue :placeholder="departments.length ? 'Select Department' : 'Select faculty first'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Departments</SelectItem>
                                <SelectItem v-for="d in departments" :key="d.id" :value="String(d.id)">
                                    {{ d.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Sort By</Label>
                        <Select v-model="form.sort">
                            <SelectTrigger class="mt-1">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Advanced Filters Toggle -->
                <button
                    type="button"
                    class="mb-4 text-sm font-medium text-primary hover:underline"
                    @click="showAdvanced = !showAdvanced"
                >
                    {{ showAdvanced ? 'Hide' : 'Show' }} Advanced Filters
                </button>

                <!-- Advanced Filters -->
                <div v-if="showAdvanced" class="grid grid-cols-1 gap-4 border-t pt-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <Label for="author_name">Author Name</Label>
                        <Input id="author_name" v-model="form.author_name" placeholder="Search by name" class="mt-1" />
                    </div>
                    <div>
                        <Label for="author_email">Author Email</Label>
                        <Input id="author_email" v-model="form.author_email" type="email" placeholder="author@example.com" class="mt-1" />
                    </div>
                    <div>
                        <Label for="institution">Institution</Label>
                        <Input id="institution" v-model="form.institution" placeholder="University name" class="mt-1" />
                    </div>
                    <div>
                        <Label>Document Type</Label>
                        <Select v-model="form.document_type">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Types</SelectItem>
                                <SelectItem v-for="dt in documentTypes" :key="dt" :value="dt">
                                    {{ dt }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Class of Degree</Label>
                        <Select v-model="form.class_of_degree">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="All Classes" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Classes</SelectItem>
                                <SelectItem v-for="d in degreeOptions" :key="d" :value="d">
                                    {{ d }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="country">Country</Label>
                        <Input id="country" v-model="form.country" placeholder="Country name" class="mt-1" />
                    </div>
                    <div>
                        <Label for="date_from">Date From</Label>
                        <Input id="date_from" v-model="form.date_from" type="date" class="mt-1" />
                    </div>
                    <div>
                        <Label for="date_to">Date To</Label>
                        <Input id="date_to" v-model="form.date_to" type="date" class="mt-1" />
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-4 flex gap-2">
                    <Button type="submit">Search</Button>
                    <Button type="button" variant="outline" @click="clearAll">Clear All</Button>
                </div>
            </form>

            <!-- Results -->
            <div class="mb-4 text-sm text-muted-foreground">
                {{ products.total }} result{{ products.total !== 1 ? 's' : '' }} found
                <span v-if="form.q">for "{{ form.q }}"</span>
            </div>

            <ProductGrid :products="products" empty-message="No projects match your search." />
        </div>
    </PublicLayout>
</template>
