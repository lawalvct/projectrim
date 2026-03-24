<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    isApproved: boolean;
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Become a Seller', href: '/dashboard/apply-seller' },
];

const form = useForm({});

const submit = () => {
    form.post('/dashboard/apply-seller');
};
</script>

<template>
    <Head title="Become a Seller" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 items-start justify-center p-6">
            <Card class="w-full max-w-lg">
                <CardHeader class="text-center">
                    <CardTitle class="text-2xl">Become a Seller</CardTitle>
                    <CardDescription>
                        Start sharing your research papers and academic projects with the world.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                Upload and manage your research projects
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                Earn revenue from views and downloads
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                Collaborate with co-authors
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                Track your analytics and request payouts
                            </li>
                        </ul>

                        <div v-if="page.props.flash?.status" class="rounded-md bg-green-50 p-3 text-sm text-green-700">
                            {{ page.props.flash.status }}
                        </div>

                        <Button
                            class="w-full"
                            size="lg"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            <Spinner v-if="form.processing" />
                            Apply to Become a Seller
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
