<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface CoAuthor {
    user_id: number;
    name: string;
    email: string;
    contribution_percentage: number;
}

const props = defineProps<{
    modelValue: CoAuthor[];
    primaryAuthorName: string;
    primaryAuthorEmail: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [authors: CoAuthor[]];
}>();

const emailInput = ref('');
const lookupError = ref('');
const lookupLoading = ref(false);

const primaryPercentage = computed(() => {
    const total = props.modelValue.reduce((sum, a) => sum + a.contribution_percentage, 0);
    return Math.max(1, 100 - total);
});

const totalPercentage = computed(() => {
    return props.modelValue.reduce((sum, a) => sum + a.contribution_percentage, 0) + primaryPercentage.value;
});

const canAddMore = computed(() => props.modelValue.length < 10);

async function lookupUser() {
    if (!emailInput.value) return;
    lookupError.value = '';
    lookupLoading.value = true;

    try {
        const response = await fetch(`/api/users/lookup?email=${encodeURIComponent(emailInput.value)}`);
        if (!response.ok) {
            lookupError.value = 'User not found. They must be registered.';
            return;
        }
        const data = await response.json();
        if (!data.found) {
            lookupError.value = 'User not found. They must be registered.';
            return;
        }

        // Check if already added
        if (props.modelValue.some((a) => a.user_id === data.user.id)) {
            lookupError.value = 'This user is already added as a co-author.';
            return;
        }

        // Check not self
        if (data.user.email === props.primaryAuthorEmail) {
            lookupError.value = 'You cannot add yourself as a co-author.';
            return;
        }

        const newAuthor: CoAuthor = {
            user_id: data.user.id,
            name: data.user.name,
            email: data.user.email,
            contribution_percentage: 10,
        };

        emit('update:modelValue', [...props.modelValue, newAuthor]);
        emailInput.value = '';
    } catch {
        lookupError.value = 'Failed to look up user.';
    } finally {
        lookupLoading.value = false;
    }
}

function removeAuthor(index: number) {
    const authors = [...props.modelValue];
    authors.splice(index, 1);
    emit('update:modelValue', authors);
}

function updatePercentage(index: number, value: number) {
    const authors = [...props.modelValue];
    authors[index] = { ...authors[index], contribution_percentage: Math.max(1, Math.min(99, value)) };
    emit('update:modelValue', authors);
}
</script>

<template>
    <div class="space-y-4">
        <!-- Primary Author -->
        <div class="rounded-lg border bg-muted/50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium">{{ primaryAuthorName }}</div>
                    <div class="text-xs text-muted-foreground">{{ primaryAuthorEmail }} (Primary Author)</div>
                </div>
                <div class="text-sm font-semibold">
                    {{ primaryPercentage }}%
                </div>
            </div>
        </div>

        <!-- Co-Authors -->
        <div v-for="(author, index) in modelValue" :key="author.user_id" class="flex items-center gap-3 rounded-lg border p-3">
            <div class="flex-1">
                <div class="text-sm font-medium">{{ author.name }}</div>
                <div class="text-xs text-muted-foreground">{{ author.email }}</div>
            </div>
            <div class="flex items-center gap-2">
                <Input
                    type="number"
                    :model-value="author.contribution_percentage"
                    min="1"
                    max="99"
                    class="w-20 text-center"
                    @update:model-value="updatePercentage(index, Number($event))"
                />
                <span class="text-sm">%</span>
                <Button type="button" variant="ghost" size="sm" class="text-destructive" @click="removeAuthor(index)">
                    &times;
                </Button>
            </div>
        </div>

        <!-- Add Co-Author -->
        <div v-if="canAddMore" class="space-y-2">
            <div class="flex gap-2">
                <Input
                    v-model="emailInput"
                    type="email"
                    placeholder="Enter co-author's email"
                    class="flex-1"
                    @keydown.enter.prevent="lookupUser"
                />
                <Button type="button" variant="outline" :disabled="lookupLoading || !emailInput" @click="lookupUser">
                    {{ lookupLoading ? 'Looking up...' : 'Add' }}
                </Button>
            </div>
            <p v-if="lookupError" class="text-xs text-destructive">{{ lookupError }}</p>
        </div>

        <!-- Total -->
        <div class="flex items-center justify-between rounded-lg border-2 p-3" :class="totalPercentage === 100 ? 'border-green-500' : 'border-destructive'">
            <span class="text-sm font-medium">Total</span>
            <span class="text-sm font-bold">{{ totalPercentage }}%</span>
        </div>
    </div>
</template>
