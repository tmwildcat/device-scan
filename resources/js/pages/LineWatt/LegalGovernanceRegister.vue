<script setup lang="ts">
import LegalGovernanceShell from '@/components/linewatt/admin/LegalGovernanceShell.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps<{ workspace: { role_label?: string | null; is_super_admin: boolean }; title: string; columns: string[]; rows: { data?: Array<Array<string | null>>; links?: Array<{ url?: string | null; label: string; active: boolean }> } | Array<Array<string | null>> }>();
</script>
<template>
    <Head :title="title" />
    <LegalGovernanceShell :title="title" subtitle="Read-only Legal Governance register." :role-label="workspace.role_label" :is-super-admin="workspace.is_super_admin">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700"><thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wider text-slate-500 dark:bg-slate-800"><tr><th v-for="column in columns" :key="column" class="px-5 py-3">{{ column }}</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800"><tr v-for="(row, index) in (Array.isArray(rows) ? rows : rows.data || [])" :key="index"><td v-for="(value, valueIndex) in row" :key="valueIndex" class="px-5 py-4 text-slate-700 dark:text-slate-300">{{ value || '—' }}</td></tr><tr v-if="!(Array.isArray(rows) ? rows : rows.data || []).length"><td :colspan="columns.length || 1" class="px-5 py-12 text-center text-slate-500">No records available.</td></tr></tbody></table></div></section>
        <nav v-if="!Array.isArray(rows) && (rows.links?.length || 0) > 3" class="mt-5 flex flex-wrap gap-2"><Link v-for="link in rows.links" :key="link.label" :href="link.url || '#'" class="rounded-md border px-3 py-2 text-sm font-bold" :class="link.active ? 'bg-slate-950 text-white' : 'bg-white text-slate-700'" v-html="link.label" /></nav>
    </LegalGovernanceShell>
</template>
