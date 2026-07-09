<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    backHref: string;
}>();
</script>

<template>
    <Head title="Add New Publisher" />

    <LibraryAdminShell
        title="Add New Publisher"
        subtitle="Publisher invitation and account creation placeholder."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Library Management' },
            { label: 'Publishers', href: '/admin/library/publishers' },
            { label: 'Add New' },
        ]"
    >
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Publisher invitation workflow coming next</h2>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">
                This screen will invite a Library Publisher, assign upload/review permissions, and track their onboarding status. For now, demo Publisher users are seeded and managed by development data.
            </p>
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Name</span>
                    <input disabled class="mt-2 w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-slate-400" placeholder="Publisher name" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Email</span>
                    <input disabled class="mt-2 w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-slate-400" placeholder="publisher@example.com" />
                </label>
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <button disabled class="rounded-md bg-slate-300 px-4 py-3 text-sm font-black text-white">Send Invitation</button>
                <Link :href="backHref" class="rounded-md border border-slate-200 px-4 py-3 text-sm font-black text-slate-700 hover:bg-slate-50">Back to Publishers</Link>
            </div>
        </section>
    </LibraryAdminShell>
</template>
