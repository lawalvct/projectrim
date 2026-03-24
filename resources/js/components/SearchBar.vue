<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    modelValue?: string;
    placeholder?: string;
    showButton?: boolean;
    compact?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const query = ref(props.modelValue || '');
const suggestions = ref<Array<{ type: string; title: string; url: string }>>([]);
const showSuggestions = ref(false);
const isLoading = ref(false);
let debounceTimer: ReturnType<typeof setTimeout>;

function search() {
    if (!query.value.trim()) return;
    router.get('/search', { q: query.value.trim() }, { preserveState: true });
    showSuggestions.value = false;
}

async function fetchSuggestions() {
    if (query.value.length < 2) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }

    isLoading.value = true;
    try {
        const response = await fetch(`/api/search/autocomplete?q=${encodeURIComponent(query.value)}`);
        if (response.ok) {
            suggestions.value = await response.json();
            showSuggestions.value = suggestions.value.length > 0;
        }
    } catch {
        suggestions.value = [];
    } finally {
        isLoading.value = false;
    }
}

function onInput() {
    emit('update:modelValue', query.value);
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchSuggestions, 300);
}

function goTo(url: string) {
    showSuggestions.value = false;
    router.visit(url);
}

function onBlur() {
    // Delay to allow click on suggestion
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
}

function typeLabel(type: string): string {
    return type === 'product' ? 'Project' : type.charAt(0).toUpperCase() + type.slice(1);
}
</script>

<template>
    <div class="relative w-full">
        <form @submit.prevent="search" class="flex gap-2">
            <div class="relative flex-1">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <Input
                    v-model="query"
                    type="text"
                    :placeholder="placeholder || 'Search projects, authors, institutions...'"
                    :class="[compact ? 'h-9 pl-9 text-sm' : 'h-11 pl-10']"
                    @input="onInput"
                    @focus="query.length >= 2 && (showSuggestions = true)"
                    @blur="onBlur"
                    @keydown.escape="showSuggestions = false"
                />
            </div>
            <Button v-if="showButton" type="submit" :size="compact ? 'sm' : 'default'">
                Search
            </Button>
        </form>

        <!-- Autocomplete Dropdown -->
        <div
            v-if="showSuggestions"
            class="absolute top-full left-0 z-50 mt-1 w-full rounded-md border bg-popover shadow-lg"
        >
            <ul class="max-h-64 overflow-y-auto py-1">
                <li
                    v-for="(item, idx) in suggestions"
                    :key="idx"
                    class="flex cursor-pointer items-center gap-3 px-3 py-2 text-sm hover:bg-accent/10"
                    @mousedown.prevent="goTo(item.url)"
                >
                    <span class="rounded bg-muted px-1.5 py-0.5 text-xs font-medium text-muted-foreground">
                        {{ typeLabel(item.type) }}
                    </span>
                    <span class="truncate">{{ item.title }}</span>
                </li>
            </ul>
            <div class="border-t px-3 py-2 text-center">
                <button
                    type="button"
                    class="text-xs text-primary hover:underline"
                    @mousedown.prevent="search"
                >
                    View all results for "{{ query }}"
                </button>
            </div>
        </div>
    </div>
</template>
