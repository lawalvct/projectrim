<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { ref, computed } from 'vue';
import axios from 'axios';

interface ProductImage {
    id: number;
    path: string;
}

interface ProductFile {
    id: number;
    file_name: string;
    file_size: number;
    file_type: string;
}

interface ProductAuthor {
    id: number;
    user_id: number;
    is_primary: boolean;
    contribution_percentage: number;
    user: { id: number; name: string; email: string };
}

interface Review {
    id: number;
    rating: number;
    comment: string;
    created_at: string;
    user: { id: number; name: string; avatar: string | null };
}

interface ProductDetail {
    id: number;
    title: string;
    slug: string;
    abstract: string | null;
    table_of_content: string | null;
    chapter_one: string | null;
    meta_description: string | null;
    meta_keywords: string | null;
    document_type: string | null;
    class_of_degree: string | null;
    institution: string | null;
    location_country: string | null;
    location_region: string | null;
    date_available: string | null;
    price: number;
    is_paid: boolean;
    views_count: number;
    downloads_count: number;
    likes_count: number;
    created_at: string;
    published_at: string | null;
    user: { id: number; name: string; email: string; avatar: string | null };
    faculty: { id: number; name: string; slug: string } | null;
    department: { id: number; name: string; slug: string } | null;
    images: ProductImage[];
    files: ProductFile[];
    tags: Array<{ id: number; name: string; slug: string }>;
    authors: ProductAuthor[];
    reviews: Review[];
    reviews_count: number;
    likes_count_aggregate?: number;
    downloads_count_aggregate?: number;
}

const props = defineProps<{
    product: ProductDetail;
    relatedProducts: Array<{
        id: number;
        title: string;
        slug: string;
        price: number;
        is_paid: boolean;
        downloads_count: number;
        images: ProductImage[];
        user: { id: number; name: string };
    }>;
    isLiked: boolean;
    userReview: { id: number; rating: number; comment: string } | null;
}>();

const page = usePage();
const auth = computed(() => page.props.auth as { user: { id: number; name: string; email: string } | null } | undefined);
const settings = computed(() => (page.props.settings as Record<string, string>) || {});
const activeTab = ref<'abstract' | 'toc' | 'chapter1' | 'reviews' | 'messenger'>('abstract');

const activeImage = ref(0);

function formatSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function formatDate(date: string | null): string {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function renderStars(rating: number): string {
    return '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));
}

// --- Cart ---
const addingToCart = ref(false);

function addToCart() {
    addingToCart.value = true;
    router.post('/cart', { product_id: props.product.id }, {
        preserveScroll: true,
        onFinish: () => { addingToCart.value = false; },
    });
}

// --- Like ---
const liked = ref(props.isLiked);
const likesCount = ref(props.product.likes_count);
const togglingLike = ref(false);

// --- Share ---
const shareUrl = computed(() => typeof window !== 'undefined' ? `${window.location.origin}/products/${props.product.slug}` : '');
const shareText = computed(() => `Check out "${props.product.title}" on ${settings.value.site_name || 'ProjectRim'}`);
const copiedLink = ref(false);

async function copyShareLink() {
    try {
        await navigator.clipboard.writeText(shareText.value + ' ' + shareUrl.value);
        copiedLink.value = true;
        setTimeout(() => { copiedLink.value = false; }, 2000);
    } catch {}
}

// --- Smart Link ---
const smartLinkEnabled = computed(() => settings.value.smart_link_enabled === '1');
const smartLinkUrl = computed(() => settings.value.smart_link_url || '');

function triggerSmartLink() {
    if (!smartLinkEnabled.value || !smartLinkUrl.value) return;
    window.open(smartLinkUrl.value, '_blank', 'noopener,noreferrer');
}

function switchTab(tab: 'abstract' | 'toc' | 'chapter1' | 'reviews' | 'messenger') {
    if (tab !== 'abstract') triggerSmartLink();
    activeTab.value = tab;
}

async function toggleLike() {
    if (!auth.value?.user) {
        router.visit('/login');
        return;
    }
    triggerSmartLink();
    togglingLike.value = true;
    try {
        const { data } = await axios.post(`/products/${props.product.id}/like`);
        liked.value = data.liked;
        likesCount.value = data.likes_count;
    } catch {
        // silently fail
    } finally {
        togglingLike.value = false;
    }
}

// --- Reviews ---
const reviews = ref<Review[]>([...props.product.reviews]);
const reviewsCount = ref(props.product.reviews_count);
const hasReviewed = ref(!!props.userReview);
const reviewRating = ref(0);
const reviewComment = ref('');
const submittingReview = ref(false);
const reviewError = ref('');
const hoverRating = ref(0);

async function submitReview() {
    if (!auth.value?.user) {
        router.visit('/login');
        return;
    }
    if (reviewRating.value < 1 || reviewRating.value > 5) {
        reviewError.value = 'Please select a rating (1-5 stars).';
        return;
    }
    submittingReview.value = true;
    reviewError.value = '';
    try {
        const { data } = await axios.post(`/products/${props.product.id}/reviews`, {
            rating: reviewRating.value,
            comment: reviewComment.value || null,
        });
        reviews.value.unshift(data.review);
        reviewsCount.value = data.reviews_count;
        hasReviewed.value = true;
        reviewRating.value = 0;
        reviewComment.value = '';
    } catch (err: any) {
        reviewError.value = err.response?.data?.message || 'Failed to submit review.';
    } finally {
        submittingReview.value = false;
    }
}

// --- Messenger ---
const msgForm = ref({
    sender_name: auth.value?.user?.name || '',
    sender_email: auth.value?.user?.email || '',
    subject: '',
    body: '',
    honeypot: '',
});
const sendingMessage = ref(false);
const messageSent = ref(false);
const messageError = ref('');

async function sendMessage() {
    sendingMessage.value = true;
    messageError.value = '';
    messageSent.value = false;
    try {
        await axios.post(`/products/${props.product.id}/messages`, msgForm.value);
        messageSent.value = true;
        msgForm.value.subject = '';
        msgForm.value.body = '';
    } catch (err: any) {
        messageError.value = err.response?.data?.message || 'Failed to send message.';
    } finally {
        sendingMessage.value = false;
    }
}
</script>

<template>
    <Head :title="product.title" />

    <PublicLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Breadcrumb -->
            <nav class="mb-6 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-primary">Home</Link>
                <span class="mx-2">/</span>
                <Link href="/products" class="hover:text-primary">Projects</Link>
                <span v-if="product.faculty" class="mx-2">/</span>
                <Link v-if="product.faculty" :href="`/faculty/${product.faculty.slug}`" class="hover:text-primary">{{ product.faculty.name }}</Link>
                <span class="mx-2">/</span>
                <span>{{ product.title }}</span>
            </nav>

            <div class="grid gap-8 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Image Gallery -->
                    <div v-if="product.images.length">
                        <div class="aspect-[16/10] overflow-hidden rounded-lg bg-muted">
                            <img
                                :src="`/storage/${product.images[activeImage].path}`"
                                :alt="product.title"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <div v-if="product.images.length > 1" class="mt-3 flex gap-2">
                            <button
                                v-for="(img, idx) in product.images"
                                :key="img.id"
                                class="h-16 w-16 overflow-hidden rounded-md border-2 transition-colors"
                                :class="idx === activeImage ? 'border-primary' : 'border-transparent hover:border-muted-foreground/50'"
                                @click="activeImage = idx"
                            >
                                <img :src="`/storage/${img.path}`" class="h-full w-full object-cover" />
                            </button>
                        </div>
                    </div>

                    <!-- Title & Meta -->
                    <div>
                        <h1 class="text-2xl font-bold lg:text-3xl">{{ product.title }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span>by <strong>{{ product.user.name }}</strong></span>
                            <span v-if="product.faculty">{{ product.faculty.name }}</span>
                            <span v-if="product.department">/ {{ product.department.name }}</span>
                        </div>
                        <div v-if="product.tags.length" class="mt-3 flex flex-wrap gap-1.5">
                            <Link v-for="tag in product.tags" :key="tag.id" :href="`/tags/${tag.slug}`">
                                <Badge variant="outline">{{ tag.name }}</Badge>
                            </Link>
                        </div>
                    </div>

                    <!-- Content Tabs -->
                    <div>
                        <div class="flex gap-1 rounded-lg border bg-muted p-1">
                            <button
                                v-if="product.abstract"
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="activeTab === 'abstract' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="switchTab('abstract')"
                            >
                                Abstract
                            </button>
                            <button
                                v-if="product.table_of_content"
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="activeTab === 'toc' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="switchTab('toc')"
                            >
                                Table of Content
                            </button>
                            <button
                                v-if="product.chapter_one"
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="activeTab === 'chapter1' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="switchTab('chapter1')"
                            >
                                Chapter 1
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="activeTab === 'reviews' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="switchTab('reviews')"
                            >
                                Reviews ({{ reviewsCount }})
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="activeTab === 'messenger' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="switchTab('messenger')"
                            >
                                Messenger
                            </button>
                        </div>

                        <div class="mt-4">
                            <div v-if="activeTab === 'abstract' && product.abstract" class="prose prose-sm max-w-none" v-html="product.abstract" />
                            <div v-else-if="activeTab === 'toc' && product.table_of_content" class="prose prose-sm max-w-none" v-html="product.table_of_content" />
                            <div v-else-if="activeTab === 'chapter1' && product.chapter_one" class="prose prose-sm max-w-none" v-html="product.chapter_one" />
                            <div v-else-if="activeTab === 'reviews'">
                                <!-- Review Form -->
                                <div v-if="auth?.user && !hasReviewed" class="mb-6 rounded-lg border p-4">
                                    <h4 class="mb-3 text-sm font-semibold">Write a Review</h4>
                                    <div v-if="reviewError" class="mb-3 rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">{{ reviewError }}</div>
                                    <div class="mb-3">
                                        <Label class="mb-1 block text-sm">Rating</Label>
                                        <div class="flex gap-1">
                                            <button
                                                v-for="star in 5"
                                                :key="star"
                                                type="button"
                                                class="text-2xl transition-colors"
                                                :class="(hoverRating || reviewRating) >= star ? 'text-yellow-500' : 'text-muted-foreground/30'"
                                                @mouseenter="hoverRating = star"
                                                @mouseleave="hoverRating = 0"
                                                @click="reviewRating = star"
                                            >
                                                ★
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <Label for="review-comment" class="mb-1 block text-sm">Comment (optional)</Label>
                                        <Textarea id="review-comment" v-model="reviewComment" rows="3" placeholder="Share your thoughts..." />
                                    </div>
                                    <Button size="sm" :disabled="submittingReview" @click="submitReview">
                                        {{ submittingReview ? 'Submitting...' : 'Submit Review' }}
                                    </Button>
                                </div>
                                <div v-else-if="!auth?.user" class="mb-6 rounded-lg border border-dashed p-4 text-center text-sm text-muted-foreground">
                                    <Link href="/login" class="text-primary hover:underline">Sign in</Link> to leave a review.
                                </div>

                                <div v-if="!reviews.length" class="py-8 text-center text-muted-foreground">
                                    No reviews yet. Be the first!
                                </div>
                                <div v-else class="space-y-4">
                                    <div v-for="review in reviews" :key="review.id" class="rounded-lg border p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="h-8 w-8 rounded-full bg-muted flex items-center justify-center text-sm font-medium">
                                                    {{ review.user.name.charAt(0) }}
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium">{{ review.user.name }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ formatDate(review.created_at) }}</div>
                                                </div>
                                            </div>
                                            <div class="text-yellow-500">{{ renderStars(review.rating) }}</div>
                                        </div>
                                        <p class="mt-2 text-sm">{{ review.comment }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Messenger Tab -->
                            <div v-else-if="activeTab === 'messenger'">
                                <div v-if="messageSent" class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                                    Message sent successfully! The author(s) will receive it shortly.
                                </div>
                                <div v-if="messageError" class="mb-4 rounded-md bg-destructive/10 px-4 py-3 text-sm text-destructive">{{ messageError }}</div>
                                <form class="space-y-4" @submit.prevent="sendMessage">
                                    <input type="text" name="honeypot" v-model="msgForm.honeypot" class="hidden" tabindex="-1" autocomplete="off" />
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label for="msg-name">Your Name</Label>
                                            <Input id="msg-name" v-model="msgForm.sender_name" required />
                                        </div>
                                        <div>
                                            <Label for="msg-email">Your Email</Label>
                                            <Input id="msg-email" type="email" v-model="msgForm.sender_email" required readonly />
                                        </div>
                                    </div>
                                    <div>
                                        <Label for="msg-subject">Subject</Label>
                                        <Input id="msg-subject" v-model="msgForm.subject" required />
                                    </div>
                                    <div>
                                        <Label for="msg-body">Message</Label>
                                        <Textarea id="msg-body" v-model="msgForm.body" rows="5" required placeholder="Write your message to the author(s)..." />
                                    </div>
                                    <Button type="submit" :disabled="sendingMessage">
                                        {{ sendingMessage ? 'Sending...' : 'Send Message' }}
                                    </Button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Price / Action Card -->
                    <Card>
                        <CardContent class="p-6">
                            <div class="text-center">
                                <div v-if="product.is_paid" class="text-3xl font-bold text-primary">
                                    {{ settings.currency_symbol || '$' }}{{ product.price }}
                                </div>
                                <div v-else class="text-3xl font-bold text-green-600">Free</div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <Button v-if="product.is_paid" class="w-full" size="lg" :disabled="addingToCart" @click="addToCart">
                                    {{ addingToCart ? 'Adding...' : 'Add to Cart' }}
                                </Button>
                                <a v-else :href="`/download/${product.id}`" class="w-full" @click="triggerSmartLink">
                                    <Button class="w-full" size="lg">
                                        Download Free
                                    </Button>
                                </a>
                            </div>

                            <Separator class="my-4" />

                            <!-- Stats -->
                            <div class="grid grid-cols-3 gap-2 text-center text-sm">
                                <div>
                                    <div class="font-semibold">{{ product.views_count }}</div>
                                    <div class="text-xs text-muted-foreground">Views</div>
                                </div>
                                <div>
                                    <div class="font-semibold">{{ product.downloads_count }}</div>
                                    <div class="text-xs text-muted-foreground">Downloads</div>
                                </div>
                                <div>
                                    <div class="font-semibold">{{ likesCount }}</div>
                                    <div class="text-xs text-muted-foreground">Likes</div>
                                </div>
                            </div>

                            <Separator class="my-4" />

                            <!-- Like Button -->
                            <Button
                                variant="outline"
                                class="w-full gap-2"
                                :disabled="togglingLike"
                                @click="toggleLike"
                            >
                                <span :class="liked ? 'text-red-500' : 'text-muted-foreground'">{{ liked ? '❤' : '♡' }}</span>
                                {{ liked ? 'Liked' : 'Like this project' }}
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Share Card -->
                    <Card>
                        <CardContent class="p-6">
                            <h3 class="mb-3 font-semibold">Share this project</h3>
                            <div class="flex gap-2">
                                <a
                                    :href="`https://wa.me/?text=${encodeURIComponent(shareText + ' ' + shareUrl)}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-[#25D366] text-white transition-opacity hover:opacity-80"
                                    title="Share on WhatsApp"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                                <a
                                    :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-[#1877F2] text-white transition-opacity hover:opacity-80"
                                    title="Share on Facebook"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <a
                                    :href="`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText)}&url=${encodeURIComponent(shareUrl)}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white transition-opacity hover:opacity-80"
                                    title="Share on X"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                                <a
                                    :href="`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-[#0A66C2] text-white transition-opacity hover:opacity-80"
                                    title="Share on LinkedIn"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-black text-white transition-opacity hover:opacity-80"
                                    :title="copiedLink ? 'Link copied!' : 'Copy link for TikTok'"
                                    @click="copyShareLink"
                                >
                                    <svg v-if="!copiedLink" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1v-3.51a6.37 6.37 0 0 0-.79-.05A6.34 6.34 0 0 0 3.15 15.2a6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V8.75a8.28 8.28 0 0 0 4.76 1.5v-3.45a4.85 4.85 0 0 1-1-.11z"/></svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-[#f09433] via-[#dc2743] to-[#bc1888] text-white transition-opacity hover:opacity-80"
                                    :title="copiedLink ? 'Link copied!' : 'Copy link for Instagram'"
                                    @click="copyShareLink"
                                >
                                    <svg v-if="!copiedLink" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Details Card -->
                    <Card>
                        <CardContent class="p-6 space-y-3">
                            <h3 class="font-semibold">Details</h3>
                            <dl class="space-y-2 text-sm">
                                <div v-if="product.document_type" class="flex justify-between">
                                    <dt class="text-muted-foreground">Type</dt>
                                    <dd>{{ product.document_type }}</dd>
                                </div>
                                <div v-if="product.class_of_degree" class="flex justify-between">
                                    <dt class="text-muted-foreground">Class</dt>
                                    <dd>{{ product.class_of_degree }}</dd>
                                </div>
                                <div v-if="product.institution" class="flex justify-between">
                                    <dt class="text-muted-foreground">Institution</dt>
                                    <dd>{{ product.institution }}</dd>
                                </div>
                                <div v-if="product.location_country" class="flex justify-between">
                                    <dt class="text-muted-foreground">Country</dt>
                                    <dd>{{ product.location_country }}</dd>
                                </div>
                                <div v-if="product.date_available" class="flex justify-between">
                                    <dt class="text-muted-foreground">Date</dt>
                                    <dd>{{ formatDate(product.date_available) }}</dd>
                                </div>
                                <div v-if="product.files.length" class="flex justify-between">
                                    <dt class="text-muted-foreground">File</dt>
                                    <dd>{{ product.files[0].file_type.toUpperCase() }} ({{ formatSize(product.files[0].file_size) }})</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <!-- Authors Card -->
                    <Card v-if="product.authors.length">
                        <CardContent class="p-6 space-y-3">
                            <h3 class="font-semibold">Authors</h3>
                            <div v-for="author in product.authors" :key="author.id" class="flex items-center justify-between text-sm">
                                <div>
                                    <div class="font-medium">{{ author.user.name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ author.is_primary ? 'Primary Author' : 'Co-Author' }}
                                    </div>
                                </div>
                                <Badge variant="outline">{{ author.contribution_percentage }}%</Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Related Products -->
            <section v-if="relatedProducts.length" class="mt-12">
                <h2 class="mb-6 text-xl font-bold">Related Projects</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Link v-for="rp in relatedProducts" :key="rp.id" :href="`/products/${rp.slug}`" class="group">
                        <Card class="h-full overflow-hidden transition-shadow hover:shadow-lg">
                            <div class="aspect-[4/3] bg-muted">
                                <img
                                    v-if="rp.images?.length"
                                    :src="`/storage/${rp.images[0].path}`"
                                    :alt="rp.title"
                                    class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                />
                            </div>
                            <CardContent class="p-4">
                                <h3 class="line-clamp-2 text-sm font-semibold group-hover:text-primary">{{ rp.title }}</h3>
                                <p class="mt-1 text-xs text-muted-foreground">by {{ rp.user.name }}</p>
                                <div class="mt-2 flex items-center justify-between">
                                    <Badge v-if="rp.is_paid" variant="secondary">{{ settings.currency_symbol || '$' }}{{ rp.price }}</Badge>
                                    <Badge v-else variant="outline" class="text-green-600">Free</Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>
