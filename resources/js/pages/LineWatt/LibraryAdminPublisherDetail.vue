<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, ClipboardCheck, DatabaseZap, FileText, Layers3 } from 'lucide-vue-next';

const props = defineProps<{
    roleLabel?: string | null;
    publisher: any;
    stats: Record<string, number>;
    unreviewed: Array<any>;
    attention: Array<any>;
    recentDatasheets: Array<any>;
}>();

const statCards = [
    ['Datasheets Compiled', props.stats.datasheets_compiled, FileText],
    ['Datasheets Pending Review', props.stats.datasheets_pending_review, ClipboardCheck],
    ['Datasheets Approved', props.stats.datasheets_approved, CheckCircle2],
    ['Datasheets Rejected / Needs Rework', props.stats.datasheets_rework, AlertTriangle],
    ['Engineering Data Created', props.stats.records_created, Layers3],
    ['Engineering Data Pending Review', props.stats.records_pending_review, DatabaseZap],
    ['Engineering Data Approved', props.stats.records_approved, CheckCircle2],
    ['Engineering Data Rejected / Needs Rework', props.stats.records_rework, AlertTriangle],
];
</script>

<template>
    <Head :title="`${publisher.name} · Publisher Performance`" />

    <LibraryAdminShell
        title="Publisher Performance"
        :subtitle="`${publisher.name} · ${publisher.email}`"
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Library Management' },
            { label: 'Publishers', href: '/admin/library/publishers' },
            { label: publisher.name },
        ]"
    >
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <article v-for="card in statCards" :key="String(card[0])" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <component :is="card[2]" class="size-5 text-emerald-700" />
                <p class="mt-4 text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ card[0] }}</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ card[1] }}</p>
            </article>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-black text-slate-950">Unreviewed Items</h2>
                    <p class="mt-1 text-sm text-slate-600">Publisher-owned Engineering Data waiting for review.</p>
                </div>
                <div v-if="unreviewed.length" class="divide-y divide-slate-100">
                    <div v-for="record in unreviewed" :key="record.uuid || record.id" class="grid gap-3 px-5 py-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                        <div>
                            <div class="font-black text-slate-950">{{ record.display_name }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ record.manufacturer }} · {{ record.device_type }}</div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <LifecycleStatusBadge :status="record.status" />
                                <ValidationStatusBadge :status="record.validation_status" />
                            </div>
                        </div>
                        <Link :href="record.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Open</Link>
                    </div>
                </div>
                <div v-else class="p-8 text-center">
                    <h3 class="font-black text-slate-950">No unreviewed items</h3>
                    <p class="mt-2 text-sm text-slate-600">This publisher has no owned records waiting for review.</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-black text-slate-950">Needs Attention</h2>
                    <p class="mt-1 text-sm text-slate-600">Rejected, needs rework, validation errors, duplicates or incomplete records.</p>
                </div>
                <div v-if="attention.length" class="divide-y divide-slate-100">
                    <div v-for="record in attention" :key="record.uuid || record.id" class="grid gap-3 px-5 py-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                        <div>
                            <div class="font-black text-slate-950">{{ record.display_name }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ record.manufacturer }} · {{ record.device_type }}</div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <LifecycleStatusBadge :status="record.status" />
                                <ValidationStatusBadge :status="record.validation_status" />
                            </div>
                        </div>
                        <Link :href="record.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Open</Link>
                    </div>
                </div>
                <div v-else class="p-8 text-center">
                    <h3 class="font-black text-slate-950">Nothing urgent</h3>
                    <p class="mt-2 text-sm text-slate-600">No publisher-owned records are currently flagged.</p>
                </div>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-black text-slate-950">Recent Datasheets</h2>
                <p class="mt-1 text-sm text-slate-600">Latest source datasheets owned by this publisher.</p>
            </div>
            <div v-if="recentDatasheets.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Datasheet</th>
                            <th class="px-4 py-3">Manufacturer</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Updated</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="datasheet in recentDatasheets" :key="datasheet.id">
                            <td class="px-4 py-4">
                                <div class="font-black text-slate-950">{{ datasheet.title }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ datasheet.device_type }}</div>
                            </td>
                            <td class="px-4 py-4">{{ datasheet.manufacturer }}</td>
                            <td class="px-4 py-4"><LifecycleStatusBadge :status="datasheet.status" /></td>
                            <td class="px-4 py-4 text-slate-600">{{ datasheet.updated_at || '—' }}</td>
                            <td class="px-4 py-4 text-right">
                                <Link :href="datasheet.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Open</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </LibraryAdminShell>
</template>
