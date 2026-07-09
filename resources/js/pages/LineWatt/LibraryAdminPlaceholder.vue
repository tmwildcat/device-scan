<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    section: string;
    title: string;
    description: string;
    items: string[];
}>();
</script>

<template>
    <Head :title="title" />

    <LibraryAdminShell
        :title="title"
        :subtitle="description"
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: section },
            { label: title },
        ]"
    >
        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">{{ section }}</p>
                <h2 class="mt-2 text-2xl font-black text-slate-950">{{ title }}</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ description }}</p>

                <div class="mt-6 grid gap-3 md:grid-cols-2">
                    <article v-for="item in items" :key="item" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h3 class="font-black text-slate-950">{{ item }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Queue, owner, evidence, comments, and resolution state will be wired here.</p>
                    </article>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black">Operator Notes</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        This is a scoped placeholder inside the Library Admin console. It marks the operational boundary without modifying compiler, storage, billing, or MCP systems.
                    </p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black">Related Work</h3>
                    <div class="mt-3 grid gap-2">
                        <Link href="/admin/library/approval-queue?view=pending_approval" class="rounded-md border border-slate-200 px-3 py-2 text-sm font-bold hover:bg-slate-50">Review & Approval</Link>
                        <Link href="/admin/library/oem-subscribers" class="rounded-md border border-slate-200 px-3 py-2 text-sm font-bold hover:bg-slate-50">OEM Subscribers</Link>
                        <Link href="/admin/library/power-search" class="rounded-md border border-slate-200 px-3 py-2 text-sm font-bold hover:bg-slate-50">Power Search Taxonomy</Link>
                    </div>
                </div>
            </aside>
        </section>
    </LibraryAdminShell>
</template>
