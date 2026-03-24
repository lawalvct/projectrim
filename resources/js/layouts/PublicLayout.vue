<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();
const auth = computed(() => page.props.auth as { user: any } | null);
const user = computed(() => auth.value?.user);
const navPages = computed(() => (page.props.navPages as any[]) || []);
const footerPages = computed(() => (page.props.footerPages as any[]) || []);
const siteName = computed(() => (page.props.name as string) || 'ProjectRim');
const settings = computed(() => (page.props.settings as Record<string, string>) || {});

const mobileMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen flex flex-col bg-background text-foreground">
        <!-- Header -->
        <header class="sticky top-0 z-50 w-full border-b bg-white dark:bg-gray-900">
            <div class="container mx-auto flex h-16 items-center justify-between px-4">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-2">
                    <img src="/images/logo.png" alt="ProjectRim" class="h-8" />
                </Link>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-6">
                    <Link href="/" class="text-sm font-medium hover:text-primary transition-colors">
                        Home
                    </Link>
                    <Link href="/products" class="text-sm font-medium hover:text-primary transition-colors">
                        Browse
                    </Link>
                    <Link
                        v-for="p in navPages"
                        :key="p.id"
                        :href="`/pages/${p.slug}`"
                        class="text-sm font-medium hover:text-primary transition-colors"
                    >
                        {{ p.title }}
                    </Link>
                </nav>

                <!-- Desktop Right -->
                <div class="hidden md:flex items-center gap-3">
                    <Link href="/cart" class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                        </svg>
                    </Link>

                    <template v-if="user">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="sm" class="gap-2">
                                    <span class="truncate max-w-[120px]">{{ user.name }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48">
                                <DropdownMenuItem as-child>
                                    <Link href="/dashboard">Dashboard</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link href="/settings/profile">Settings</Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem as-child>
                                    <Link href="/logout" method="post" as="button" class="w-full text-left">
                                        Log out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>
                    <template v-else>
                        <Link href="/login">
                            <Button variant="ghost" size="sm">Log in</Button>
                        </Link>
                        <Link href="/register">
                            <Button size="sm">Sign up</Button>
                        </Link>
                    </template>
                </div>

                <!-- Mobile Menu -->
                <Sheet v-model:open="mobileMenuOpen">
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon" class="md:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-72">
                        <div class="flex flex-col gap-4 pt-6">
                            <Link href="/" class="text-sm font-medium" @click="mobileMenuOpen = false">Home</Link>
                            <Link href="/products" class="text-sm font-medium" @click="mobileMenuOpen = false">Browse</Link>
                            <Link
                                v-for="p in navPages"
                                :key="p.id"
                                :href="`/pages/${p.slug}`"
                                class="text-sm font-medium"
                                @click="mobileMenuOpen = false"
                            >
                                {{ p.title }}
                            </Link>
                            <hr />
                            <template v-if="user">
                                <Link href="/dashboard" class="text-sm font-medium" @click="mobileMenuOpen = false">Dashboard</Link>
                                <Link href="/settings/profile" class="text-sm font-medium" @click="mobileMenuOpen = false">Settings</Link>
                                <Link href="/logout" method="post" as="button" class="text-sm font-medium text-left" @click="mobileMenuOpen = false">Log out</Link>
                            </template>
                            <template v-else>
                                <Link href="/login" class="text-sm font-medium" @click="mobileMenuOpen = false">Log in</Link>
                                <Link href="/register" class="text-sm font-medium" @click="mobileMenuOpen = false">Sign up</Link>
                            </template>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t bg-gray-50 dark:bg-gray-900">
            <div class="container mx-auto px-4 py-10">
                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <!-- Brand -->
                    <div>
                        <img src="/images/logo.png" alt="ProjectRim" class="h-8 mb-3" />
                        <p class="text-sm text-muted-foreground">
                            {{ settings.site_description || 'Your trusted marketplace for research papers and academic projects.' }}
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="font-semibold mb-3">Quick Links</h4>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li><Link href="/" class="hover:text-primary transition-colors">Home</Link></li>
                            <li><Link href="/products" class="hover:text-primary transition-colors">Browse Projects</Link></li>
                            <li v-for="p in footerPages" :key="p.id">
                                <Link :href="`/pages/${p.slug}`" class="hover:text-primary transition-colors">{{ p.title }}</Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact / Social -->
                    <div>
                        <h4 class="font-semibold mb-3">Connect</h4>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li v-if="settings.contact_email">
                                <a :href="`mailto:${settings.contact_email}`" class="hover:text-primary transition-colors">
                                    {{ settings.contact_email }}
                                </a>
                            </li>
                            <li v-if="settings.contact_phone">{{ settings.contact_phone }}</li>
                        </ul>
                        <div class="flex gap-3 mt-4">
                            <a v-if="settings.facebook_url" :href="settings.facebook_url" target="_blank" rel="noopener noreferrer" class="text-muted-foreground hover:text-primary">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a v-if="settings.twitter_url" :href="settings.twitter_url" target="_blank" rel="noopener noreferrer" class="text-muted-foreground hover:text-primary">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                            <a v-if="settings.linkedin_url" :href="settings.linkedin_url" target="_blank" rel="noopener noreferrer" class="text-muted-foreground hover:text-primary">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-8 border-t pt-6 text-center text-sm text-muted-foreground">
                    &copy; {{ new Date().getFullYear() }} {{ siteName }}. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>
