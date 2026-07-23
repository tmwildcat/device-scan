<script setup lang="ts">
import LegalGovernanceShell from '@/components/linewatt/admin/LegalGovernanceShell.vue';
import { Head, Link } from '@inertiajs/vue3';

type Version = { id: string; label: string; status: string; updated_at?: string | null; href: string };
type Document = { id: string; title: string; type: string; visibility: string; versions_count: number; versions: Version[] };

defineProps<{
    workspace: { role_label?: string | null; is_super_admin: boolean };
    documents: { data: Document[]; links: Array<{ url?: string | null; label: string; active: boolean }>; total: number };
    status_filter?: string | null;
}>();

const statusTone = (status: string) => status === 'published'
    ? 'bg-emerald-100 text-emerald-800'
    : status === 'draft' ? 'bg-violet-100 text-violet-800' : 'bg-amber-100 text-amber-800';
</script>

<template>
    <Head title="Legal Documents" />
    <LegalGovernanceShell title="Legal Documents" :subtitle="`${documents.total} governed documents${status_filter ? ` filtered to ${status_filter.replaceAll('_', ' ')}` : ''}.`" :role-label="workspace.role_label" :is-super-admin="workspace.is_super_admin">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                        <tr><th class="px-5 py-3">Document</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Visibility</th><th class="px-5 py-3">Versions</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="document in documents.data" :key="document.id" class="align-top">
                            <td class="px-5 py-4"><div class="font-black text-slate-950 dark:text-white">{{ document.title }}</div></td>
                            <td class="px-5 py-4 capitalize text-slate-600 dark:text-slate-300">{{ document.type }}</td>
                            <td class="px-5 py-4 capitalize text-slate-600 dark:text-slate-300">{{ document.visibility }}</td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <Link v-for="version in document.versions" :key="version.id" :href="version.href" class="inline-flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 font-black hover:border-emerald-300 hover:bg-emerald-50 dark:border-slate-700 dark:hover:bg-slate-800">
                                        {{ version.label }} <span class="rounded-full px-2 py-0.5 text-xs capitalize" :class="statusTone(version.status)">{{ version.status.replaceAll('_', ' ') }}</span>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!documents.data.length"><td colspan="4" class="px-5 py-12 text-center text-slate-500">No legal documents found.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
        <nav v-if="documents.links.length > 3" class="mt-5 flex flex-wrap gap-2" aria-label="Document pagination">
            <Link v-for="link in documents.links" :key="link.label" :href="link.url || '#'" class="rounded-md border px-3 py-2 text-sm font-bold" :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700'" :aria-disabled="!link.url" v-html="link.label" />
        </nav>
    </LegalGovernanceShell>
</template>
