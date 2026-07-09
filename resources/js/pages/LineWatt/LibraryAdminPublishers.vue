<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    createHref: string;
    publishers: {
        data: Array<any>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
</script>

<template>
    <Head title="Publishers" />

    <LibraryAdminShell
        title="Publishers"
        subtitle="Manage Library Publisher accounts and review their publishing performance."
        :role-label="roleLabel"
        :primary-action="{ label: 'Add New Publisher', href: createHref }"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Library Management' },
            { label: 'Publishers' },
        ]"
    >
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="font-black text-slate-950">Library Publishers</h2>
                <p class="mt-1 text-sm text-slate-600">Open a publisher to inspect performance, unreviewed work, and records needing attention.</p>
            </div>

            <div v-if="publishers.data.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Publisher</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Work</th>
                            <th class="px-4 py-3">Needs Attention</th>
                            <th class="px-4 py-3">Last Activity</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="publisher in publishers.data" :key="publisher.id">
                            <td class="px-4 py-4">
                                <div class="font-black text-slate-950">{{ publisher.name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ publisher.email }}</div>
                            </td>
                            <td class="px-4 py-4 capitalize">{{ publisher.status }}</td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">{{ publisher.datasheets }} datasheets</div>
                                <div class="mt-1 text-xs text-slate-500">{{ publisher.records }} Engineering Data</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-black" :class="publisher.needs_attention ? 'text-amber-700' : 'text-slate-600'">{{ publisher.needs_attention }}</span>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ publisher.last_activity || '—' }}</td>
                            <td class="px-4 py-4 text-right">
                                <Link :href="publisher.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">
                                    Open
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="p-10 text-center">
                <h2 class="font-black text-slate-950">No publishers yet</h2>
                <p class="mt-2 text-sm text-slate-600">Add a Library Publisher to begin assigning upload, review and submission work.</p>
            </div>
        </section>

        <nav v-if="publishers.links?.length > 3" class="mt-6 flex flex-wrap gap-2">
            <Link
                v-for="link in publishers.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-md border px-3 py-2 text-sm font-bold"
                :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'"
                v-html="link.label"
            />
        </nav>
    </LibraryAdminShell>
</template>
