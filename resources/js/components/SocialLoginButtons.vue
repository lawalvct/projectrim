<script setup lang="ts">
import { Button } from '@/components/ui/button';
</script>

<template>
    <div class="grid gap-3">
        <div class="relative text-center text-sm after:absolute after:inset-0 after:top-1/2 after:z-0 after:flex after:items-center after:border-t after:border-border">
            <span class="relative z-10 bg-background px-2 text-muted-foreground">Or continue with</span>
        </div>

        <div class="grid grid-cols-4 gap-3">
            <Button variant="outline" as-child class="w-full">
                <a href="/auth/social/google/redirect">
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span class="sr-only">Google</span>
                </a>
            </Button>

            <Button variant="outline" as-child class="w-full">
                <a href="/auth/social/facebook/redirect">
                    <svg class="h-5 w-5" fill="#1877F2" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span class="sr-only">Facebook</span>
                </a>
            </Button>

            <Button variant="outline" as-child class="w-full">
                <a href="/auth/social/twitter/redirect">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    <span class="sr-only">X (Twitter)</span>
                </a>
            </Button>

            <Button variant="outline" as-child class="w-full">
                <a href="/auth/social/apple/redirect">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.53-3.23 0-1.44.66-2.2.47-3.06-.4C3.79 16.17 4.36 9.02 8.93 8.75c1.27.07 2.16.72 2.91.77.98-.2 1.92-.77 2.97-.7 1.26.1 2.2.6 2.82 1.5-2.59 1.55-1.97 4.96.49 5.92-.58 1.52-1.33 3.02-3.07 4.04zM12.03 8.67c-.13-2.21 1.67-4.07 3.82-4.17.28 2.38-2.17 4.42-3.82 4.17z"/>
                    </svg>
                    <span class="sr-only">Apple</span>
                </a>
            </Button>
        </div>
    </div>
</template>
