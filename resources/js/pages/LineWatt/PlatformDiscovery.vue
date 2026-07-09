<script setup lang="ts">
import PlatformAdminShell from '@/components/linewatt/admin/PlatformAdminShell.vue';
import MetricCard from '@/components/linewatt/MetricCard.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Binary, Bot, FileSearch, Globe2, Link2, Map, Route, SearchCheck } from 'lucide-vue-next';
import { computed } from 'vue';

type Section = { key: string; label: string; href: string };
type Row = Record<string, any>;

const props = defineProps<{
    workspace: { role_label?: string | null; environment?: string | null; health?: string | null };
    section: string;
    sectionTitle: string;
    sections: Section[];
    summary: Record<string, string | number>;
    rows: Row[];
    sitemaps: Row[];
    robots?: string | null;
    structuredData: Row[];
}>();

const redirectForm = useForm({
    source_path: '',
    target_path: '',
    status_code: 301,
    reason: '',
});

const metadataForm = useForm({
    meta_title: '',
    meta_description: '',
    robots: 'index,follow',
    indexable: true,
    canonical_url: '',
});

const selectedMetadata = computed(() => props.rows.find((row) => row.editable) || null);

function loadMetadata(row: Row): void {
    metadataForm.meta_title = row.editable?.meta_title || '';
    metadataForm.meta_description = row.editable?.meta_description || '';
    metadataForm.robots = row.editable?.robots || 'index,follow';
    metadataForm.indexable = Boolean(row.editable?.indexable);
    metadataForm.canonical_url = row.editable?.canonical_url || '';
}

function saveMetadata(row: Row): void {
    metadataForm.patch(`/admin/platform/discovery/metadata/${row.id}`, {
        preserveScroll: true,
    });
}

function submitRedirect(): void {
    redirectForm.post('/admin/platform/discovery/redirects', {
        preserveScroll: true,
        onSuccess: () => redirectForm.reset(),
    });
}

const cards = computed(() => [
    { label: 'Public Pages', value: props.summary.public_pages ?? 0, icon: Globe2, tone: 'emerald' },
    { label: 'Indexable Pages', value: props.summary.indexable_pages ?? 0, icon: SearchCheck, tone: 'sky' },
    { label: 'Missing Meta Titles', value: props.summary.missing_meta_titles ?? 0, icon: AlertTriangle, tone: 'amber' },
    { label: 'Missing Meta Descriptions', value: props.summary.missing_meta_descriptions ?? 0, icon: AlertTriangle, tone: 'amber' },
    { label: 'Missing Canonicals', value: props.summary.missing_canonicals ?? 0, icon: Link2, tone: 'amber' },
    { label: 'Duplicate Slugs', value: props.summary.duplicate_slugs ?? 0, icon: Route, tone: 'rose' },
    { label: 'Broken Redirects', value: props.summary.broken_redirects ?? 0, icon: Route, tone: 'sky' },
    { label: 'Structured Data Coverage', value: props.summary.structured_data_coverage ?? '0%', icon: Binary, tone: 'emerald' },
    { label: 'Sitemap Status', value: props.summary.sitemap_status ?? 'Placeholder', icon: Map, tone: 'sky' },
    { label: 'Orphan Pages', value: props.summary.orphan_pages ?? 'Placeholder', icon: FileSearch, tone: 'violet' },
]);

const sectionIcon = (key: string) => {
    if (key === 'landing-pages') return FileSearch;
    if (key === 'metadata') return AlertTriangle;
    if (key === 'canonical-urls') return Link2;
    if (key === 'redirects') return Route;
    if (key === 'structured-data') return Binary;
    if (key === 'sitemaps') return Map;
    if (key === 'ai') return Bot;
    return SearchCheck;
};

const rowCoverage = (row: Row): string => {
    if (row.coverage) return String(row.coverage);
    if (row.indexable !== undefined) return row.indexable ? 'Yes' : 'No';
    if (row.url_count !== undefined) return String(row.url_count);

    return '—';
};
</script>

