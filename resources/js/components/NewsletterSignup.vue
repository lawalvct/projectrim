<script setup lang="ts">
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

const email = ref('');
const isSubmitting = ref(false);
const message = ref('');
const isError = ref(false);

async function subscribe() {
    if (!email.value.trim()) return;

    isSubmitting.value = true;
    message.value = '';
    isError.value = false;

    try {
        const response = await fetch('/api/newsletter/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || ''
                ),
                Accept: 'application/json',
            },
            body: JSON.stringify({ email: email.value.trim() }),
        });

        const data = await response.json();

        if (response.ok) {
            message.value = data.message;
            email.value = '';
        } else if (response.status === 422) {
            const errors = data.errors;
            message.value = errors?.email?.[0] || 'Please enter a valid email.';
            isError.value = true;
        } else {
            message.value = 'Something went wrong. Please try again.';
            isError.value = true;
        }
    } catch {
        message.value = 'Network error. Please try again.';
        isError.value = true;
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-md">
        <form @submit.prevent="subscribe" class="flex gap-2">
            <Input
                v-model="email"
                type="email"
                placeholder="Enter your email"
                required
                class="flex-1"
            />
            <Button type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? 'Subscribing...' : 'Subscribe' }}
            </Button>
        </form>
        <p v-if="message" class="mt-2 text-sm" :class="isError ? 'text-destructive' : 'text-green-600'">
            {{ message }}
        </p>
    </div>
</template>
