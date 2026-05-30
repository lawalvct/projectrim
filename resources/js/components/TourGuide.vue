<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { useTour } from '@/composables/useTour';

const { isActive, steps, currentIndex, next, prev, stop } = useTour();

const HOLE_PADDING = 8;
const CARD_WIDTH = 320;
const GAP = 14;

const rect = ref<DOMRect | null>(null);

const currentStep = computed(() => steps.value[currentIndex.value] ?? null);
const isFirst = computed(() => currentIndex.value === 0);
const isLast = computed(() => currentIndex.value === steps.value.length - 1);
const progress = computed(() => `${currentIndex.value + 1} / ${steps.value.length}`);

let scrollTimer: number | undefined;

const measure = () => {
    const step = currentStep.value;
    if (!step?.target) {
        rect.value = null;
        return;
    }
    const el = document.querySelector(step.target) as HTMLElement | null;
    rect.value = el ? el.getBoundingClientRect() : null;
};

const focusTarget = () => {
    const step = currentStep.value;
    if (!step?.target) {
        rect.value = null;
        return;
    }
    const el = document.querySelector(step.target) as HTMLElement | null;
    if (!el) {
        rect.value = null;
        return;
    }
    el.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
    window.clearTimeout(scrollTimer);
    scrollTimer = window.setTimeout(measure, 320);
    // Measure immediately too, in case no scrolling is needed.
    rect.value = el.getBoundingClientRect();
};

const holeStyle = computed(() => {
    if (!rect.value) return undefined;
    const r = rect.value;
    return {
        top: `${r.top - HOLE_PADDING}px`,
        left: `${r.left - HOLE_PADDING}px`,
        width: `${r.width + HOLE_PADDING * 2}px`,
        height: `${r.height + HOLE_PADDING * 2}px`,
    };
});

const cardStyle = computed(() => {
    if (typeof window === 'undefined') return {};
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    if (!rect.value) {
        return {
            top: '50%',
            left: '50%',
            width: `${CARD_WIDTH}px`,
            transform: 'translate(-50%, -50%)',
        };
    }

    const r = rect.value;
    let top: number;
    let left: number;

    if (r.right + CARD_WIDTH + GAP < vw) {
        // Place to the right of the target (great for sidebar items).
        left = r.right + GAP;
        top = r.top;
    } else if (vh - r.bottom > 240) {
        // Place below the target.
        top = r.bottom + GAP;
        left = r.left;
    } else {
        // Place above the target.
        top = r.top - 240;
        left = r.left;
    }

    left = Math.min(Math.max(GAP, left), vw - CARD_WIDTH - GAP);
    top = Math.min(Math.max(GAP, top), vh - 240);

    return { top: `${top}px`, left: `${left}px`, width: `${CARD_WIDTH}px` };
});

const onWindowChange = () => measure();

watch(
    [isActive, currentIndex],
    async () => {
        if (!isActive.value) return;
        focusTarget();
    },
    { flush: 'post' },
);

watch(isActive, (active) => {
    if (active) {
        window.addEventListener('resize', onWindowChange);
        window.addEventListener('scroll', onWindowChange, true);
    } else {
        window.removeEventListener('resize', onWindowChange);
        window.removeEventListener('scroll', onWindowChange, true);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', onWindowChange);
    window.removeEventListener('scroll', onWindowChange, true);
    window.clearTimeout(scrollTimer);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="tour-fade">
            <div v-if="isActive && currentStep" class="fixed inset-0 z-100">
                <!-- Dim overlay with a spotlight cut-out -->
                <div
                    v-if="holeStyle"
                    class="pointer-events-none absolute rounded-lg ring-2 ring-primary transition-all duration-200"
                    :style="{
                        ...holeStyle,
                        boxShadow: '0 0 0 9999px rgba(0,0,0,0.6)',
                    }"
                />
                <div v-else class="absolute inset-0 bg-black/60" />

                <!-- Click-catcher to advance / skip without interacting with the page -->
                <div class="absolute inset-0" @click="next" />

                <!-- Tooltip card -->
                <div
                    class="absolute rounded-xl border border-border bg-popover p-4 text-popover-foreground shadow-2xl"
                    :style="cardStyle"
                    @click.stop
                >
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-xs font-medium text-muted-foreground">{{ progress }}</span>
                        <button
                            type="button"
                            class="text-xs text-muted-foreground hover:text-foreground"
                            @click="stop()"
                        >
                            Skip
                        </button>
                    </div>
                    <h3 class="text-base font-semibold">{{ currentStep.title }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">{{ currentStep.body }}</p>
                    <div class="mt-4 flex items-center justify-between gap-2">
                        <Button
                            v-if="!isFirst"
                            variant="ghost"
                            size="sm"
                            @click="prev()"
                        >
                            Back
                        </Button>
                        <span v-else />
                        <Button size="sm" @click="next()">
                            {{ isLast ? 'Finish' : 'Next' }}
                        </Button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.tour-fade-enter-active,
.tour-fade-leave-active {
    transition: opacity 0.2s ease;
}
.tour-fade-enter-from,
.tour-fade-leave-to {
    opacity: 0;
}
</style>
