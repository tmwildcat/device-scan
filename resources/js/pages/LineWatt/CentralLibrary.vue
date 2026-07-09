<script setup lang="ts">
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import MetricCard from '@/components/linewatt/MetricCard.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, BadgeCheck, ClipboardCheck, DatabaseZap, Factory, FileWarning, Search, ShieldAlert, UploadCloud, UserPlus } from 'lucide-vue-next';

type RecordSummary = {
    id?: number;
    uuid?: string;
    source_label?: string;
    manufacturer?: string | null;
    display_name?: string | null;
    model_name?: string | null;
    model_series?: string | null;
    device_type?: string | null;
    status?: string | null;
    validation_status?: string | null;
    review_href?: string | null;
};

defineProps<{
    workspace: {
        name: string;
        role?: string | null;
        role_label?: string | null;
    };
    summary: {
        published_records: number;
        pending_review: number;
        validation_warnings: number;
        partner_submissions: number;
        oem_submissions: number;
        changes_requested: number;
        failed_compiles: number;
        recently_published: number;
        manufacturers: number;
        pending_review_records: RecordSummary[];
        oem_submission_records: RecordSummary[];
        changes_requested_records: RecordSummary[];
        failed_compile_datasheets: Array<any>;
        recent_records: RecordSummary[];
        activity: {
            subscriber: { total: number; added_this_week: number; dropped_this_week: number };
            oem_partner: { total: number; added_this_week: number; dropped_this_week: number; submissions: number };
            guest: { total: number; searches_this_week: number; conversion_intent: number };
            pdf_downloads: number;
            exports: number;
            high_intent_searches: number;
            recently_onboarded_oems: number;
            subscribers_added: number;
        };
    };
}>();

function recordTitle(record: RecordSummary): string {
    return record.display_name || record.model_name || record.model_series || 'Engineering Record';
}
</script>

