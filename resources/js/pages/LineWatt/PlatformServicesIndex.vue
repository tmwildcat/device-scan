<script setup lang="ts">
import PlatformAdminShell from '@/components/linewatt/admin/PlatformAdminShell.vue';
import MetricCard from '@/components/linewatt/MetricCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Activity, PauseCircle, PowerOff, Wrench } from 'lucide-vue-next';

type Service = {
    uuid: string;
    name: string;
    service_key: string;
    service_type: string;
    status: string;
    environment: string;
    required_scopes: string[];
    last_health_check_at?: string | null;
    href: string;
};

const props = defineProps<{
    workspace: { role_label?: string | null; environment?: string | null; health?: string | null };
    services: { data: Service[] };
    summary: { active: number; paused: number; disabled: number; maintenance: number };
}>();

const statusClass = (status: string): string => {
    if (status === 'active') return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    if (status === 'paused') return 'border-amber-200 bg-amber-50 text-amber-800';
    if (status === 'disabled') return 'border-red-200 bg-red-50 text-red-800';
    return 'border-sky-200 bg-sky-50 text-sky-800';
};
</script>

<template>
    <Head title="Services" />

    <PlatformAdminShell
        title="Services"
        subtitle="Manage platform service inventory, ownership, scopes and operational status placeholders."
        :role-label="workspace.role_label"
        :environment="workspace.environment"
        :health="workspace.health"
        :breadcrumbs="[
            { label: 'Platform', href: '/admin/platform' },
            { label: 'Infrastructure' },
            { label: 'Services' },
        ]"
    >
        <section class="grid gap-4 md:grid-cols-4">
            <MetricCard :icon="Activity" label="Active" :value="props.summary.active" tone="emerald" />
            <MetricCard :icon="PauseCircle" label="Paused" :value="props.summary.paused" tone="amber" />
            <MetricCard :icon="PowerOff" label="Disabled" :value="props.summary.disabled" tone="rose" />
            <MetricCard :icon="Wrench" label="Maintenance" :value="props.summary.maintenance" tone="sky" />
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-xl font-black text-slate-950">Platform Services</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">Service status is a management registry for now. It does not stop live routes or workers.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.14em] text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Service</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Environment</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Required Scopes</th>
                            <th class="px-5 py-3">Last Health Check</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="service in services.data" :key="service.uuid">
                            <td class="px-5 py-4">
                                <div class="font-black text-slate-950">{{ service.name }}</div>
                                <div class="mt-1 font-mono text-xs text-slate-500">{{ service.service_key }}</div>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ service.service_type.replaceAll('_', ' ') }}</td>
                            <td class="px-5 py-4 font-semibold capitalize text-slate-700">{{ service.environment }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full border px-2.5 py-1 text-xs font-black capitalize" :class="statusClass(service.status)">
                                    {{ service.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ service.required_scopes.length ? service.required_scopes.join(', ') : '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ service.last_health_check_at || 'Not checked' }}</td>
                            <td class="px-5 py-4 text-right">
                                <Link :href="service.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">Open</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </PlatformAdminShell>
</template>