<template>
    <Head :title="`Discovery · ${sectionTitle}`" />

    <PlatformAdminShell
        :title="sectionTitle === 'Dashboard' ? 'Discovery Dashboard' : sectionTitle"
        subtitle="Search, sitemap, metadata and AI discoverability controls for public LineWatt Library discovery."
        :role-label="workspace.role_label"
        :environment="workspace.environment"
        :health="workspace.health"
        :breadcrumbs="[
            { label: 'Platform', href: '/admin/platform' },
            { label: 'Discovery', href: '/admin/platform/discovery' },
            { label: sectionTitle },
        ]"
    >
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <Link
                v-for="item in sections"
                :key="item.key"
                :href="item.href"
                class="rounded-lg border p-4 transition hover:-translate-y-0.5"
                :class="section === item.key ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-200 hover:bg-emerald-50'"
            >
                <component :is="sectionIcon(item.key)" class="size-5" :class="section === item.key ? 'text-white' : 'text-emerald-700'" />
                <div class="mt-3 text-sm font-black">{{ item.label }}</div>
            </Link>
        </section>

        <section v-if="section === 'dashboard'" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <MetricCard v-for="card in cards" :key="card.label" :icon="card.icon" :label="card.label" :value="card.value" :tone="card.tone as any" />
        </section>

        <section v-if="section === 'redirects'" class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Create Redirect</h2>
            <form class="mt-5 grid gap-4 xl:grid-cols-[1fr_1fr_140px_1fr_auto]" @submit.prevent="submitRedirect">
                <input v-model="redirectForm.source_path" class="rounded-md border border-slate-200 px-4 py-3 font-semibold" placeholder="/old-url" />
                <input v-model="redirectForm.target_path" class="rounded-md border border-slate-200 px-4 py-3 font-semibold" placeholder="/new-url" />
                <select v-model="redirectForm.status_code" class="rounded-md border border-slate-200 px-4 py-3 font-semibold">
                    <option :value="301">301</option>
                    <option :value="302">302</option>
                </select>
                <input v-model="redirectForm.reason" class="rounded-md border border-slate-200 px-4 py-3 font-semibold" placeholder="Reason" />
                <button class="rounded-md bg-slate-950 px-5 py-3 font-black text-white">Save</button>
            </form>
        </section>

        <section v-if="section === 'robots'" class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-6">
                <h2 class="text-xl font-black text-slate-950">robots.txt Preview</h2>
                <p class="mt-1 text-sm text-slate-600">Safe default policy. Editing is intentionally disabled for now.</p>
            </div>
            <pre class="overflow-auto p-6 font-mono text-sm leading-7 text-slate-800">{{ robots }}</pre>
        </section>

        <section v-if="section === 'metadata' && selectedMetadata" class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Metadata Quick Edit</h2>
            <p class="mt-1 text-sm text-slate-600">Loads the first metadata item needing attention. Open rows remain list-first for now.</p>
            <form class="mt-5 grid gap-4" @submit.prevent="saveMetadata(selectedMetadata)">
                <input v-model="metadataForm.meta_title" class="rounded-md border border-slate-200 px-4 py-3 font-semibold" placeholder="Meta title" @focus="loadMetadata(selectedMetadata)" />
                <textarea v-model="metadataForm.meta_description" class="min-h-24 rounded-md border border-slate-200 px-4 py-3 font-semibold" placeholder="Meta description" />
                <div class="grid gap-4 md:grid-cols-[1fr_180px_auto]">
                    <input v-model="metadataForm.canonical_url" class="rounded-md border border-slate-200 px-4 py-3 font-semibold" placeholder="Canonical URL" />
                    <input v-model="metadataForm.robots" class="rounded-md border border-slate-200 px-4 py-3 font-semibold" placeholder="robots" />
                    <label class="flex items-center gap-2 rounded-md border border-slate-200 px-4 py-3 font-bold">
                        <input v-model="metadataForm.indexable" type="checkbox" />
                        Indexable
                    </label>
                </div>
                <button class="w-fit rounded-md bg-slate-950 px-5 py-3 font-black text-white">Save Metadata</button>
            </form>
        </section>

        <section v-if="section !== 'robots'" class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-6">
                <h2 class="text-xl font-black text-slate-950">{{ sectionTitle }}</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{
                        section === 'search-console'
                            ? 'Search Console integration is not connected yet.'
                            : section === 'ai'
                              ? 'AI discoverability is reserved for future AI sitemaps, structured engineering feeds and MCP visibility.'
                              : 'Discovery rows from the SEO foundation.'
                    }}
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.14em] text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Item</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Slug / URL</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Indexable / Coverage</th>
                            <th class="px-5 py-3">Updated</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(row, index) in rows" :key="row.id || index">
                            <td class="px-5 py-4">
                                <div class="font-black text-slate-950">{{ row.primary || row.title || row.source_path || 'Discovery item' }}</div>
                                <div v-if="row.secondary" class="mt-1 max-w-lg text-xs text-slate-500">{{ row.secondary }}</div>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ row.type || '—' }}</td>
                            <td class="px-5 py-4 font-mono text-xs text-emerald-700">{{ row.slug || row.url || row.canonical_url || row.target_path || '—' }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ row.status || '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ rowCoverage(row) }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ row.updated_at || row.last_generated || row.created_at || '—' }}</td>
                            <td class="px-5 py-4 text-right">
                                <span class="rounded-md bg-slate-100 px-3 py-2 text-xs font-black text-slate-600">Open</span>
                            </td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">No discovery rows yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </PlatformAdminShell>
</template>
