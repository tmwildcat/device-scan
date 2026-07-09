<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, ClipboardCheck, DatabaseZap, Factory, FileText, Gauge, Layers3 } from 'lucide-vue-next';

const props = defineProps<{
    roleLabel?: string | null;
    datasheetStats: Record<string, number>;
    recordStats: Record<string, number>;
    manufacturerStats: Record<string, number>;
    monthStats: Record<string, number>;
    attention: any[];
    recentWork: any[];
}>();

const datasheetCards = [
    ['Datasheets compiled by me', props.datasheetStats.compiled, FileText],
    ['Datasheets reviewed', props.datasheetStats.reviewed, ClipboardCheck],
    ['Datasheets approved', props.datasheetStats.approved, CheckCircle2],
    ['Rejected / Needs Rework', props.datasheetStats.rejected_rework, AlertTriangle],
    ['Datasheets pending review', props.datasheetStats.pending_review, DatabaseZap],
    ['Datasheet approval rate', `${props.datasheetStats.approval_rate || 0}%`, Gauge],
];

const recordCards = [
    ['Engineering records created by me', props.recordStats.created, Layers3],
    ['Engineering records reviewed', props.recordStats.reviewed, ClipboardCheck],
    ['Engineering records approved', props.recordStats.approved, CheckCircle2],
    ['Rejected / Needs Rework', props.recordStats.rejected_rework, AlertTriangle],
    ['Engineering records pending review', props.recordStats.pending_review, DatabaseZap],
    ['Engineering record approval rate', `${props.recordStats.approval_rate || 0}%`, Gauge],
];

const manufacturerCards = [
    ['New manufacturers discovered by me', props.manufacturerStats.discovered, Factory],
    ['Manufacturers approved', props.manufacturerStats.approved, CheckCircle2],
    ['Manufacturers pending review', props.manufacturerStats.pending_review, ClipboardCheck],
    ['Manufacturers rejected / merged', props.manufacturerStats.rejected_merged, AlertTriangle],
    ['Duplicate manufacturer warnings', props.manufacturerStats.duplicate_warnings, AlertTriangle],
];

const monthCards = [
    ['Datasheets compiled this month', props.monthStats.datasheets_compiled],
    ['Engineering records created this month', props.monthStats.engineering_records_created],
    ['New manufacturers discovered this month', props.monthStats.manufacturers_discovered],
    ['Rejected / Needs Rework this month', props.monthStats.rejected_needs_rework],
];

function deviceLabel(record: any): string {
    return record.device_type ? record.device_type.replaceAll('_', ' ') : '—';
}
</script>

<template>
    <Head title="Publisher Dashboard" />

    <LibraryAdminShell
        title="Publisher Dashboard"
        subtitle="Your compilation, review, quality and manufacturer discovery activity."
        :role-label="roleLabel"
        :breadcrumbs="[{ label: 'Dashboard' }]"
    >
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article v-for="card in monthCards" :key="card[0]" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">This Month</p>
                <p class="mt-2 text-sm font-black text-slate-700">{{ card[0] }}</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ card[1] }}</p>
            </article>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Datasheet Stats</h2>
                        <p class="mt-1 text-sm text-slate-600">Source datasheets compiled and reviewed by you.</p>
                    </div>
                    <Link href="/publisher/uploads?view=pending_review&device_type=module" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50">Open Datasheets</Link>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <article v-for="card in datasheetCards" :key="card[0] as string" class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <component :is="card[2]" class="size-5 text-emerald-700" />
                        <p class="mt-3 text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ card[0] }}</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ card[1] }}</p>
                    </article>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Engineering Record Stats</h2>
                        <p class="mt-1 text-sm text-slate-600">Structured Engineering Data created through your work.</p>
                    </div>
                    <Link href="/publisher/review?view=pending_review&device_type=module" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50">Open Records</Link>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <article v-for="card in recordCards" :key="card[0] as string" class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <component :is="card[2]" class="size-5 text-emerald-700" />
                        <p class="mt-3 text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ card[0] }}</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ card[1] }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Manufacturer Stats</h2>
            <p class="mt-1 text-sm text-slate-600">Manufacturer discovery and normalization signals from your uploads.</p>
            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <article v-for="card in manufacturerCards" :key="card[0] as string" class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                    <component :is="card[2]" class="size-5 text-emerald-700" />
                    <p class="mt-3 text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ card[0] }}</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">{{ card[1] }}</p>
                </article>
            </div>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-black text-slate-950">Needs My Attention</h2>
                    <p class="mt-1 text-sm text-slate-600">Rejected, needs rework, incomplete, duplicate or pending-resubmission records.</p>
                </div>
                <div v-if="attention.length" class="divide-y divide-slate-100">
                    <div v-for="record in attention" :key="record.uuid || record.id" class="grid gap-3 px-5 py-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                        <div>
                            <div class="font-black text-slate-950">{{ record.display_name }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ record.manufacturer || 'Manufacturer pending' }} · {{ deviceLabel(record) }}</div>
                            <div class="mt-2 text-xs font-black uppercase tracking-[0.12em] text-amber-700">{{ record.needs_attention_reason }}</div>
                        </div>
                        <Link :href="record.open_href || record.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Open</Link>
                    </div>
                </div>
                <div v-else class="p-8 text-center">
                    <h3 class="font-black text-slate-950">Nothing needs attention</h3>
                    <p class="mt-2 text-sm text-slate-600">Good. No rejected or incomplete owned records are currently flagged.</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-black text-slate-950">My Recent Work</h2>
                    <p class="mt-1 text-sm text-slate-600">Latest publisher-owned Engineering Records.</p>
                </div>
                <div v-if="recentWork.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Record</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Submitted</th>
                                <th class="px-4 py-3">Reviewed</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="record in recentWork" :key="record.uuid || record.id">
                                <td class="px-4 py-4">
                                    <div class="font-black text-slate-950">{{ record.display_name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ record.manufacturer || 'Manufacturer pending' }} · {{ deviceLabel(record) }}</div>
                                    <div class="mt-2"><ValidationStatusBadge :status="record.validation_status" /></div>
                                </td>
                                <td class="px-4 py-4"><LifecycleStatusBadge :status="record.status" /></td>
                                <td class="px-4 py-4 text-xs font-semibold text-slate-600">{{ record.submitted_at || '—' }}</td>
                                <td class="px-4 py-4 text-xs font-semibold text-slate-600">{{ record.reviewed_at || '—' }}</td>
                                <td class="px-4 py-4 text-right">
                                    <Link :href="record.open_href || record.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Open</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-8 text-center">
                    <h3 class="font-black text-slate-950">No recent work yet</h3>
                    <p class="mt-2 text-sm text-slate-600">Upload and compile datasheets to populate this list.</p>
                </div>
            </div>
        </section>
    </LibraryAdminShell>
</template>
