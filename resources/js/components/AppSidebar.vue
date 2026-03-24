<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BadgeDollarSign,
    Banknote,
    BookOpen,
    CreditCard,
    Download,
    FileText,
    LayoutGrid,
    Mail,
    Package,
    Search,
    ShoppingCart,
    Store,
    TrendingUp,
    Upload,
    User,
    UserPlus,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth?.user as any);
const isSeller = computed(() => user.value?.role === 'seller' || user.value?.role === 'admin');

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Explore Products', href: '/dashboard/explore', icon: Search },
    { title: 'My Downloads', href: '/dashboard/downloads', icon: Download },
    { title: 'My Orders', href: '/dashboard/orders', icon: ShoppingCart },
    { title: 'Messages', href: '/dashboard/messages', icon: Mail },
];

const sellerNavItems: NavItem[] = [
    { title: 'Seller Overview', href: '/dashboard/seller', icon: TrendingUp },
    { title: 'Seller Profile', href: '/dashboard/seller/profile', icon: Store },
    { title: 'Products', href: '/dashboard/seller/products', icon: Package },
    { title: 'Orders', href: '/dashboard/seller/orders', icon: FileText },
    { title: 'Transactions', href: '/dashboard/seller/transactions', icon: BadgeDollarSign },
    { title: 'Payments Given', href: '/dashboard/seller/payments', icon: Banknote },
    { title: 'Payout Requests', href: '/dashboard/seller/payouts', icon: Wallet },
    { title: 'Payment Method', href: '/dashboard/seller/payment-method', icon: CreditCard },
];

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <!-- Become a Seller CTA (only for regular users) -->
            <SidebarGroup v-if="!isSeller" class="px-2 py-0">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isCurrentUrl('/dashboard/apply-seller')" tooltip="Become a Seller">
                            <Link href="/dashboard/apply-seller">
                                <UserPlus />
                                <span>Become a Seller</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <!-- Seller section -->
            <template v-if="isSeller">
                <SidebarSeparator />
                <SidebarGroup class="px-2 py-0">
                    <SidebarGroupLabel>Seller</SidebarGroupLabel>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in sellerNavItems" :key="item.title">
                            <SidebarMenuButton as-child :is-active="isCurrentUrl(item.href)" :tooltip="item.title">
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>
            </template>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
