<script setup lang="ts">
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { Head, Link } from '@inertiajs/vue3';
type Item = Record<string, any>;
defineProps<{ outstanding: Item[]; accepted: Item[] }>();
</script>

<template>
    <Head title="Legal Status" />
    <div class="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-white">
        <WorkspaceNavigation />
        <main class="mx-auto max-w-5xl px-4 py-12 sm:px-6">
            <h1 class="text-3xl font-black">Agreements &amp; Acceptances</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-300">Your own governed legal requirements and acceptance history.</p>
            <section class="mt-8"><h2 class="text-xl font-black">Outstanding ({{ outstanding.length }})</h2><div class="mt-4 grid gap-3"><article v-for="item in outstanding" :key="item.id" class="rounded-lg border bg-white p-5 dark:border-slate-700 dark:bg-slate-900"><b>{{ item.document }} · {{ item.version }}</b><p class="text-sm text-slate-500">{{ item.workflow }} · {{ item.blocking ? 'Required before access' : 'Optional' }}</p><Link href="/legal/acceptance" class="mt-2 inline-block font-bold text-emerald-700">Review requirement</Link></article><p v-if="!outstanding.length" class="text-slate-500">No outstanding requirements.</p></div></section>
            <section class="mt-10"><h2 class="text-xl font-black">Accepted ({{ accepted.length }})</h2><div class="mt-4 overflow-hidden rounded-lg border bg-white dark:border-slate-700 dark:bg-slate-900"><div v-for="item in accepted" :key="item.reference" class="border-b p-4 last:border-b-0"><b>{{ item.document }} · {{ item.version }}</b><p class="text-sm text-slate-500">{{ item.workflow }} · {{ item.accepted_at }} · Evidence {{ item.reference }}</p></div><p v-if="!accepted.length" class="p-5 text-slate-500">No acceptance evidence recorded.</p></div></section>
        </main>
    </div>
</template>
