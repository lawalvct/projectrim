import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

export type TourStep = {
    /** CSS selector for the element to highlight. Omit for a centered step. */
    target?: string;
    title: string;
    body: string;
};

const TOUR_STORAGE_KEY = 'projectrim.dashboardTour.v1';
const REPLAY_FLAG = 'projectrim.replayTour';
const DASHBOARD_PATH = '/dashboard';

const isActive = ref(false);
const steps = ref<TourStep[]>([]);
const currentIndex = ref(0);

const dashboardSteps: TourStep[] = [
    {
        title: 'Welcome to ProjectRim',
        body: "Let's take a quick tour of your dashboard so you know where everything lives. It only takes a few seconds.",
    },
    {
        target: '[data-tour="explore"]',
        title: 'Explore Products',
        body: 'Browse and search research projects shared by creators across the platform. Find the work that fits your topic and download it instantly.',
    },
    {
        target: '[data-tour="downloads"]',
        title: 'My Downloads',
        body: 'Every project you download is saved here, so you can re-access your files anytime without buying again.',
    },
    {
        target: '[data-tour="orders"]',
        title: 'My Orders',
        body: 'Track your purchases, view order details, and check the status of each transaction in one place.',
    },
    {
        target: '[data-tour="messages"]',
        title: 'Messages',
        body: 'Chat with creators about their work and receive important notifications from the platform here.',
    },
    {
        target: '[data-tour="apply-seller"]',
        title: 'Become a Creator',
        body: 'Want to earn? Click "Apply Now" to become a creator and start publishing and selling your own research projects.',
    },
    {
        target: '[data-tour="user-menu"]',
        title: 'Your Account',
        body: 'Click your name here in the bottom-left corner to open Settings (update your profile and password) or to Log out. You can also replay this tour from this menu anytime.',
    },
    {
        title: "You're all set! ✅",
        body: 'That\'s the tour. You can replay it anytime from the menu under your name in the bottom-left corner.',
    },
];

function isMobileViewport() {
    return typeof window !== 'undefined' && window.matchMedia('(max-width: 768px)').matches;
}

function start(tourSteps: TourStep[] = dashboardSteps) {
    if (isMobileViewport()) {
        return;
    }

    steps.value = tourSteps;
    currentIndex.value = 0;
    isActive.value = true;
}

function stop(markComplete = true) {
    isActive.value = false;
    if (markComplete && typeof window !== 'undefined') {
        localStorage.setItem(TOUR_STORAGE_KEY, '1');
    }
}

function next() {
    if (currentIndex.value < steps.value.length - 1) {
        currentIndex.value += 1;
    } else {
        stop();
    }
}

function prev() {
    if (currentIndex.value > 0) {
        currentIndex.value -= 1;
    }
}

function hasCompleted() {
    return typeof window !== 'undefined' && localStorage.getItem(TOUR_STORAGE_KEY) === '1';
}

/**
 * Auto-starts the dashboard tour for newly registered users (those who have
 * never completed it) or when a replay was requested from another page.
 */
function maybeAutoStart() {
    if (typeof window === 'undefined') return;

    if (sessionStorage.getItem(REPLAY_FLAG) === '1') {
        sessionStorage.removeItem(REPLAY_FLAG);
        window.setTimeout(() => start(), 400);
        return;
    }

    if (!hasCompleted()) {
        window.setTimeout(() => start(), 700);
    }
}

/** Replays the tour, navigating to the dashboard first when needed. */
function replay() {
    if (typeof window === 'undefined') return;

    if (window.location.pathname === DASHBOARD_PATH) {
        start();
    } else {
        sessionStorage.setItem(REPLAY_FLAG, '1');
        router.visit(DASHBOARD_PATH);
    }
}

export function useTour() {
    return {
        isActive,
        steps,
        currentIndex,
        dashboardSteps,
        start,
        stop,
        next,
        prev,
        hasCompleted,
        maybeAutoStart,
        replay,
    };
}
