<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    company: { name: string; plan_label: string };
    title: string;
    description: string;
    columns: string[];
    rows: Array<Record<string, string>>;
    emptyState: string;
}>();
</script>

<template>
    <Head :title="title" />
    <ManufacturerAdminShell
        :company="company"
        :title="title"
        :subtitle="description"
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: title }]"
    >

            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                            <tr>
                                <th v-for="column in columns" :key="column" class="px-3 py-3 text-left">{{ column }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, rowIndex) in rows" :key="rowIndex">
                                <td v-for="column in columns" :key="column" class="px-3 py-4">{{ row[column] || 'Pending' }}</td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td :colspan="columns.length" class="px-3 py-10 text-center text-slate-600">{{ emptyState }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">
                    Management actions are prepared as UI/data-model placeholders. Persistence will be connected in a later milestone.
                </div>
            </section>
    </ManufacturerAdminShell>
</template>
