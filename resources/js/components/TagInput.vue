<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    modelValue: string[];
    placeholder?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [tags: string[]];
}>();

const inputValue = ref('');

function addTag(value: string) {
    const tag = value.trim();
    if (!tag) return;
    if (props.modelValue.includes(tag)) return;
    emit('update:modelValue', [...props.modelValue, tag]);
    inputValue.value = '';
}

function removeTag(index: number) {
    const tags = [...props.modelValue];
    tags.splice(index, 1);
    emit('update:modelValue', tags);
}

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addTag(inputValue.value);
    }
    if (event.key === 'Backspace' && !inputValue.value && props.modelValue.length) {
        removeTag(props.modelValue.length - 1);
    }
}

function handlePaste(event: ClipboardEvent) {
    event.preventDefault();
    const text = event.clipboardData?.getData('text') || '';
    const tags = text.split(',').map((t) => t.trim()).filter(Boolean);
    const newTags = [...props.modelValue];
    tags.forEach((tag) => {
        if (!newTags.includes(tag)) {
            newTags.push(tag);
        }
    });
    emit('update:modelValue', newTags);
}
</script>

<template>
    <div class="flex min-h-10 flex-wrap gap-1.5 rounded-md border bg-background px-3 py-2 focus-within:ring-1 focus-within:ring-ring">
        <Badge
            v-for="(tag, index) in modelValue"
            :key="tag"
            variant="secondary"
            class="gap-1"
        >
            {{ tag }}
            <button type="button" class="ml-0.5 hover:text-destructive" @click="removeTag(index)">&times;</button>
        </Badge>
        <input
            v-model="inputValue"
            :placeholder="modelValue.length ? '' : (placeholder || 'Type and press Enter or comma')"
            class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
            @keydown="handleKeydown"
            @paste="handlePaste"
        />
    </div>
</template>
