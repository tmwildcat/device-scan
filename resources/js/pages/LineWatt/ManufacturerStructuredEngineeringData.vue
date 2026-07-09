<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ company: { name: string }; records: { data: Array<any>; links: Array<{ url: string | null; label: string; active: boolean }> } }>();
</script>

<template>
    <Head title="Structured Engineering Data" />
    <ManufacturerAdminShell
        :company="company"
        title="Structured Engineering Data"
        subtitle="Derived structured data created by LineWatt from datasheets."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Structured Engineering Data' }]"
    >
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                            <tr>
                                <th class="px-3 py-3 text-left">Model</th>
                                <th class="px-3 py-3 text-left">Datasheet</th>
                                <th class="px-3 py-3 text-left">Version</th>
                                <th class="px-3 py-3 text-left">Status</th>
                                <th class="px-3 py-3 text-left">Compiler Version</th>
                                <th class="px-3 py-3 text-left">Updated</th>
                                <th class="px-3 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="record in records.data" :key="record.uuid || record.id">
                                <td class="px-3 py-4 font-black">{{ record.model }}</td>
                                <td class="px-3 py-4"><a v-if="record.datasheet_href" :href="record.datasheet_href" class="font-bold text-emerald-700">{{ record.datasheet }}</a><span v-else>{{ record.datasheet }}</span></td>
                                <td class="px-3 py-4">{{ record.version }}</td>
                                <td class="px-3 py-4 capitalize">{{ record.status }}</td>
                                <td class="px-3 py-4">{{ record.compiler_version || 'Pending' }}</td>
                                <td class="px-3 py-4">{{ record.updated || 'Pending' }}</td>
                                <td class="px-3 py-4">
                                    <div class="flex justify-end">
                                        <a :href="record.review_href || record.open_href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">Open</a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="records.data.length === 0">
                                <td colspan="7" class="px-3 py-10 text-center text-slate-600">No structured engineering data found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="records.links?.length" class="mt-5 flex flex-wrap gap-2">
                    <a v-for="link in records.links" :key="link.label" :href="link.url || '#'" class="rounded-md border px-3 py-2 text-sm font-bold" :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 text-slate-700 hover:bg-slate-50' : 'pointer-events-none border-slate-100 text-slate-300'" v-html="link.label" />
                </div>
            </section>
    </ManufacturerAdminShell>
</template>
