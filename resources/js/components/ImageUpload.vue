<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue: File[];
    existingImages?: Array<{ id: number; path: string }>;
    max?: number;
}>();

const emit = defineEmits<{
    'update:modelValue': [files: File[]];
    'remove-existing': [id: number];
}>();

const maxImages = computed(() => props.max ?? 10);
const previews = ref<string[]>([]);

function handleFiles(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.files) return;

    const newFiles = Array.from(target.files);
    const current = [...props.modelValue];
    const totalExisting = (props.existingImages?.length ?? 0);
    const allowed = maxImages.value - totalExisting - current.length;
    const filesToAdd = newFiles.slice(0, Math.max(0, allowed));

    filesToAdd.forEach((file) => {
        current.push(file);
        const reader = new FileReader();
        reader.onload = (e) => {
            previews.value.push(e.target?.result as string);
        };
        reader.readAsDataURL(file);
    });

    emit('update:modelValue', current);
    target.value = '';
}

function removeNew(index: number) {
    const current = [...props.modelValue];
    current.splice(index, 1);
    previews.value.splice(index, 1);
    emit('update:modelValue', current);
}

function removeExisting(id: number) {
    emit('remove-existing', id);
}

const canAddMore = computed(() => {
    const total = (props.existingImages?.length ?? 0) + props.modelValue.length;
    return total < maxImages.value;
});
</script>

<template>
    <div>
        <!-- Existing images -->
        <div v-if="existingImages?.length" class="mb-3 grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
            <div v-for="img in existingImages" :key="img.id" class="group relative aspect-square overflow-hidden rounded-lg border">
                <img :src="`/storage/${img.path}`" class="h-full w-full object-cover" />
                <button
                    type="button"
                    class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-white opacity-0 transition-opacity group-hover:opacity-100"
                    @click="removeExisting(img.id)"
                >
                    &times;
                </button>
            </div>
        </div>

        <!-- New image previews -->
        <div v-if="previews.length" class="mb-3 grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
            <div v-for="(preview, index) in previews" :key="index" class="group relative aspect-square overflow-hidden rounded-lg border">
                <img :src="preview" class="h-full w-full object-cover" />
                <button
                    type="button"
                    class="absolute top-1 right-1 flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-white opacity-0 transition-opacity group-hover:opacity-100"
                    @click="removeNew(index)"
                >
                    &times;
                </button>
            </div>
        </div>

        <!-- Upload button -->
        <label v-if="canAddMore" class="flex cursor-pointer items-center gap-2 rounded-lg border-2 border-dashed p-4 text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>Upload Images (max {{ maxImages }})</span>
            <input type="file" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFiles" />
        </label>
    </div>
</template>
