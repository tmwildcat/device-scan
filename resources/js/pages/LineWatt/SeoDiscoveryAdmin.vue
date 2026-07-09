<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import MetricCard from '@/components/linewatt/MetricCard.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Binary, Bot, FileSearch, Link2, Map, Route, SearchCheck } from 'lucide-vue-next';

const props = defineProps<{
    page: string;
    summary: {
        indexed_pages: number;
        missing_titles: number;
        missing_descriptions: number;
        missing_canonicals: number;
        duplicate_titles: number;
        broken_links: number;
        orphan_pages: number;
        structured_data_coverage: number;
    };
    rows: Array<Record<string, any>>;
}>();

const nav = [
    { label: 'SEO Dashboard', href: '/admin/business/discovery', page: 'dashboard', icon: SearchCheck },
    { label: 'Canonical URLs', href: '/admin/business/discovery/canonical-urls', page: 'canonical-urls', icon: Link2 },
    { label: 'Landing Pages', href: '/admin/business/discovery/landing-pages', page: 'landing-pages', icon: FileSearch },
    { label: 'Redirect Manager', href: '/admin/business/discovery/redirects', page: 'redirects', icon: Route },
    { label: 'Sitemap Manager', href: '/admin/business/discovery/sitemaps', page: 'sitemaps', icon: Map },
    { label: 'Structured Data', href: '/admin/business/discovery/structured-data', page: 'structured-data', icon: Binary },
    { label: 'AI Discoverability', href: '/admin/business/discovery/ai-discoverability', page: 'ai-discoverability', icon: Bot },
];

const redirectForm = useForm({
    source_path: '',
    target_path: '',
    status_code: 301,
    reason: '',
});

const submitRedirect = () => {
    redirectForm.post('/admin/business/discovery/redirects', {
        preserveScroll: true,
        onSuccess: () => redirectForm.reset(),
    });
};

const pageTitle = nav.find((item) => item.page === props.page)?.label || 'SEO Dashboard';
</script>

<template>
    <Head :title="pageTitle" />

    <LibraryAdminShell
        :title="pageTitle"
        subtitle="Discovery infrastructure for canonical URLs, metadata, structured data, redirects and sitemaps."
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/business' },
            { label: 'Discovery', href: '/admin/business/discovery' },
            { label: pageTitle },
        ]"
        :primary-action="{ label: 'Sitemap', href: '/sitemap.xml' }"
        :secondary-actions="[{ label: 'Search', href: '/search' }]"
    >
        <section class="grid gap-3 lg:grid-cols-7">
            <Link
                v-for="item in nav"
                :key="item.page"
                :href="item.href"
                class="rounded-lg border p-4 transition hover:-translate-y-0.5"
                :class="page === item.page ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-200 hover:bg-emerald-50'"
            >
                <component :is="item.icon" class="size-5" :class="page === item.page ? 'text-white' : 'text-emerald-700'" />
                <div class="mt-3 text-sm font-black">{{ item.label }}</div>
            </Link>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <MetricCard :icon="SearchCheck" label="Indexed Pages" :value="summary.indexed_pages" tone="emerald" />
            <MetricCard :icon="AlertTriangle" label="Missing Titles" :value="summary.missing_titles" tone="amber" />
            <MetricCard :icon="AlertTriangle" label="Missing Descriptions" :value="summary.missing_descriptions" tone="amber" />
            <MetricCard :icon="Link2" label="Missing Canonicals" :value="summary.missing_canonicals" tone="sky" />
            <MetricCard :icon="AlertTriangle" label="Duplicate Titles" :value="summary.duplicate_titles" tone="amber" />
            <MetricCard :icon="Route" label="Broken Links" :value="summary.broken_links" tone="sky" />
            <MetricCard :icon="Map" label="Orphan Pages" :value="summary.orphan_pages" tone="violet" />
            <MetricCard :icon="Binary" label="Structured Data Coverage" :value="`${summary.structured_data_coverage}%`" tone="emerald" />
        </section>

        <section v-if="page === 'redirects'" class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Create Redirect</h2>
            <form class="mt-5 grid gap-4 lg:grid-cols-[1fr_1fr_140px_1fr_auto]" @submit.prevent="submitRedirect">
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

        <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-6">
                <h2 class="text-xl font-black text-slate-950">{{ page === 'ai-discoverability' ? 'AI Discoverability Placeholder' : pageTitle }}</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ page === 'ai-discoverability' ? 'Reserved for future LLM feeds, AI sitemaps and structured engineering APIs. No AI discoverability has been implemented yet.' : 'Operational rows from the SEO foundation.' }}
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.14em] text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Item</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Path</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(row, index) in rows" :key="index">
                            <td class="px-5 py-4 font-black text-slate-950">{{ row.title || row.source_path || 'SEO item' }}</td>
                            <td class="px-5 py-4">{{ row.kind || row.status_code || '—' }}</td>
                            <td class="px-5 py-4 font-semibold text-emerald-700">{{ row.path || row.target_path || '—' }}</td>
                            <td class="px-5 py-4">{{ row.status || (row.active ? 'active' : 'inactive') || '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ row.description || row.reason || '—' }}</td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">No SEO rows yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </LibraryAdminShell>
</template>
