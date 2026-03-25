<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Store, Upload, X } from 'lucide-vue-next';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Seller Profile', href: '/dashboard/seller/profile' },
];

const props = defineProps<{
    profile: {
        bio: string | null;
        company: string | null;
        phone: string | null;
        country: string | null;
        region_state: string | null;
        preferred_payment_method_id: number | null;
        bank_account_details: string | null;
        company_logo: string | null;
        banner: string | null;
    };
    paymentMethods: Array<{ id: number; name: string }>;
    countries: Array<{ id: number; name: string; code: string }>;
}>();

const form = useForm({
    bio: props.profile.bio || '',
    company: props.profile.company || '',
    phone: props.profile.phone || '',
    country: props.profile.country || '',
    region_state: props.profile.region_state || '',
    preferred_payment_method_id: props.profile.preferred_payment_method_id || '',
    bank_account_details: props.profile.bank_account_details || '',
    company_logo: null as File | null,
    banner: null as File | null,
});

const logoPreview = ref<string | null>(props.profile.company_logo ? `/storage/${props.profile.company_logo}` : null);
const bannerPreview = ref<string | null>(props.profile.banner ? `/storage/${props.profile.banner}` : null);

const handleLogo = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        form.company_logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const handleBanner = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        form.banner = file;
        bannerPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post('/dashboard/seller/profile', {
        preserveScroll: true,
        forceFormData: true,
    });
};

const page = usePage();
const flash = computed(() => (page.props.flash as any)?.success);
</script>

<template>
    <Head title="Seller Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div>
                <h1 class="text-2xl font-bold">Seller Profile</h1>
                <p class="text-sm text-muted-foreground">Update your seller information and branding</p>
            </div>

            <div v-if="flash" class="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">
                {{ flash }}
            </div>

            <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-2">
                <!-- Business Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Business Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label for="company">Company / Business Name</Label>
                            <Input id="company" v-model="form.company" placeholder="Your business name" />
                            <p v-if="form.errors.company" class="mt-1 text-xs text-red-500">{{ form.errors.company }}</p>
                        </div>

                        <div>
                            <Label for="phone">Phone</Label>
                            <Input id="phone" v-model="form.phone" placeholder="+234 ..." />
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <Label for="country">Country</Label>
                            <select id="country" v-model="form.country" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                                <option value="">Select country</option>
                                <option v-for="c in countries" :key="c.id" :value="c.name">{{ c.name }}</option>
                            </select>
                            <p v-if="form.errors.country" class="mt-1 text-xs text-red-500">{{ form.errors.country }}</p>
                        </div>

                        <div>
                            <Label for="region_state">Region / State</Label>
                            <Input id="region_state" v-model="form.region_state" placeholder="Your region or state" />
                            <p v-if="form.errors.region_state" class="mt-1 text-xs text-red-500">{{ form.errors.region_state }}</p>
                        </div>

                        <div>
                            <Label for="bio">Bio / About</Label>
                            <RichTextEditor
                                v-model="form.bio"
                                placeholder="Tell buyers about yourself or your business..."
                            />
                            <p v-if="form.errors.bio" class="mt-1 text-xs text-red-500">{{ form.errors.bio }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment & Branding -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Payment Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <Label for="payment_method">Preferred Payment Method</Label>
                                <select id="payment_method" v-model="form.preferred_payment_method_id" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                                    <option value="">Select method</option>
                                    <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">{{ pm.name }}</option>
                                </select>
                                <p v-if="form.errors.preferred_payment_method_id" class="mt-1 text-xs text-red-500">{{ form.errors.preferred_payment_method_id }}</p>
                            </div>

                            <div>
                                <Label for="bank_details">Bank Account / Payment Details</Label>
                                <textarea
                                    id="bank_details"
                                    v-model="form.bank_account_details"
                                    rows="3"
                                    class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    placeholder="Bank name, account number, etc."
                                />
                                <p v-if="form.errors.bank_account_details" class="mt-1 text-xs text-red-500">{{ form.errors.bank_account_details }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Branding</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Logo -->
                            <div>
                                <Label>Company Logo</Label>
                                <div class="mt-2 flex items-center gap-4">
                                    <div class="h-20 w-20 overflow-hidden rounded-lg border bg-muted">
                                        <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="h-full w-full object-cover" />
                                        <div v-else class="flex h-full w-full items-center justify-center">
                                            <Store class="h-8 w-8 text-muted-foreground/30" />
                                        </div>
                                    </div>
                                    <label class="cursor-pointer rounded-md border px-3 py-2 text-sm hover:bg-muted transition-colors">
                                        <Upload class="mr-1 inline h-4 w-4" /> Upload
                                        <input type="file" accept="image/*" class="hidden" @change="handleLogo" />
                                    </label>
                                </div>
                                <p v-if="form.errors.company_logo" class="mt-1 text-xs text-red-500">{{ form.errors.company_logo }}</p>
                            </div>

                            <!-- Banner -->
                            <div>
                                <Label>Banner Image</Label>
                                <div class="mt-2">
                                    <div class="aspect-[3/1] overflow-hidden rounded-lg border bg-muted">
                                        <img v-if="bannerPreview" :src="bannerPreview" alt="Banner" class="h-full w-full object-cover" />
                                        <div v-else class="flex h-full w-full items-center justify-center">
                                            <span class="text-sm text-muted-foreground/50">No banner</span>
                                        </div>
                                    </div>
                                    <label class="mt-2 inline-flex cursor-pointer items-center rounded-md border px-3 py-2 text-sm hover:bg-muted transition-colors">
                                        <Upload class="mr-1 h-4 w-4" /> Upload Banner
                                        <input type="file" accept="image/*" class="hidden" @change="handleBanner" />
                                    </label>
                                </div>
                                <p v-if="form.errors.banner" class="mt-1 text-xs text-red-500">{{ form.errors.banner }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Submit -->
                <div class="lg:col-span-2">
                    <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
                        {{ form.processing ? 'Saving...' : 'Save Profile' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
