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
import { useProductVideoUpload } from '@/composables/useProductVideoUpload';
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
    preview_video: string | null;
    preview_video_status?: string | null;
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
const orderedCountries = computed(() => [
    ...props.countries.filter((country) => country.code === 'NG'),
    ...props.countries.filter((country) => country.code !== 'NG'),
]);

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
    preview_video_upload_token: '',
    remove_video: false,
    remove_images: [] as number[],
    co_authors: (props.product?.authors?.filter((a) => !a.is_primary).map((a) => ({
        user_id: a.user_id,
        name: a.user.name,
        email: a.user.email,
        contribution_percentage: Number(a.contribution_percentage),
    })) ?? []) as Array<{ user_id: number; name: string; email: string; contribution_percentage: number }>,
    status: props.product?.status ?? 'draft',
    notify_users: false,
});

const previewVideoInput = ref<HTMLInputElement | null>(null);
const videoUpload = useProductVideoUpload();
const selectedVideoFile = videoUpload.file;
const videoProgress = videoUpload.progress;
const videoError = videoUpload.error;
const videoCurrentChunk = videoUpload.currentChunk;
const videoTotalChunks = videoUpload.totalChunks;
const videoIsComplete = videoUpload.isComplete;
const videoSubmissionBlocked = computed(() => videoUpload.requiresCompletion.value);

async function selectPreviewVideo(event: Event) {
    const selectedFile = (event.target as HTMLInputElement).files?.[0];

    if (!selectedFile) {
return;
}

    form.preview_video_upload_token = '';
    form.remove_video = false;
    await videoUpload.start(selectedFile);

    if (videoUpload.isComplete.value && videoUpload.token.value) {
form.preview_video_upload_token = videoUpload.token.value;
}
}

async function retryPreviewVideo() {
    form.preview_video_upload_token = '';
    await videoUpload.retry();

    if (videoUpload.isComplete.value && videoUpload.token.value) {
form.preview_video_upload_token = videoUpload.token.value;
}
}

async function cancelPreviewVideo() {
    await videoUpload.cancel();
    form.preview_video_upload_token = '';

    if (previewVideoInput.value) {
previewVideoInput.value.value = '';
}
}

async function removeCurrentVideo() {
    await cancelPreviewVideo();
    form.remove_video = true;
}

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

