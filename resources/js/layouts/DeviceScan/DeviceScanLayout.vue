<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-white">
        <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <Link href="/" class="text-lg font-bold">
                    LineWatt <span class="text-emerald-500">DeviceScan</span>
                </Link>

                <nav class="flex items-center gap-4 text-sm">
                    <Link href="/dashboard" class="text-slate-600 hover:text-slate-900 dark:text-slate-300">
                        Dashboard
                    </Link>
                    <Link href="/logout" method="post" as="button" class="text-slate-600 hover:text-slate-900 dark:text-slate-300">
                        Logout
                    </Link>
                </nav>
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>