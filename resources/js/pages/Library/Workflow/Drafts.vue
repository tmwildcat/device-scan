<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FilePenLine } from 'lucide-vue-next';
</script>

<template>
    <Head title="Draft Engineering Records" />

    <div class="mx-auto max-w-7xl p-6">
        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-slate-600 dark:bg-slate-900">
            <FilePenLine class="mb-4 size-8 text-slate-400" />
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                Draft Engineering Records
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6">
                Draft workflow placeholder. Upload, compile and review flows are intentionally outside this UI shell
                milestone.
            </p>
        </div>
    </div>
</template>
