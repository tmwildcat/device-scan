<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardCheck, Library, Search } from 'lucide-vue-next';
</script>

<template>
    <Head title="Central Engineering Workspace" />

    <div class="mx-auto max-w-7xl space-y-8 p-6">
        <div>
            <p class="text-sm font-semibold text-emerald-600">
                LineWatt Library
            </p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                Central Engineering Workspace
            </h1>
            <p class="mt-3 max-w-3xl text-slate-600 dark:text-slate-400">
                Placeholder workspace for curated Engineering Record review and publishing. Write actions are not
                implemented in this UI shell milestone.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <Link
                href="/search"
                class="rounded-lg border bg-white p-6 text-left shadow-sm transition hover:border-emerald-500 hover:shadow dark:bg-slate-900"
            >
                <Search class="mb-4 size-7 text-emerald-600" />
                <h2 class="font-semibold">Engineering Search</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Search published and approved central Engineering Records.
                </p>
            </Link>
            <div class="rounded-lg border bg-white p-6 text-left shadow-sm dark:bg-slate-900">
                <ClipboardCheck class="mb-4 size-7 text-amber-600" />
                <h2 class="font-semibold">Review Queue</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Review workflow placeholder for librarian and admin roles.
                </p>
            </div>
            <div class="rounded-lg border bg-white p-6 text-left shadow-sm dark:bg-slate-900">
                <Library class="mb-4 size-7 text-sky-600" />
                <h2 class="font-semibold">Published Records</h2>
                <p class="mt-2 text-sm text-slate-500">
                    Curated library management will be wired after the UI shell.
                </p>
            </div>
        </div>
    </div>
</template>
