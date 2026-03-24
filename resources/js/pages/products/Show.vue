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

async function toggleLike() {
    if (!auth.value?.user) {
        router.visit('/login');
        return;
    }
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
                                @click="activeTab = 'abstract'"
                            >
                                Abstract
                            </button>
                            <button
                                v-if="product.table_of_content"
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="activeTab === 'toc' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="activeTab = 'toc'"
                            >
                                Table of Content
                            </button>
                            <button
                                v-if="product.chapter_one"
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="activeTab === 'chapter1' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="activeTab = 'chapter1'"
                            >
                                Chapter 1
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="activeTab === 'reviews' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="activeTab = 'reviews'"
                            >
                                Reviews ({{ reviewsCount }})
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="activeTab === 'messenger' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="activeTab = 'messenger'"
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
                                            <Input id="msg-email" type="email" v-model="msgForm.sender_email" required />
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
                                <a v-else :href="`/download/${product.id}`" class="w-full">
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
