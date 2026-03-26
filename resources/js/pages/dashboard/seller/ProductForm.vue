<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import CoAuthorManager from '@/components/CoAuthorManager.vue';
import FileUpload from '@/components/FileUpload.vue';
import ImageUpload from '@/components/ImageUpload.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import TagInput from '@/components/TagInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Department {
    id: number;
    name: string;
    faculty_id?: number;
}

interface Faculty {
    id: number;
    name: string;
    departments: Department[];
}

interface ExistingProduct {
    id: number;
    title: string;
    slug: string;
    faculty_id: number | null;
    department_id: number | null;
    abstract: string | null;
    table_of_content: string | null;
    chapter_one: string | null;
    meta_description: string | null;
    meta_keywords: string | null;
    document_type: string | null;
    class_of_degree: string | null;
    institution: string | null;
    location_country: string | null;
    location_region: string | null;
    date_available: string | null;
    price: number;
    is_paid: boolean;
    status: string;
    images: Array<{ id: number; path: string }>;
    files: Array<{ id: number; file_name: string; file_size: number; file_type: string }>;
    tags: Array<{ id: number; name: string }>;
    authors: Array<{
        id: number;
        user_id: number;
        is_primary: boolean;
        contribution_percentage: number;
        user: { id: number; name: string; email: string };
    }>;
}

const props = defineProps<{
    product?: ExistingProduct;
    faculties: Faculty[];
    countries: Array<{ id: number; name: string; code: string }>;
    allowPaidProducts: boolean;
}>();

const page = usePage();
const user = computed(() => (page.props.auth as any)?.user);

const isEditing = computed(() => !!props.product);
const activeTab = ref<'general' | 'data'>('general');

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: '/dashboard/seller/products' },
    { title: isEditing.value ? 'Edit Product' : 'New Product', href: '#' },
]);

// Form setup
const form = useForm({
    title: props.product?.title ?? '',
    faculty_id: props.product?.faculty_id?.toString() ?? '',
    department_id: props.product?.department_id?.toString() ?? '',
    abstract: props.product?.abstract ?? '',
    table_of_content: props.product?.table_of_content ?? '',
    chapter_one: props.product?.chapter_one ?? '',
    meta_description: props.product?.meta_description ?? '',
    meta_keywords: props.product?.meta_keywords ?? '',
    tags: props.product?.tags?.map((t) => t.name) ?? [],
    document_type: props.product?.document_type ?? '',
    class_of_degree: props.product?.class_of_degree ?? '',
    institution: props.product?.institution ?? '',
    location_country: props.product?.location_country ?? '',
    location_region: props.product?.location_region ?? '',
    date_available: props.product?.date_available ?? '',
    price: props.product?.price ?? 0,
    images: [] as File[],
    project_file: null as File | null,
    remove_images: [] as number[],
    co_authors: (props.product?.authors?.filter((a) => !a.is_primary).map((a) => ({
        user_id: a.user_id,
        name: a.user.name,
        email: a.user.email,
        contribution_percentage: Number(a.contribution_percentage),
    })) ?? []) as Array<{ user_id: number; name: string; email: string; contribution_percentage: number }>,
    status: props.product?.status ?? 'draft',
});

// Filtered departments based on selected faculty
const filteredDepartments = computed(() => {
    if (!form.faculty_id) {
        return [];
    }

    const faculty = props.faculties.find((f) => f.id.toString() === form.faculty_id);

    return faculty?.departments ?? [];
});

// Reset department when faculty changes
watch(() => form.faculty_id, () => {
    form.department_id = '';
});

const existingImages = computed(() => {
    if (!props.product?.images) {
        return [];
    }

    return props.product.images.filter((img) => !form.remove_images.includes(img.id));
});

const existingFile = computed(() => {
    return props.product?.files?.[0] ?? null;
});

function handleRemoveExistingImage(id: number) {
    form.remove_images.push(id);
}

const degreeOptions = [
    'First Class',
    'Second Class Upper',
    'Second Class Lower',
    'Third Class',
    'Pass',
    'Distinction',
    'Credit',
    'Merit',
];

