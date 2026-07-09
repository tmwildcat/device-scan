<script setup lang="ts">
import PdfViewer from '@/device-scan/components/pdf/PdfViewer.vue';
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    company: { name: string; plan_code: string; plan_label: string; can_upgrade: boolean; upgrade_message: string | null };
    datasheet: any;
    models: Array<any>;
    structuredEngineeringData: Array<any>;
    powerSearchCategories: Array<{ category: string; options: string[] }>;
    manufacturingLocations: Array<Record<string, string>>;
    supportingDocuments: Array<Record<string, string>>;
    history: Array<Record<string, string>>;
}>();

const tabs = ['Preview', 'Metadata', 'Library Compilation', 'Discoverability', 'Manufacturing', 'Supporting Documents', 'History'];
const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'Preview');
const activeModel = ref(props.models[0]?.id ?? null);
const selectedModel = computed(() => props.models.find((model) => model.id === activeModel.value) ?? props.models[0]);
</script>

<template>
    <Head :title="datasheet.title" />

    <ManufacturerAdminShell
        :company="company"
        :title="datasheet.title"
        :subtitle="`${datasheet.filename} · ${datasheet.family_series} · Revision ${datasheet.revision}`"
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Datasheets', href: '/admin/manufacturer/datasheets' }, { label: datasheet.title }]"
        :primary-action="{ label: 'Replace', href: datasheet.replace_href }"
    >
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex gap-2 overflow-x-auto border-b border-slate-200 px-4 py-3">
                    <button v-for="tab in tabs" :key="tab" type="button" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-black" :class="activeTab === tab ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50'" @click="activeTab = tab">{{ tab }}</button>
                </div>

                <div class="p-5">
                    <section v-if="activeTab === 'Preview'" class="grid gap-6 xl:grid-cols-[minmax(520px,1fr)_360px]">
                        <div>
                            <PdfViewer :src="datasheet.preview_href" />
                        </div>
                        <aside class="space-y-4">
                            <Info label="Revision" :value="datasheet.revision" />
                            <Info label="Language" :value="datasheet.language" />
                            <Info label="Publication date" :value="datasheet.publication_date || 'Pending'" />
                            <Info label="Effective date" :value="datasheet.effective_date || 'Pending'" />
                            <Info label="Supersedes revision" :value="datasheet.supersedes_revision || 'None'" />
                            <a :href="datasheet.preview_href" target="_blank" class="block rounded-md bg-slate-950 px-4 py-3 text-center text-sm font-black text-white">Open original PDF</a>
                            <a :href="datasheet.preview_href" target="_blank" class="block rounded-md border border-slate-200 bg-white px-4 py-3 text-center text-sm font-black text-slate-700 hover:bg-slate-50">Download original if permitted</a>
                        </aside>
                    </section>

                    <section v-if="activeTab === 'Metadata'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Editable label="Datasheet title" :value="datasheet.title" />
                        <Editable label="Family / Series" :value="datasheet.family_series" />
                        <Editable label="Revision" :value="datasheet.revision" />
                        <Editable label="Language" :value="datasheet.language" />
                        <Editable label="Publication date" :value="datasheet.publication_date || ''" />
                        <Editable label="Effective date" :value="datasheet.effective_date || ''" />
                        <Editable label="Supersedes revision" :value="datasheet.supersedes_revision || ''" />
                        <Editable label="Status" :value="datasheet.status" />
                        <Editable class="md:col-span-2 xl:col-span-3" label="Notes" :value="datasheet.notes || ''" multiline />
                        <p class="md:col-span-2 xl:col-span-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">Metadata editing UI is prepared; persistence will be connected in a later workflow milestone.</p>
                    </section>

                    <section v-if="activeTab === 'Library Compilation'">
                        <h2 class="text-lg font-black">Library Compilation</h2>
                        <p class="mt-1 text-sm text-slate-600">Structured compilation derived from this datasheet. A single datasheet may contain multiple models.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button v-for="model in models" :key="model.id" type="button" class="rounded-md px-3 py-2 text-sm font-black" :class="activeModel === model.id ? 'bg-slate-950 text-white' : 'border border-slate-200 bg-white text-slate-700'" @click="activeModel = model.id">{{ model.model }}</button>
                        </div>
                        <div v-if="selectedModel" class="mt-5 grid gap-6 xl:grid-cols-[340px_1fr]">
                            <aside class="rounded-lg border border-slate-200 p-4">
                                <Info label="Model" :value="selectedModel.model" />
                                <Info label="Power" :value="selectedModel.power" />
                                <Info label="Technology" :value="selectedModel.technology" />
                                <Info label="Review status" :value="selectedModel.structured_data_status" />
                                <Info label="Publication status" :value="selectedModel.status" />
                                <Link :href="selectedModel.review_href" class="mt-4 block rounded-md bg-slate-950 px-4 py-3 text-center text-sm font-black text-white">Review structured fields</Link>
                            </aside>
                            <div class="rounded-lg border border-slate-200 p-4">
                                <h3 class="font-black">Structured Engineering Data</h3>
                                <div class="mt-4 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <tbody class="divide-y divide-slate-100">
                                            <tr v-for="item in structuredEngineeringData.filter((row) => row.id === selectedModel.id)" :key="item.id">
                                                <td class="px-3 py-3 font-bold">Compiler Version</td><td class="px-3 py-3">{{ item.compiler_version || 'Pending' }}</td>
                                                <td class="px-3 py-3 font-bold">Validation</td><td class="px-3 py-3">{{ item.validation_grade || 'Pending' }}</td>
                                                <td class="px-3 py-3 font-bold">Updated</td><td class="px-3 py-3">{{ item.updated || 'Pending' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="mt-4 rounded-md bg-slate-50 p-3 text-sm text-slate-600">Source traceability is visible in the Review screen. Raw JSON remains hidden unless LINEWATT_LIB_DEBUG is enabled.</p>
                            </div>
                        </div>
                    </section>

                    <section v-if="activeTab === 'Discoverability'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div v-for="category in powerSearchCategories" :key="category.category" class="rounded-lg border border-slate-200 p-4">
                            <h3 class="font-black">{{ category.category }}</h3>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span v-for="option in category.options" :key="option" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ option }}</span>
                            </div>
                        </div>
                        <p class="md:col-span-2 xl:col-span-3 rounded-md border border-sky-200 bg-sky-50 p-3 text-sm font-bold text-sky-800">Policy and subsidy tags are curated reference data. They are not inferred from datasheets.</p>
                    </section>

                    <section v-if="activeTab === 'Manufacturing'">
                        <AdminTable :columns="['Factory name', 'Country', 'State', 'City', 'Product types', 'Production capacity', 'Certifications', 'Status']" :rows="manufacturingLocations" />
                    </section>

                    <section v-if="activeTab === 'Supporting Documents'">
                        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm font-bold text-emerald-900">
                            This tab shows datasheet-specific supporting documents only. Company-wide documents live under Company → Supporting Documents.
                        </div>
                        <AdminTable :columns="['Scope', 'Document title', 'Category', 'Related datasheet / family / model', 'Revision', 'Language', 'Status', 'Uploaded', 'Actions']" :rows="supportingDocuments" />
                    </section>

                    <section v-if="activeTab === 'History'">
                        <AdminTable :columns="['Event', 'When', 'Details']" :rows="history" />
                    </section>
                </div>
            </section>
    </ManufacturerAdminShell>
</template>

<script lang="ts">
export default {
    components: {
        Info: { props: ['label', 'value'], template: `<div class="rounded-lg border border-slate-200 bg-white p-4"><div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ label }}</div><div class="mt-1 font-black">{{ value || 'Pending' }}</div></div>` },
        Editable: { props: ['label', 'value', 'multiline'], template: `<label class="block"><span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ label }}</span><textarea v-if="multiline" class="mt-2 min-h-28 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" :value="value" /><input v-else class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" :value="value" /></label>` },
        AdminTable: { props: ['columns', 'rows'], template: `<div class="overflow-x-auto rounded-lg border border-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500"><tr><th v-for="column in columns" :key="column" class="px-3 py-3 text-left">{{ column }}</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="(row, index) in rows" :key="index"><td v-for="column in columns" :key="column" class="px-3 py-4">{{ row[column] || 'Pending' }}</td></tr><tr v-if="rows.length === 0"><td :colspan="columns.length" class="px-3 py-8 text-center text-slate-600">No records yet.</td></tr></tbody></table></div>` },
    },
};
</script>
