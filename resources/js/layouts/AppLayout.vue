<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PostLoginModal from '@/components/PostLoginModal.vue';
import TourGuide from '@/components/TourGuide.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const impersonation = computed(
    () =>
        (page.props.impersonation as
            | { active?: boolean; stop_url?: string }
            | undefined) ?? { active: false },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            v-if="impersonation.active"
            class="border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100"
        >
            <div
                class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3"
            >
                <span>You are impersonating a user.</span>
                <Link
                    :href="impersonation.stop_url || '/impersonation/stop'"
                    method="post"
                    as="button"
                    class="rounded-md border border-amber-300 px-3 py-1 font-medium hover:bg-amber-100 dark:border-amber-700 dark:hover:bg-amber-900"
                >
                    Return to admin
                </Link>
            </div>
        </div>
        <slot />
        <PostLoginModal />
        <TourGuide />
    </AppLayout>
</template>
