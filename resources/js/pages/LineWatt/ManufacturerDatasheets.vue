<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    company: any;
    datasheets: {
        data: Array<any>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
</script>

<template>
    <Head title="Manufacturer Datasheets" />

    <ManufacturerAdminShell
        :company="company"
        title="Datasheets"
        subtitle="Primary working page for source datasheets, revisions, review work and supporting document links."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Datasheets' }]"
        :primary-action="{ label: 'Upload Datasheet', href: '/partner/submissions/new' }"
    >
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                        <tr>
                            <th class="px-3 py-3 text-left">Datasheet</th>
                            <th class="px-3 py-3 text-left">Family / Series</th>
                            <th class="px-3 py-3 text-left">Models</th>
                            <th class="px-3 py-3 text-left">Revision</th>
                            <th class="px-3 py-3 text-left">Language</th>
                            <th class="px-3 py-3 text-left">Status</th>
                            <th class="px-3 py-3 text-left">Uploaded</th>
                            <th class="px-3 py-3 text-left">Current / Archived</th>
                            <th class="px-3 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="datasheet in datasheets.data" :key="datasheet.id">
                            <td class="px-3 py-4">
                                <div class="font-black">{{ datasheet.title }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ datasheet.filename }}</div>
                            </td>
                            <td class="px-3 py-4">{{ datasheet.family_series }}</td>
                            <td class="px-3 py-4">{{ datasheet.models_count }}</td>
                            <td class="px-3 py-4">{{ datasheet.revision }}</td>
                            <td class="px-3 py-4">{{ datasheet.language }}</td>
                            <td class="px-3 py-4 capitalize">{{ datasheet.status }}</td>
                            <td class="px-3 py-4">{{ datasheet.uploaded || 'Pending' }}</td>
                            <td class="px-3 py-4">{{ datasheet.archival_status }}</td>
                            <td class="px-3 py-4">
                                <div class="flex justify-end">
                                    <Link :href="datasheet.review_href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">Open</Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="datasheets.data.length === 0">
                            <td colspan="9" class="px-3 py-10 text-center text-slate-600">No datasheets found for this manufacturer.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="datasheets.links?.length" class="mt-5 flex flex-wrap gap-2">
                <Link v-for="link in datasheets.links" :key="link.label" :href="link.url || '#'" class="rounded-md border px-3 py-2 text-sm font-bold" :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 text-slate-700 hover:bg-slate-50' : 'pointer-events-none border-slate-100 text-slate-300'" v-html="link.label" />
            </div>
        </section>
    </ManufacturerAdminShell>
</template>
