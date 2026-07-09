<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ company: { name: string }; models: { data: Array<any>; links: Array<{ url: string | null; label: string; active: boolean }> } }>();
</script>

<template>
    <Head title="Manufacturer Models" />
    <ManufacturerAdminShell
        :company="company"
        title="Models"
        subtitle="Models are contained in datasheets. Use this page to locate a model, compare it, or jump back to its datasheet."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Models' }]"
    >
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <DataTable :columns="['Model', 'Datasheet', 'Family / Series', 'Power', 'Technology', 'Status', 'Structured Engineering Data status', 'Actions']" :rows="models.data" />
                <Pager :links="models.links" />
            </section>
    </ManufacturerAdminShell>
</template>

<script lang="ts">
export default {
    components: {
        DataTable: {
            props: ['columns', 'rows'],
            template: `<div class="overflow-x-auto rounded-lg border border-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500"><tr><th v-for="column in columns" :key="column" class="px-3 py-3 text-left">{{ column }}</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="row in rows" :key="row.id"><td class="px-3 py-4 font-black">{{ row.model }}</td><td class="px-3 py-4"><a v-if="row.datasheet_href" :href="row.datasheet_href" class="font-bold text-emerald-700">{{ row.datasheet }}</a><span v-else>{{ row.datasheet }}</span></td><td class="px-3 py-4">{{ row.family_series }}</td><td class="px-3 py-4">{{ row.power }}</td><td class="px-3 py-4">{{ row.technology }}</td><td class="px-3 py-4 capitalize">{{ row.status }}</td><td class="px-3 py-4">{{ row.structured_data_status }}</td><td class="px-3 py-4"><div class="flex gap-2"><a :href="row.open_href" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold">Open</a><a :href="row.compare_href" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold">Compare</a><a v-if="row.datasheet_href" :href="row.datasheet_href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">View Datasheet</a></div></td></tr><tr v-if="rows.length === 0"><td :colspan="columns.length" class="px-3 py-8 text-center text-slate-600">No models found.</td></tr></tbody></table></div>`,
        },
        Pager: { props: ['links'], template: `<div v-if="links?.length" class="mt-5 flex flex-wrap gap-2"><a v-for="link in links" :key="link.label" :href="link.url || '#'" class="rounded-md border px-3 py-2 text-sm font-bold" :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 text-slate-700 hover:bg-slate-50' : 'pointer-events-none border-slate-100 text-slate-300'" v-html="link.label" /></div>` },
    },
};
</script>
