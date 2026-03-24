<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CreditCard, Check } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Payment Method', href: '/dashboard/seller/payment-method' },
];

const props = defineProps<{
    paymentMethods: Array<{ id: number; name: string }>;
    currentMethodId: number | null;
    bankAccountDetails: string | null;
}>();

const page = usePage();
const flash = computed(() => (page.props.flash as any)?.success);

const form = useForm({
    preferred_payment_method_id: props.currentMethodId || '',
    bank_account_details: props.bankAccountDetails || '',
});

const submit = () => {
    form.post('/dashboard/seller/payment-method', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Payment Method" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
            <div v-if="flash" class="rounded-md bg-green-50 p-4 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">
                {{ flash }}
            </div>

            <Card class="mx-auto w-full max-w-2xl">
                <CardHeader>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <CreditCard class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <CardTitle>Payment Method</CardTitle>
                            <CardDescription>Choose how you want to receive payouts.</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <Label for="method">Preferred Payment Method</Label>
                            <select
                                id="method"
                                v-model="form.preferred_payment_method_id"
                                class="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Select method</option>
                                <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">
                                    {{ pm.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.preferred_payment_method_id" class="mt-1 text-xs text-red-500">
                                {{ form.errors.preferred_payment_method_id }}
                            </p>
                        </div>

                        <div>
                            <Label for="bank">Bank Account / Payment Details</Label>
                            <textarea
                                id="bank"
                                v-model="form.bank_account_details"
                                rows="5"
                                class="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                placeholder="Enter your bank name, account number, routing number, or any other payment details..."
                            />
                            <p v-if="form.errors.bank_account_details" class="mt-1 text-xs text-red-500">
                                {{ form.errors.bank_account_details }}
                            </p>
                        </div>

                        <Button type="submit" :disabled="form.processing" class="w-full">
                            <Check class="mr-1 h-4 w-4" />
                            {{ form.processing ? 'Saving...' : 'Save Payment Method' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
