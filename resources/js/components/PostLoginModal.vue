<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const page = usePage();

const loginReturn = computed(() => (page.props as Record<string, unknown>).loginReturn as string | null | undefined);

const open = ref(false);
const returnUrl = ref<string | null>(null);

const showReturnPrompt = (value: string) => {
    returnUrl.value = value;
    open.value = true;
};

watch(
    loginReturn,
    (value) => {
        if (value) {
            showReturnPrompt(value);
        }
    },
    { immediate: true },
);

onMounted(() => {
    if (returnUrl.value || typeof window === 'undefined') return;

    const storedReturnUrl = sessionStorage.getItem('postLoginReturnUrl');

    if (storedReturnUrl) {
        sessionStorage.removeItem('postLoginReturnUrl');
        showReturnPrompt(storedReturnUrl);
    }
});

const goDashboard = () => {
    open.value = false;
    returnUrl.value = null;
};

const goBack = () => {
    if (!returnUrl.value) {
        open.value = false;
        return;
    }
    const target = returnUrl.value;
    open.value = false;
    router.visit(target);
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Welcome back!</DialogTitle>
                <DialogDescription>
                    You were redirected here to log in. Where would you like to go now?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="flex-col gap-2 sm:flex-row sm:justify-end">
                <Button variant="outline" @click="goDashboard">Go to Dashboard</Button>
                <Button @click="goBack">Continue where I was</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
