<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    company: { name: string; plan_label: string };
    companyDocuments: Array<Record<string, string>>;
    datasheetDocuments: Array<Record<string, string>>;
}>();

const columns = ['Scope', 'Document title', 'Category', 'Related datasheet / family / model', 'Revision', 'Language', 'Status', 'Uploaded', 'Actions'];
</script>

<template>
    <Head title="Supporting Documents" />

    <ManufacturerAdminShell
        :company="company"
        title="Supporting Documents"
        subtitle="Manage manufacturer-level documents separately from datasheet, family and model-specific documents."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Supporting Documents' }]"
    >
        <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-5">
                <section id="company-wide" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black">Company-wide Supporting Documents</h2>
                            <p class="mt-1 max-w-4xl text-sm leading-6 text-slate-600">Corporate and manufacturer-level assets: company profile, corporate certifications, factory certificates, ESG documents, warranty policy, bankability documents, brand assets, service policy and regional contacts.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ companyDocuments.length }} items</span>
                    </div>
                    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                                <tr>
                                    <th v-for="column in columns" :key="column" class="px-3 py-3 text-left">{{ column }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(row, rowIndex) in companyDocuments" :key="rowIndex">
                                    <td v-for="column in columns" :key="column" class="px-3 py-4">
                                        <span v-if="column === 'Scope'" class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-black text-sky-800 ring-1 ring-sky-200">{{ row[column] || 'Pending' }}</span>
                                        <span v-else>{{ row[column] || 'Pending' }}</span>
                                    </td>
                                </tr>
                                <tr v-if="companyDocuments.length === 0">
                                    <td :colspan="columns.length" class="px-3 py-10 text-center text-slate-600">No company-wide supporting documents have been uploaded yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">
                        Upload, replace and history actions are prepared for the next document-management workflow milestone.
                    </div>
                </section>

                <section id="datasheet-specific" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black">Datasheet-specific Supporting Documents</h2>
                            <p class="mt-1 max-w-4xl text-sm leading-6 text-slate-600">Documents tied to a datasheet, family, revision or model: IEC certificates, fire certificates, family warranty documents, installation manuals, CAD/BIM files, firmware/software, images, videos and application notes.</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ datasheetDocuments.length }} items</span>
                    </div>
                    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                                <tr>
                                    <th v-for="column in columns" :key="column" class="px-3 py-3 text-left">{{ column }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(row, rowIndex) in datasheetDocuments" :key="rowIndex">
                                    <td v-for="column in columns" :key="column" class="px-3 py-4">
                                        <span v-if="column === 'Scope'" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-800 ring-1 ring-emerald-200">{{ row[column] || 'Pending' }}</span>
                                        <span v-else>{{ row[column] || 'Pending' }}</span>
                                    </td>
                                </tr>
                                <tr v-if="datasheetDocuments.length === 0">
                                    <td :colspan="columns.length" class="px-3 py-10 text-center text-slate-600">No datasheet-specific supporting documents have been linked yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">
                        Upload, replace and history actions are prepared for the next document-management workflow milestone.
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                    <h2 class="text-sm font-black uppercase tracking-[0.14em] text-emerald-800">Scope rule</h2>
                    <p class="mt-3 text-sm leading-6 text-emerald-950">
                        Every supporting document has one explicit primary scope. It may reference both the company and a datasheet, but the UI must always show whether it is Company-wide or Datasheet-specific.
                    </p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-black">Navigation</h2>
                    <div class="mt-4 grid gap-2 text-sm font-bold">
                        <a href="#company-wide" class="rounded-md border border-slate-200 px-3 py-2 hover:bg-slate-50">Company-wide documents</a>
                        <a href="#datasheet-specific" class="rounded-md border border-slate-200 px-3 py-2 hover:bg-slate-50">Datasheet-specific documents</a>
                    </div>
                </div>
            </aside>
        </section>
    </ManufacturerAdminShell>
</template>
