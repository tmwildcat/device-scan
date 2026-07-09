<script setup lang="ts">
import PlatformAdminShell from '@/components/linewatt/admin/PlatformAdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';

type Service = {
    uuid: string;
    name: string;
    service_key: string;
    service_type: string;
    status: string;
    environment: string;
    description?: string | null;
    required_scopes: string[];
    endpoint_url?: string | null;
    health_check_url?: string | null;
    last_health_check_at?: string | null;
    last_status_message?: string | null;
    linked_internal_app?: { name: string; client_id: string } | null;
    metadata: Record<string, unknown>;
};

defineProps<{
    workspace: { role_label?: string | null; environment?: string | null; health?: string | null };
    service: Service;
    recentActivity: Array<{ label: string; detail?: string | null; status_code?: number | null; created_at?: string | null }>;
}>();

const statusClass = (status: string): string => {
    if (status === 'active') return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    if (status === 'paused') return 'border-amber-200 bg-amber-50 text-amber-800';
    if (status === 'disabled') return 'border-red-200 bg-red-50 text-red-800';
    return 'border-sky-200 bg-sky-50 text-sky-800';
};
</script>

<template>
    <Head :title="service.name" />

    <PlatformAdminShell
        :title="service.name"
        subtitle="Platform service identity, status, scopes and operational placeholders."
        :role-label="workspace.role_label"
        :environment="workspace.environment"
        :health="workspace.health"
        :breadcrumbs="[
            { label: 'Platform', href: '/admin/platform' },
            { label: 'Services', href: '/admin/platform/services' },
            { label: service.name },
        ]"
    >
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">{{ service.service_type.replaceAll('_', ' ') }}</div>
                    <h2 class="mt-2 text-2xl font-black text-slate-950">{{ service.name }}</h2>
                    <p class="mt-1 max-w-4xl text-sm leading-6 text-slate-600">{{ service.description || 'No description provided.' }}</p>
                </div>
                <span class="rounded-full border px-3 py-1 text-xs font-black capitalize" :class="statusClass(service.status)">
                    {{ service.status }}
                </span>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
                <button class="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700" type="button" @click="router.post(`/admin/platform/services/${service.uuid}/pause`)">
                    {{ service.status === 'paused' ? 'Resume' : 'Pause' }}
                </button>
                <button class="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-black text-red-800" type="button" @click="router.post(`/admin/platform/services/${service.uuid}/disable`)">
                    Disable
                </button>
                <button class="rounded-md border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-black text-sky-800" type="button" @click="router.post(`/admin/platform/services/${service.uuid}/health-check`)">
                    Run Health Check
                </button>
            </div>
            <p class="mt-3 text-xs font-bold text-slate-500">These actions update the service registry only. Runtime enforcement is intentionally not enabled in this milestone.</p>
        </section>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_420px]">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="text-xl font-black text-slate-950">Service Configuration</h2>
                </div>
                <dl class="divide-y divide-slate-100">
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[240px_1fr]">
                        <dt class="text-sm font-black text-slate-500">Service Key</dt>
                        <dd class="font-mono text-sm font-semibold text-slate-900">{{ service.service_key }}</dd>
                    </div>
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[240px_1fr]">
                        <dt class="text-sm font-black text-slate-500">Environment</dt>
                        <dd class="text-sm font-semibold capitalize text-slate-900">{{ service.environment }}</dd>
                    </div>
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[240px_1fr]">
                        <dt class="text-sm font-black text-slate-500">Endpoint</dt>
                        <dd class="font-mono text-sm font-semibold text-slate-900">{{ service.endpoint_url || '—' }}</dd>
                    </div>
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[240px_1fr]">
                        <dt class="text-sm font-black text-slate-500">Health Check URL</dt>
                        <dd class="font-mono text-sm font-semibold text-slate-900">{{ service.health_check_url || '—' }}</dd>
                    </div>
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[240px_1fr]">
                        <dt class="text-sm font-black text-slate-500">Required Scopes</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ service.required_scopes.length ? service.required_scopes.join(', ') : '—' }}</dd>
                    </div>
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[240px_1fr]">
                        <dt class="text-sm font-black text-slate-500">Linked Internal App</dt>
                        <dd class="text-sm font-semibold text-slate-900">
                            <span v-if="service.linked_internal_app">{{ service.linked_internal_app.name }} · {{ service.linked_internal_app.client_id }}</span>
                            <span v-else>—</span>
                        </dd>
                    </div>
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[240px_1fr]">
                        <dt class="text-sm font-black text-slate-500">Last Health Check</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ service.last_health_check_at || 'Not checked' }}</dd>
                    </div>
                    <div class="grid gap-3 px-5 py-4 md:grid-cols-[240px_1fr]">
                        <dt class="text-sm font-black text-slate-500">Status Message</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ service.last_status_message || 'No status message.' }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="space-y-6">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Configuration Notes</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        This service registry centralizes operational visibility. It is not yet wired as a kill switch, so existing API, MCP foundation, compiler and storage paths remain unchanged.
                    </p>
                    <div v-if="Object.keys(service.metadata || {}).length" class="mt-4 rounded-md bg-slate-50 p-3">
                        <pre class="whitespace-pre-wrap text-xs text-slate-700">{{ JSON.stringify(service.metadata, null, 2) }}</pre>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 p-5">
                        <h2 class="text-lg font-black text-slate-950">Recent Audit / Activity</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <article v-for="item in recentActivity" :key="`${item.label}-${item.created_at}`" class="p-4 text-sm">
                            <div class="font-black text-slate-950">{{ item.label }}</div>
                            <div class="mt-1 text-xs font-bold text-slate-500">{{ item.detail || '—' }} · {{ item.status_code || '—' }} · {{ item.created_at || '—' }}</div>
                        </article>
                    </div>
                </section>
            </aside>
        </div>

        <Link href="/admin/platform/services" class="mt-6 inline-flex rounded-md border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700">Back to Services</Link>
    </PlatformAdminShell>
</template>