const documentTypeOptions = [
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

const uploadProgress = ref(0);

function submit(status: 'draft' | 'pending') {
    if (videoSubmissionBlocked.value) {
return;
}

    form.status = status;

    const url = isEditing.value
        ? `/dashboard/seller/products/${props.product!.id}`
        : '/dashboard/seller/products';

    // reset progress when starting
    uploadProgress.value = 0;

    const baseOptions = {
        forceFormData: true,
        preserveScroll: true,
    };

    const options = {
        ...baseOptions,
        // Inertia exposes an onProgress callback for uploads
        onProgress: (progress: any) => {
            // Typical shape: { percentage: number }
            if (progress && typeof progress.percentage === 'number') {
                uploadProgress.value = Math.round(progress.percentage);
            } else if (progress && progress.detail && typeof progress.detail.loaded === 'number' && typeof progress.detail.total === 'number') {
                // Fallback for different progress event shapes
                const pct = Math.round((progress.detail.loaded / progress.detail.total) * 100);
                uploadProgress.value = Math.min(100, Math.max(0, pct));
            }
        },
    };

    if (isEditing.value) {
        form
            .transform((data) => ({
                ...data,
                _method: 'put',
            }))
            .post(url, options as any);
    } else {
        form.post(url, options as any);
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
                <!-- Validation Errors -->
                <div v-if="Object.keys(form.errors).length" class="mb-6 rounded-lg border border-destructive/50 bg-destructive/5 p-4">
                    <p class="mb-2 text-sm font-medium text-destructive">Please fix the following errors:</p>
                    <ul class="list-inside list-disc space-y-1 text-sm text-destructive">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </div>

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
                                <Label>Cover Images</Label>
                                <ImageUpload
                                    v-model="form.images"
                                    :existing-images="existingImages"
                                    :max="10"
                                    @remove-existing="handleRemoveExistingImage"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">Recommended size: <strong>1200 × 750 px</strong> (16:10 ratio). JPEG or WebP, max 500 KB per image.</p>
                                <p v-if="Object.keys(form.errors).some(k => k.startsWith('images'))" class="mt-1 text-xs text-destructive">
                                    {{ Object.entries(form.errors).filter(([k]) => k.startsWith('images')).map(([, v]) => v).join(', ') }}
                                </p>
                            </div>
                            <div>
                                <Label>Project File *</Label>
                                <FileUpload
                                    v-model="form.project_file"
                                    :existing-file="existingFile"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ existingFile ? 'Upload a new file only if you want to replace the current one.' : 'Upload the main project file to continue.' }}
                                </p>
                                <p v-if="form.errors.project_file" class="mt-1 text-xs text-destructive">{{ form.errors.project_file }}</p>

                            </div>
                            <div>
                                <Label>Preview Video</Label>
                                <p class='mb-2 text-xs text-muted-foreground'>MP4, WebM or MOV, up to 400 MB and 20 minutes. Maximum 1080p; the final MP4 is automatically compressed below 100 MB.</p>
                                <div v-if="isEditing && product?.preview_video && !form.remove_video" class="mb-2 flex items-center gap-3 rounded-lg border bg-muted/50 p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                    <span class="flex-1 truncate text-sm">Current video uploaded</span>
                                    <button type="button" class="text-xs text-destructive hover:underline" @click="removeCurrentVideo">
                                        Remove
                                    </button>
                                </div>
                                <div v-if="form.remove_video && !selectedVideoFile" class="mb-2 rounded-lg border border-dashed border-amber-500 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-950/20 dark:text-amber-400">
                                    Video will be removed on save.
                                    <button type="button" class="ml-1 underline" @click="form.remove_video = false">Undo</button>
                                </div>
                                <input
                                    ref="previewVideoInput"
                                    type="file"
                                    accept="video/mp4,video/webm,video/quicktime"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-primary/10 file:px-3 file:py-1 file:text-xs file:font-medium file:text-primary"
                                    @change="selectPreviewVideo"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">Upload a video summarizing the process and findings of your research.</p>
                                <div v-if="selectedVideoFile" class="mt-3 rounded-lg border bg-muted/30 p-3 text-xs">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="truncate font-medium">{{ selectedVideoFile.name }}</span>
                                        <span>{{ videoProgress }}%</span>

                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                                        <div class="h-full bg-primary transition-all" :style="{ width: `${videoProgress}%` }" />
                                    </div>
                                    <p v-if="videoIsComplete" class="mt-2 text-green-700 dark:text-green-400">Upload complete. Conversion will continue in the background after saving.</p>
                                    <p v-else-if="videoError" class="mt-2 text-destructive">{{ videoError }}</p>
                                    <p v-else class="mt-2 text-muted-foreground">Uploading chunk {{ videoCurrentChunk }} of {{ videoTotalChunks || '...' }}</p>
                                    <p v-if="isEditing && product?.preview_video" class="mt-2 text-muted-foreground">Your current video stays available until the replacement is ready.</p>

                                    <div class="mt-3 flex gap-2">
                                        <Button v-if="videoError" type="button" size="sm" variant="outline" @click="retryPreviewVideo">Retry</Button>
                                        <Button type="button" size="sm" variant="ghost" @click="cancelPreviewVideo">Cancel and reselect</Button>
                                    </div>

                                </div>
                                <p v-if="form.errors.preview_video_upload_token" class="mt-1 text-xs text-destructive">{{ form.errors.preview_video_upload_token }}</p>
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
                                    <Select v-model="form.document_type">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select document type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="dt in documentTypeOptions" :key="dt" :value="dt">
                                                {{ dt }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
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
                                            <SelectItem v-for="c in orderedCountries" :key="c.id" :value="c.name">
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

                    <!-- Notification -->
                    <Card v-if="!isEditing">
                        <CardHeader>
                            <h3 class="text-lg font-semibold">Notification</h3>
                        </CardHeader>
                        <CardContent>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.notify_users" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary" />
                                <div>
                                    <span class="text-sm font-medium">Notify users about this product</span>
                                    <p class="text-xs text-muted-foreground">Queue an email to all registered users after the product is saved. Delivery continues in the background.</p>
                                </div>
                            </label>
                        </CardContent>
                    </Card>
                </div>

                <!-- Form Actions -->
                <div class="mt-6 flex items-center justify-between rounded-lg border bg-muted/50 p-4">
                    <div class="text-sm text-muted-foreground">
                        <template v-if="form.processing">
                            <span v-if="uploadProgress > 0">{{ uploadProgress }}% Submitting</span>
                            <span v-else>Submitting...</span>
                        </template>
                        <span v-else-if="videoSubmissionBlocked">Finish the preview video upload before saving.</span>
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
                                :disabled="form.processing || videoSubmissionBlocked"
                                @click="submit('pending')"
                            >
                                Submit and Publish
                            </Button>
                        </template>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