function submit(status: 'draft' | 'pending') {
    form.status = status;

    const url = isEditing.value
        ? `/dashboard/seller/products/${props.product!.id}`
        : '/dashboard/seller/products';

    const options = {
        forceFormData: true,
        preserveScroll: true,
    };

    if (isEditing.value) {
        form.put(url, options);
    } else {
        form.post(url, options);
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Edit Product' : 'New Product'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 sm:p-6">
            <h1 class="mb-6 text-2xl font-bold">{{ isEditing ? 'Edit Product' : 'Create New Product' }}</h1>

            <!-- Tab navigation -->
            <div class="mb-6 flex gap-1 rounded-lg border bg-muted p-1">
                <button
                    type="button"
                    class="flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors"
                    :class="activeTab === 'general' ? 'bg-background shadow-sm' : 'hover:text-foreground text-muted-foreground'"
                    @click="activeTab = 'general'"
                >
                    General
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors"
                    :class="activeTab === 'data' ? 'bg-background shadow-sm' : 'hover:text-foreground text-muted-foreground'"
                    @click="activeTab = 'data'"
                >
                    Data
                </button>
            </div>

            <form @submit.prevent>
                <!-- TAB 1: General -->
                <div v-show="activeTab === 'general'" class="space-y-6">
                    <!-- Name Section -->
                    <Card>
                        <CardHeader>
                            <h3 class="text-lg font-semibold">Project Details</h3>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label for="title">Project Topic *</Label>
                                <Input id="title" v-model="form.title" placeholder="Enter project topic" />
                                <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label for="faculty">Faculty</Label>
                                    <Select v-model="form.faculty_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Faculty" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="f in faculties" :key="f.id" :value="f.id.toString()">
                                                {{ f.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label for="department">Department</Label>
                                    <Select v-model="form.department_id" :disabled="!filteredDepartments.length">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Department" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="d in filteredDepartments" :key="d.id" :value="d.id.toString()">
                                                {{ d.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- File Section -->
                    <Card>
                        <CardHeader>
                            <h3 class="text-lg font-semibold">Files</h3>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label>Images</Label>
                                <ImageUpload
                                    v-model="form.images"
                                    :existing-images="existingImages"
                                    :max="10"
                                    @remove-existing="handleRemoveExistingImage"
                                />
                            </div>
                            <div>
                                <Label>Project File</Label>
                                <FileUpload
                                    v-model="form.project_file"
                                    :existing-file="existingFile"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Description Section -->
                    <Card>
                        <CardHeader>
                            <h3 class="text-lg font-semibold">Description / Preview</h3>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label>Abstract</Label>
                                <RichTextEditor v-model="form.abstract" placeholder="Write the abstract..." />
                            </div>
                            <div>
                                <Label>Table of Content</Label>
                                <RichTextEditor v-model="form.table_of_content" placeholder="Write the table of content..." />
                            </div>
                            <div>
                                <Label>Chapter 1</Label>
                                <RichTextEditor v-model="form.chapter_one" placeholder="Write chapter 1 preview..." />
                            </div>
                            <div>
                                <Label>Meta Tag Description</Label>
                                <textarea
                                    v-model="form.meta_description"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    rows="3"
                                    placeholder="SEO description..."
                                />
                            </div>
                            <div>
                                <Label>Meta Tag Keywords</Label>
                                <textarea
                                    v-model="form.meta_keywords"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    rows="2"
                                    placeholder="SEO keywords..."
                                />
                            </div>
                            <div>
                                <Label>Tags</Label>
                                <TagInput v-model="form.tags" placeholder="Type tag and press Enter" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- TAB 2: Data -->
                <div v-show="activeTab === 'data'" class="space-y-6">
                    <!-- Co-Authors -->
                    <Card>
                        <CardHeader>
                            <h3 class="text-lg font-semibold">Authors</h3>
                        </CardHeader>
                        <CardContent>
                            <CoAuthorManager
                                v-model="form.co_authors"
                                :primary-author-name="user?.name ?? ''"
                                :primary-author-email="user?.email ?? ''"
                            />
                        </CardContent>
                    </Card>

                    <!-- Document Info -->
                    <Card>
                        <CardHeader>
                            <h3 class="text-lg font-semibold">Document Information</h3>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label>Document Type</Label>
                                    <Input v-model="form.document_type" placeholder="e.g. Thesis, Dissertation, Project" />
                                </div>
                                <div>
                                    <Label>Class of Degree</Label>
                                    <Select v-model="form.class_of_degree">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select class" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="d in degreeOptions" :key="d" :value="d">
                                                {{ d }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div>
                                <Label>Institution</Label>
                                <Input v-model="form.institution" placeholder="e.g. University of Lagos" />
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label>Country</Label>
                                    <Select v-model="form.location_country">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select country" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="c in countries" :key="c.id" :value="c.name">
                                                {{ c.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Region / State</Label>
                                    <Input v-model="form.location_region" placeholder="e.g. Lagos" />
                                </div>
                            </div>

                            <div>
                                <Label>Date Available</Label>
                                <Input type="date" v-model="form.date_available" />
                            </div>

                            <div v-if="allowPaidProducts">
                                <Label>Price</Label>
                                <Input type="number" v-model.number="form.price" min="0" step="0.01" placeholder="0 = Free" />
                                <p class="mt-1 text-xs text-muted-foreground">Set to 0 for free download</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Form Actions -->
                <div class="mt-6 flex items-center justify-between rounded-lg border bg-muted/50 p-4">
                    <div class="text-sm text-muted-foreground">
                        <span v-if="form.processing">Saving...</span>
                        <span v-else-if="isEditing">Status: {{ product?.status }}</span>
                    </div>
                    <div class="flex gap-3">
                        <!-- General tab: only Next -->
                        <template v-if="activeTab === 'general'">
                            <Button
                                type="button"
                                @click="activeTab = 'data'"
                            >
                                Next
                            </Button>
                        </template>

                        <!-- Data tab: submit actions -->
                        <template v-else>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="form.processing"
                                @click="submit('draft')"
                            >
                                Save as Draft
                            </Button>
                            <Button
                                type="button"
                                :disabled="form.processing"
                                @click="submit('pending')"
                            >
                                Submit for Review
                            </Button>
                        </template>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