<template>
    <Head title="Library Admin" />

    <LibraryAdminShell
        title="Library Operations Console"
        subtitle="Operate the official LineWatt Library: approvals, OEM submissions, compiler quality, governance, and publication health."
        :role-label="workspace.role_label"
        :primary-action="{ label: 'Upload Datasheet', href: '/admin/library/uploads/new' }"
        :secondary-actions="[{ label: 'Search', href: '/search' }]"
    >
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <Link href="/admin/library/engineering-data?view=pending_approval" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="ClipboardCheck" label="Pending Approval" :value="summary.pending_review" tone="amber" />
            </Link>
            <Link href="/admin/library/approval-queue?view=pending_approval&source=oem" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="Factory" label="OEM Submissions" :value="summary.oem_submissions" tone="violet" />
            </Link>
            <Link href="/admin/library/approval-queue?view=changes_requested" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="AlertTriangle" label="Changes Requested" :value="summary.changes_requested" tone="rose" />
            </Link>
            <Link href="/admin/library/datasheets?view=failed_compiles" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="FileWarning" label="Failed Compiles" :value="summary.failed_compiles" tone="rose" />
            </Link>
            <Link href="/admin/library/engineering-data?view=validation_warnings" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="ShieldAlert" label="Validation Warnings" :value="summary.validation_warnings" tone="sky" />
            </Link>
            <Link href="/admin/library/engineering-data?view=published" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="BadgeCheck" label="Recently Published" :value="summary.recently_published" tone="emerald" />
            </Link>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Library Intelligence</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950">Visitors and Activity</h2>
                            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">
                                Track what subscribers, OEM partners, and guests are doing so librarians can steer manufacturer coverage, download support, and engineering priorities.
                            </p>
                        </div>
                        <Link href="/admin/library/operations/notification-delivery" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50">
                            Activity details
                        </Link>
                    </div>

                    <div class="mt-5 grid gap-3 lg:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <h3 class="font-black text-slate-950">Subscriber Activity</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Paid user growth, retention, exports, and usage signals.</p>
                            <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Total</dt>
                                    <dd class="mt-1 text-lg font-black">{{ summary.activity.subscriber.total }}</dd>
                                </div>
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Added</dt>
                                    <dd class="mt-1 text-lg font-black text-emerald-700">{{ summary.activity.subscriber.added_this_week }}</dd>
                                </div>
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Dropped</dt>
                                    <dd class="mt-1 text-lg font-black text-rose-700">{{ summary.activity.subscriber.dropped_this_week }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <h3 class="font-black text-slate-950">OEM Partner Activity</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Partner onboarding, submissions, and content pressure.</p>
                            <dl class="mt-4 grid grid-cols-4 gap-2 text-center">
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Total</dt>
                                    <dd class="mt-1 text-lg font-black">{{ summary.activity.oem_partner.total }}</dd>
                                </div>
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Added</dt>
                                    <dd class="mt-1 text-lg font-black text-emerald-700">{{ summary.activity.oem_partner.added_this_week }}</dd>
                                </div>
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Dropped</dt>
                                    <dd class="mt-1 text-lg font-black text-rose-700">{{ summary.activity.oem_partner.dropped_this_week }}</dd>
                                </div>
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Subs.</dt>
                                    <dd class="mt-1 text-lg font-black text-violet-700">{{ summary.activity.oem_partner.submissions }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <h3 class="font-black text-slate-950">Guest Activity</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Anonymous discovery, demand gaps, and conversion intent.</p>
                            <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Total</dt>
                                    <dd class="mt-1 text-lg font-black">{{ summary.activity.guest.total }}</dd>
                                </div>
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Searches</dt>
                                    <dd class="mt-1 text-lg font-black text-sky-700">{{ summary.activity.guest.searches_this_week }}</dd>
                                </div>
                                <div class="rounded-md bg-white p-2">
                                    <dt class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">Intent</dt>
                                    <dd class="mt-1 text-lg font-black text-amber-700">{{ summary.activity.guest.conversion_intent }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">Pending Approval</h2>
                            <p class="mt-1 text-sm text-slate-600">Publisher, OEM, and central records waiting for librarian action.</p>
                        </div>
                        <Link href="/admin/library/approval-queue?view=pending_approval" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50">Show all</Link>
                    </div>
                    <div v-if="summary.pending_review_records.length" class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Source</th>
                                    <th class="px-4 py-3">Manufacturer</th>
                                    <th class="px-4 py-3">Engineering Data</th>
                                    <th class="px-4 py-3">Validation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="record in summary.pending_review_records" :key="record.uuid || record.id">
                                    <td class="px-4 py-3 text-xs font-black uppercase tracking-[0.12em] text-slate-500">{{ record.source_label || 'Central' }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ record.manufacturer || 'Pending' }}</td>
                                    <td class="px-4 py-3">
                                        <Link :href="record.review_href || '#'" class="font-black text-slate-950 hover:text-emerald-700">{{ recordTitle(record) }}</Link>
                                        <div class="mt-1 text-xs text-slate-500">{{ record.device_type || 'unknown' }}</div>
                                    </td>
                                    <td class="px-4 py-3"><ValidationStatusBadge :status="record.validation_status" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="mt-4 rounded-md border border-dashed border-slate-300 p-5 text-sm text-slate-600">No records are pending approval.</p>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black text-slate-950">Primary Actions</h3>
                    <div class="mt-4 grid gap-2">
                        <Link href="/admin/library/uploads/new" class="flex items-center gap-3 rounded-md border border-slate-200 p-3 text-sm font-black hover:bg-slate-50">
                            <UploadCloud class="size-4 text-emerald-700" /> Upload Datasheet
                        </Link>
                        <Link href="/search" class="flex items-center gap-3 rounded-md border border-slate-200 p-3 text-sm font-black hover:bg-slate-50">
                            <Search class="size-4 text-sky-700" /> Search
                        </Link>
                        <Link href="/admin/library/approval-queue?view=pending_approval" class="flex items-center gap-3 rounded-md border border-slate-200 p-3 text-sm font-black hover:bg-slate-50">
                            <ClipboardCheck class="size-4 text-amber-700" /> Review & Approval
                        </Link>
                        <Link href="/admin/library/oem-subscribers/new" class="flex items-center gap-3 rounded-md border border-slate-200 p-3 text-sm font-black hover:bg-slate-50">
                            <UserPlus class="size-4 text-violet-700" /> New OEM Subscriber
                        </Link>
                        <Link href="/admin/library/power-search" class="flex items-center gap-3 rounded-md border border-slate-200 p-3 text-sm font-black hover:bg-slate-50">
                            <DatabaseZap class="size-4 text-sky-700" /> Power Search Taxonomy
                        </Link>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-black text-slate-950">OEM Submissions</h3>
                        <Link href="/admin/library/approval-queue?view=pending_approval&source=oem" class="text-xs font-black text-emerald-700">Show all</Link>
                    </div>
                    <div v-if="summary.oem_submission_records.length" class="mt-4 space-y-3">
                        <Link v-for="record in summary.oem_submission_records" :key="record.uuid || record.id" :href="record.review_href || '#'" class="block rounded-lg border border-slate-200 bg-slate-50 p-3 hover:bg-white">
                            <div class="text-sm font-black text-slate-950">{{ recordTitle(record) }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ record.manufacturer || 'Unknown OEM' }}</div>
                        </Link>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-500">No OEM submissions waiting.</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-black text-slate-950">Changes Requested</h3>
                        <Link href="/admin/library/approval-queue?view=changes_requested" class="text-xs font-black text-emerald-700">Show all</Link>
                    </div>
                    <div v-if="summary.changes_requested_records.length" class="mt-4 space-y-3">
                        <Link v-for="record in summary.changes_requested_records" :key="record.uuid || record.id" :href="record.review_href || '#'" class="block rounded-lg border border-amber-200 bg-amber-50 p-3 hover:bg-white">
                            <div class="text-sm font-black text-slate-950">{{ recordTitle(record) }}</div>
                            <div class="mt-1 text-xs text-amber-800">{{ record.manufacturer || 'Unknown manufacturer' }}</div>
                        </Link>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-500">No requested changes are open.</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-black text-slate-950">Recently Published</h3>
                        <Link href="/admin/library/approval-queue?view=recently_published" class="text-xs font-black text-emerald-700">Show all</Link>
                    </div>
                    <div v-if="summary.recent_records.length" class="mt-4 space-y-3">
                        <Link v-for="record in summary.recent_records" :key="record.uuid || record.id" :href="record.review_href || '#'" class="block rounded-lg border border-slate-200 bg-slate-50 p-3 hover:bg-white">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-black text-slate-950">{{ recordTitle(record) }}</div>
                                    <div class="mt-1 truncate text-xs text-slate-500">{{ record.manufacturer || 'Unknown manufacturer' }}</div>
                                </div>
                                <LifecycleStatusBadge :status="record.status" />
                            </div>
                        </Link>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-500">No recently published records.</p>
                </div>
            </aside>
        </section>
    </LibraryAdminShell>
</template>
