<script setup lang="ts">
import LegalGovernanceShell from '@/components/linewatt/admin/LegalGovernanceShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    workspace: { role_label?: string | null; is_super_admin: boolean };
    version: { id: string; document_title: string; version_label: string; status: string; editable: boolean; content_checksum: string; change_summary?: string | null; markdown_source: string; sanitized_html: string; plain_text: string; placeholders_count: number; reviews_count: number; artifacts_count: number; update_href: string };
}>();
const form = useForm({ change_summary: props.version.change_summary || '', markdown_source: props.version.markdown_source });
const submit = () => form.put(props.version.update_href, { preserveScroll: true });
</script>

<template>
    <Head :title="`${version.document_title} ${version.version_label}`" />
    <LegalGovernanceShell :title="version.document_title" :subtitle="`Version ${version.version_label} · ${version.status.replaceAll('_', ' ')}`" :role-label="workspace.role_label" :is-super-admin="workspace.is_super_admin">
        <div class="mb-5 flex flex-wrap gap-2 text-xs font-black"><span class="rounded-full bg-violet-100 px-3 py-1 capitalize text-violet-800">{{ version.status }}</span><span class="rounded-full bg-slate-200 px-3 py-1 text-slate-700">{{ version.placeholders_count }} open placeholders</span><span class="rounded-full bg-slate-200 px-3 py-1 text-slate-700">{{ version.reviews_count }} reviews</span><span class="rounded-full bg-slate-200 px-3 py-1 text-slate-700">{{ version.artifacts_count }} artifacts</span></div>
        <form v-if="version.editable" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900" @submit.prevent="submit">
            <label class="block text-sm font-black text-slate-800 dark:text-slate-200">Change summary<input v-model="form.change_summary" required class="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 font-normal dark:border-slate-600 dark:bg-slate-950" /></label>
            <p v-if="form.errors.change_summary" class="mt-1 text-sm text-rose-700">{{ form.errors.change_summary }}</p>
            <label class="mt-5 block text-sm font-black text-slate-800 dark:text-slate-200">Markdown source<textarea v-model="form.markdown_source" required rows="28" class="mt-2 w-full rounded-md border border-slate-300 bg-white p-4 font-mono text-sm dark:border-slate-600 dark:bg-slate-950" /></label>
            <p v-if="form.errors.markdown_source" class="mt-1 text-sm text-rose-700">{{ form.errors.markdown_source }}</p>
            <div class="mt-5 flex items-center gap-3"><button type="submit" :disabled="form.processing" class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white disabled:opacity-50">{{ form.processing ? 'Saving…' : 'Save draft' }}</button><Link href="/admin/legal-governance/documents" class="text-sm font-black text-slate-600">Back to documents</Link></div>
        </form>
        <div v-else class="rounded-lg border border-sky-200 bg-sky-50 p-5 text-sm font-semibold text-sky-900">This version is immutable and is shown read-only. Create a new Draft for corrections.</div>
        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900"><h2 class="text-xl font-black">Rendered preview</h2><article class="prose mt-5 max-w-none dark:prose-invert" v-html="version.sanitized_html" /></section>
    </LegalGovernanceShell>
</template>
