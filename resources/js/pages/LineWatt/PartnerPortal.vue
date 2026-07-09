<script setup lang="ts">
import EntitlementDiagnostics from '@/components/linewatt/EntitlementDiagnostics.vue';
import MetricCard from '@/components/linewatt/MetricCard.vue';
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';
import { DatabaseZap, Download, FileText, Globe2, NotebookTabs, UploadCloud, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';

defineProps<{
    company: any;
    summary: {
        datasheets: number;
        models: number;
        structured_engineering_data: number;
        supporting_documents: number;
        downloads_last_30_days: number;
        comparisons: number;
        pending_reviews: number;
        recent_updates: number;
        draft_saved_review: number;
        submitted_for_approval: number;
        changes_requested: number;
        published: number;
    };
    recentDatasheets: Array<any>;
    pendingReviews: Array<any>;
    recentStructuredData: Array<any>;
}>();

const { dir, t } = useLineWattI18n();

const quickActions = computed(() => [
    [t('manufacturer.uploadAction'), '/partner/submissions/new', UploadCloud],
    [t('manufacturer.manageDatasheets'), '/admin/manufacturer/datasheets', FileText],
    [t('manufacturer.reviewCompilation'), '/admin/manufacturer/structured-engineering-data?status=review_required', DatabaseZap],
    [t('manufacturer.supportingDocuments'), '/admin/manufacturer/supporting-documents', Download],
    [t('manufacturer.websiteIntegration'), '/admin/manufacturer/website-integration', Globe2],
    [t('manufacturer.users'), '/admin/manufacturer/users', Users],
]);
</script>

<template>
    <Head :title="t('manufacturer.workspaceTitle')" />

    <ManufacturerAdminShell
        :company="company"
        :title="t('manufacturer.dashboard')"
        :subtitle="t('manufacturer.dashboardSubtitle')"
        :primary-action="{ label: t('manufacturer.uploadAction'), href: '/partner/submissions/new' }"
    >
        <section :dir="dir">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-black">{{ t('manufacturer.recentDatasheets') }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ t('manufacturer.recentDatasheetsHelp') }}</p>
                    </div>
                    <Link href="/admin/manufacturer/datasheets" class="text-sm font-black text-emerald-700">{{ t('manufacturer.showAll') }}</Link>
                </div>
                <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                            <tr>
                                <th class="px-3 py-3 text-left">{{ t('manufacturer.datasheets') }}</th>
                                <th class="px-3 py-3 text-left">{{ t('manufacturer.familySeries') }}</th>
                                <th class="px-3 py-3 text-left">{{ t('manufacturer.revision') }}</th>
                                <th class="px-3 py-3 text-left">{{ t('manufacturer.language') }}</th>
                                <th class="px-3 py-3 text-left">{{ t('manufacturer.status') }}</th>
                                <th class="px-3 py-3 text-left">{{ t('manufacturer.uploaded') }}</th>
                                <th class="px-3 py-3 text-right">{{ t('manufacturer.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="datasheet in recentDatasheets" :key="datasheet.id">
                                <td class="px-3 py-4 font-bold">{{ datasheet.title }}</td>
                                <td class="px-3 py-4">{{ datasheet.family_series }}</td>
                                <td class="px-3 py-4">{{ datasheet.revision }}</td>
                                <td class="px-3 py-4">{{ datasheet.language }}</td>
                                <td class="px-3 py-4 capitalize">{{ datasheet.status }}</td>
                                <td class="px-3 py-4">{{ datasheet.uploaded || 'Pending' }}</td>
                                <td class="px-3 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a :href="datasheet.preview_href" target="_blank" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold hover:bg-slate-50">{{ t('manufacturer.preview') }}</a>
                                        <Link :href="datasheet.show_href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">{{ t('manufacturer.review') }}</Link>
                                        <Link :href="datasheet.replace_href" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold hover:bg-slate-50">{{ t('manufacturer.replace') }}</Link>
                                        <Link :href="datasheet.history_href" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold hover:bg-slate-50">{{ t('manufacturer.history') }}</Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="recentDatasheets.length === 0">
                                <td colspan="7" class="px-3 py-8 text-center text-slate-600">{{ t('manufacturer.noDatasheets') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-2">
                <DataList :title="t('manufacturer.pendingReviews')" :show-all-label="t('manufacturer.showAll')" :review-label="t('manufacturer.review')" :datasheet-label="t('manufacturer.datasheets')" :empty-label="t('manufacturer.noItems')" :items="pendingReviews" show-all-href="/admin/manufacturer/structured-engineering-data?status=review_required" />
                <DataList :title="t('manufacturer.recentActivity')" :show-all-label="t('manufacturer.showAll')" :review-label="t('manufacturer.review')" :datasheet-label="t('manufacturer.datasheets')" :empty-label="t('manufacturer.noItems')" :items="recentStructuredData" show-all-href="/admin/manufacturer/structured-engineering-data" />
            </div>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <Link href="/admin/manufacturer/structured-engineering-data?status=publisher_review" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="DatabaseZap" :label="t('manufacturer.draftSavedReview')" :value="summary.draft_saved_review" tone="sky" />
            </Link>
            <Link href="/admin/manufacturer/structured-engineering-data?status=submitted_for_approval" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="DatabaseZap" :label="t('manufacturer.submittedForApproval')" :value="summary.submitted_for_approval" tone="violet" />
            </Link>
            <Link href="/admin/manufacturer/structured-engineering-data?status=changes_requested" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="DatabaseZap" :label="t('manufacturer.changesRequested')" :value="summary.changes_requested" tone="amber" />
            </Link>
            <Link href="/admin/manufacturer/structured-engineering-data?status=published" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="DatabaseZap" :label="t('manufacturer.published')" :value="summary.published" tone="emerald" />
            </Link>
            <Link href="/admin/manufacturer/datasheets" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="FileText" :label="t('manufacturer.datasheets')" :value="summary.datasheets" tone="sky" />
            </Link>
            <Link href="/admin/manufacturer/models" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="NotebookTabs" :label="t('manufacturer.models')" :value="summary.models" tone="emerald" />
            </Link>
            <Link href="/admin/manufacturer/structured-engineering-data" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="DatabaseZap" :label="t('manufacturer.structuredData')" :value="summary.structured_engineering_data" tone="violet" />
            </Link>
            <Link href="/admin/manufacturer/supporting-documents" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="Download" :label="t('manufacturer.supportingDocs')" :value="summary.supporting_documents" tone="amber" />
            </Link>
            <Link href="/admin/manufacturer/structured-engineering-data?status=review_required" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="DatabaseZap" :label="t('manufacturer.pendingReviews')" :value="summary.pending_reviews" tone="amber" />
            </Link>
            <Link href="/admin/manufacturer/structured-engineering-data" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="FileText" :label="t('manufacturer.recentUpdates')" :value="summary.recent_updates" tone="emerald" />
            </Link>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black">{{ t('manufacturer.quickActions') }}</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                <Link v-for="[label, href, icon] in quickActions" :key="String(label)" :href="String(href)" class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:border-emerald-200 hover:bg-emerald-50/40">
                    <span class="flex items-center gap-3">
                        <component :is="icon" class="size-5 text-emerald-700" />
                        <span class="font-black">{{ label }}</span>
                    </span>
                </Link>
            </div>
        </section>

        <section class="mt-6">
            <EntitlementDiagnostics />
        </section>
    </ManufacturerAdminShell>
</template>

<script lang="ts">
export default {
    components: {
        DataList: {
            props: ['title', 'items', 'showAllHref', 'showAllLabel', 'reviewLabel', 'datasheetLabel', 'emptyLabel'],
            template: `
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-black">{{ title }}</h2>
                        <a :href="showAllHref" class="text-sm font-black text-emerald-700">{{ showAllLabel }}</a>
                    </div>
                    <div class="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-200">
                        <div v-for="item in items" :key="item.uuid || item.id" class="p-3">
                            <div class="font-black">{{ item.model }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ item.datasheet }} · {{ item.status || 'compiled' }} · {{ item.updated_at || 'Pending' }}</div>
                            <div class="mt-2 flex gap-2">
                                <a :href="item.review_href" class="rounded-md bg-slate-950 px-3 py-1.5 text-xs font-black text-white">{{ reviewLabel }}</a>
                                <a v-if="item.datasheet_href" :href="item.datasheet_href" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-bold">{{ datasheetLabel }}</a>
                            </div>
                        </div>
                        <div v-if="items.length === 0" class="p-6 text-center text-sm text-slate-600">{{ emptyLabel }}</div>
                    </div>
                </div>
            `,
        },
    },
};
</script>
