<script setup lang="ts">
import { ref, computed } from 'vue';

const props = defineProps<{
    modelValue: File | null;
    existingFile?: { id: number; file_name: string; file_size: number; file_type: string } | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [file: File | null];
}>();

const fileName = ref<string | null>(null);

function handleFile(event: Event) {
    const target = event.target as HTMLInputElement;
    if (!target.files?.length) return;
    const file = target.files[0];
    fileName.value = file.name;
    emit('update:modelValue', file);
}

function removeFile() {
    fileName.value = null;
    emit('update:modelValue', null);
}

function formatSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}
</script>

<template>
    <div>
        <!-- Existing file display -->
        <div v-if="existingFile && !modelValue" class="flex items-center gap-3 rounded-lg border p-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div class="flex-1 text-sm">
                <div class="font-medium">{{ existingFile.file_name }}</div>
                <div class="text-xs text-muted-foreground">{{ formatSize(existingFile.file_size) }} &middot; {{ existingFile.file_type.toUpperCase() }}</div>
            </div>
            <label class="cursor-pointer text-xs font-medium text-primary hover:underline">
                Replace
                <input type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip" class="hidden" @change="handleFile" />
            </label>
        </div>

        <!-- New file selected -->
        <div v-else-if="fileName" class="flex items-center gap-3 rounded-lg border p-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div class="flex-1 text-sm font-medium">{{ fileName }}</div>
            <button type="button" class="text-xs text-destructive hover:underline" @click="removeFile">Remove</button>
        </div>

        <!-- Upload area -->
        <label v-else class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border-2 border-dashed p-6 text-sm text-muted-foreground transition-colors hover:border-primary hover:text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <span>Upload Project File</span>
            <span class="text-xs">PDF, DOC, DOCX, PPT, XLS, ZIP (max 50MB)</span>
            <input type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip" class="hidden" @change="handleFile" />
        </label>
    </div>
</template>
