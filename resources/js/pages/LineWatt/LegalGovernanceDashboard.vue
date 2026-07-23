<script setup lang="ts">
import LegalGovernanceShell from '@/components/linewatt/admin/LegalGovernanceShell.vue';
import MetricCard from '@/components/linewatt/MetricCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, BookOpenCheck, FileText, Scale, ScrollText, ShieldCheck } from 'lucide-vue-next';

defineProps<{
    workspace: { name: string; role_label?: string | null; is_super_admin: boolean };
    dashboard: {
        metrics: { documents: number; drafts: number; in_review: number; published: number; scheduled: number; release_blockers: number; outstanding_obligations: number; acceptances: number; manifests: number };
        status_counts: Record<string, number>;
        record_counts: { versions: number; acceptances: number; manifests: number; open_placeholders: number };
        recent_activity: Array<{ id: string; type: string; summary: string; occurred_at?: string | null }>;
        integrity: { status: string; last_run?: string | null; discrepancies?: number | null };
    };
}>();
</script>

<template>
    <Head title="Legal Governance" />
    <LegalGovernanceShell
        title="Legal Governance Dashboard"
        subtitle="Document lifecycle, review, publication, obligations and acceptance evidence."
        :role-label="workspace.role_label"
        :is-super-admin="workspace.is_super_admin"
    >
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <MetricCard label="Documents" :value="dashboard.metrics.documents" :icon="FileText" tone="sky" />
            <MetricCard label="Drafts" :value="dashboard.metrics.drafts" :icon="ScrollText" tone="violet" />
            <MetricCard label="In Review" :value="dashboard.metrics.in_review" :icon="Scale" tone="amber" />
            <MetricCard label="Published" :value="dashboard.metrics.published" :icon="BookOpenCheck" tone="emerald" />
            <MetricCard label="Release Blockers" :value="dashboard.metrics.release_blockers" :icon="AlertTriangle" tone="amber" />
            <MetricCard label="Outstanding Obligations" :value="dashboard.metrics.outstanding_obligations" :icon="ShieldCheck" tone="sky" />
            <MetricCard label="Scheduled Publications" :value="dashboard.metrics.scheduled" :icon="BookOpenCheck" tone="sky" />
        </section>

        <section class="mt-6 grid gap-4 lg:grid-cols-3">
            <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Governance Records</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt>Versions</dt><dd class="font-black">{{ dashboard.record_counts.versions }}</dd></div>
                    <div class="flex justify-between"><dt>Acceptance evidence</dt><dd class="font-black">{{ dashboard.record_counts.acceptances }}</dd></div>
                    <div class="flex justify-between"><dt>Manifests</dt><dd class="font-black">{{ dashboard.record_counts.manifests }}</dd></div>
                    <div class="flex justify-between"><dt>Open placeholders</dt><dd class="font-black">{{ dashboard.record_counts.open_placeholders }}</dd></div>
                </dl>
                <Link href="/admin/legal-governance/documents" class="mt-5 inline-flex text-sm font-black text-emerald-700">Open documents</Link>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">Lifecycle Status</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div v-for="(count, status) in dashboard.status_counts" :key="status" class="flex justify-between">
                        <dt class="capitalize">{{ status.replaceAll('_', ' ') }}</dt><dd class="font-black">{{ count }}</dd>
                    </div>
                    <div v-if="!Object.keys(dashboard.status_counts).length" class="text-slate-500">No versions recorded.</div>
                </dl>
            </article>
            <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Implementation Readiness</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt>Review and publication system</dt><dd class="font-black text-emerald-700">Operational</dd></div>
                    <div class="flex justify-between gap-4"><dt>Workflow builder and evidence export</dt><dd class="font-black text-emerald-700">Operational</dd></div>
                    <div class="flex justify-between gap-4"><dt>Integrity verification</dt><dd class="font-black capitalize" :class="dashboard.integrity.status === 'passed' ? 'text-emerald-700' : 'text-amber-700'">{{ dashboard.integrity.status.replaceAll('_', ' ') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt>Subscriber and Manufacturer enforcement</dt><dd class="font-black text-amber-700">Integration pending</dd></div>
                    <div class="flex justify-between gap-4"><dt>Enterprise and API/MCP enforcement</dt><dd class="font-black text-amber-700">Integration pending</dd></div>
                    <div class="flex justify-between gap-4"><dt>Final legal publication</dt><dd class="font-black text-amber-700">Legal approval required</dd></div>
                </dl>
            </article>
        </section>

        <nav class="mt-6 flex flex-wrap gap-3" aria-label="Legal dashboard actions">
            <Link href="/admin/legal-governance/documents?status=draft" class="rounded-md border bg-white px-4 py-2 text-sm font-black">Draft documents</Link>
            <Link href="/admin/legal-governance/reviews" class="rounded-md border bg-white px-4 py-2 text-sm font-black">Review queue</Link>
            <Link href="/admin/legal-governance/publications" class="rounded-md border bg-white px-4 py-2 text-sm font-black">Publication schedule</Link>
            <Link href="/admin/legal-governance/placeholders" class="rounded-md border bg-white px-4 py-2 text-sm font-black">Release blockers</Link>
            <Link href="/admin/legal-governance/obligations" class="rounded-md border bg-white px-4 py-2 text-sm font-black">Outstanding obligations</Link>
        </nav>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5"><h2 class="text-xl font-black text-slate-950">Recent Legal Activity</h2></div>
            <div class="divide-y divide-slate-100">
                <article v-for="event in dashboard.recent_activity" :key="event.id" class="px-5 py-4">
                    <div class="text-xs font-black uppercase tracking-wider text-slate-400">{{ event.type }}</div>
                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ event.summary }}</p>
                    <time v-if="event.occurred_at" class="mt-1 block text-xs text-slate-500">{{ event.occurred_at }}</time>
                </article>
                <div v-if="!dashboard.recent_activity.length" class="px-5 py-10 text-center text-sm text-slate-500">No legal activity recorded.</div>
            </div>
        </section>
    </LegalGovernanceShell>
</template>
