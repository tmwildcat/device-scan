<script setup lang="ts">
import PlatformAdminShell from '@/components/linewatt/admin/PlatformAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

type Application = {
    uuid: string;
    name: string;
    client_id: string;
    environment: string;
    allowed_domains: string[];
    scopes: string[];
    status: string;
    last_used_at?: string | null;
    href: string;
};

defineProps<{
    workspace: { role_label?: string | null; environment?: string | null; health?: string | null };
    applications: { data: Application[]; links?: unknown[] };
}>();
</script>

<template>
    <Head title="Internal App Access" />

    <PlatformAdminShell
        title="Internal App Access"
        subtitle="Generate and manage first-party credentials for LineWatt-owned applications and internal services."
        :role-label="workspace.role_label"
        :environment="workspace.environment"
        :health="workspace.health"
        :breadcrumbs="[
            { label: 'Platform', href: '/admin/platform' },
            { label: 'Infrastructure' },
            { label: 'Internal App Access' },
        ]"
        :primary-action="{ label: 'Register Application', href: '/admin/platform/internal-app-access/new' }"
    >
        <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-xl font-black text-slate-950">Registered Applications</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    These credentials are for LineWatt-owned apps only. They are not customer developer keys.
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.14em] text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Application</th>
                            <th class="px-5 py-3">Environment</th>
                            <th class="px-5 py-3">Allowed Domains</th>
                            <th class="px-5 py-3">Scopes</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Last Used</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="application in applications.data" :key="application.uuid">
                            <td class="px-5 py-4">
                                <div class="font-black text-slate-950">{{ application.name }}</div>
                                <div class="mt-1 font-mono text-xs text-slate-500">{{ application.client_id }}</div>
                            </td>
                            <td class="px-5 py-4 font-semibold capitalize text-slate-700">{{ application.environment }}</td>
                            <td class="max-w-xs px-5 py-4 text-slate-600">
                                <span v-if="application.allowed_domains.length">{{ application.allowed_domains.join(', ') }}</span>
                                <span v-else>—</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ application.scopes.length }} scopes</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full border px-2.5 py-1 text-xs font-black capitalize" :class="application.status === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-100 text-slate-700'">
                                    {{ application.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ application.last_used_at || 'Never' }}</td>
                            <td class="px-5 py-4 text-right">
                                <Link :href="application.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">Open</Link>
                            </td>
                        </tr>
                        <tr v-if="!applications.data.length">
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="font-black text-slate-950">No internal applications registered</div>
                                <p class="mt-1 text-sm text-slate-500">Register the first LineWatt-owned app when Studio or another first-party service needs access.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </PlatformAdminShell>
</template>
