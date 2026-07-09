<script setup lang="ts">
import PlatformAdminShell from '@/components/linewatt/admin/PlatformAdminShell.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

type Application = {
    uuid: string;
    name: string;
    client_id: string;
    description?: string | null;
    environment: string;
    allowed_domains: string[];
    scopes: string[];
    status: string;
    last_used_at?: string | null;
    last_used_ip?: string | null;
    created_by?: string | null;
    created_at?: string | null;
    revoked_at?: string | null;
};

const props = defineProps<{
    workspace: { role_label?: string | null; environment?: string | null; health?: string | null };
    application: Application;
    scopes: string[];
    environments: string[];
    oneTimeSecret?: string | null;
    recentLogs: Array<{ endpoint: string; method: string; scope_used?: string | null; status_code?: number | null; ip?: string | null; created_at?: string | null }>;
}>();

const form = useForm({
    name: props.application.name,
    description: props.application.description || '',
    environment: props.application.environment,
    allowed_domains: props.application.allowed_domains.join('\n'),
    scopes: [...props.application.scopes],
    status: props.application.status,
});

function toggleScope(scope: string): void {
    form.scopes = form.scopes.includes(scope)
        ? form.scopes.filter((item) => item !== scope)
        : [...form.scopes, scope];
}

function copyText(value: string): void {
    navigator.clipboard?.writeText(value);
}
</script>

<template>
    <Head :title="application.name" />

    <PlatformAdminShell
        :title="application.name"
        subtitle="First-party application access for LineWatt-owned systems."
        :role-label="workspace.role_label"
        :environment="workspace.environment"
        :health="workspace.health"
        :breadcrumbs="[
            { label: 'Platform', href: '/admin/platform' },
            { label: 'Internal App Access', href: '/admin/platform/internal-app-access' },
            { label: application.name },
        ]"
    >
        <section v-if="oneTimeSecret" class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-950 shadow-sm">
            <div class="text-xs font-black uppercase tracking-[0.14em]">Copy this secret now</div>
            <p class="mt-2 text-sm font-bold">This secret will not be shown again after you leave or refresh this page.</p>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <code class="rounded-md bg-white px-3 py-2 font-mono text-sm text-slate-950">{{ oneTimeSecret }}</code>
                <button class="rounded-md bg-slate-950 px-3 py-2 text-sm font-black text-white" type="button" @click="copyText(oneTimeSecret)">Copy Secret</button>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="text-xl font-black text-slate-950">Application Detail</h2>
                </div>
                <form class="grid gap-5 p-5" @submit.prevent="form.patch(`/admin/platform/internal-app-access/${application.uuid}`)">
                    <label class="block">
                        <span class="text-sm font-black text-slate-700">Application Name</span>
                        <input v-model="form.name" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-3 font-semibold" required />
                    </label>

                    <label class="block">
                        <span class="text-sm font-black text-slate-700">Description</span>
                        <textarea v-model="form.description" class="mt-2 min-h-24 w-full rounded-md border border-slate-200 px-3 py-3 font-semibold" />
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">Client ID</span>
                            <div class="mt-2 flex rounded-md border border-slate-200 bg-slate-50">
                                <code class="flex-1 px-3 py-3 font-mono text-sm">{{ application.client_id }}</code>
                                <button class="border-l border-slate-200 px-3 text-sm font-black" type="button" @click="copyText(application.client_id)">Copy</button>
                            </div>
                        </label>
                        <label class="block">
                            <span class="text-sm font-black text-slate-700">Status</span>
                            <select v-model="form.status" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-3 font-semibold">
                                <option value="active">active</option>
                                <option value="paused">paused</option>
                                <option value="revoked">revoked</option>
                            </select>
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-black text-slate-700">Environment</span>
                        <select v-model="form.environment" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-3 font-semibold">
                            <option v-for="environment in environments" :key="environment" :value="environment">{{ environment }}</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-black text-slate-700">Allowed Domains</span>
                        <textarea v-model="form.allowed_domains" class="mt-2 min-h-24 w-full rounded-md border border-slate-200 px-3 py-3 font-mono text-sm" />
                    </label>

                    <div>
                        <div class="text-sm font-black text-slate-700">Scopes</div>
                        <div class="mt-3 grid gap-2 md:grid-cols-2">
                            <label v-for="scope in scopes" :key="scope" class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 text-sm font-bold">
                                <input type="checkbox" :checked="form.scopes.includes(scope)" @change="toggleScope(scope)" />
                                <span>{{ scope }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-md bg-slate-950 px-4 py-3 text-sm font-black text-white" type="submit" :disabled="form.processing">Save Changes</button>
                        <button class="rounded-md border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700" type="button" @click="router.post(`/admin/platform/internal-app-access/${application.uuid}/pause`)">
                            {{ application.status === 'paused' ? 'Resume' : 'Pause' }}
                        </button>
                        <button class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-black text-red-800" type="button" @click="router.post(`/admin/platform/internal-app-access/${application.uuid}/revoke`)">Revoke</button>
                        <button class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-black text-amber-900" type="button" @click="router.post(`/admin/platform/internal-app-access/${application.uuid}/regenerate-secret`)">Regenerate Secret</button>
                    </div>
                </form>
            </section>

            <aside class="space-y-6">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Access Summary</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Last used</dt><dd class="font-black text-slate-900">{{ application.last_used_at || 'Never' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Last IP</dt><dd class="font-black text-slate-900">{{ application.last_used_ip || '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Created by</dt><dd class="font-black text-slate-900">{{ application.created_by || '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="font-bold text-slate-500">Created</dt><dd class="font-black text-slate-900">{{ application.created_at || '—' }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 p-5">
                        <h2 class="text-lg font-black text-slate-950">Recent Activity</h2>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <article v-for="log in recentLogs" :key="`${log.endpoint}-${log.created_at}`" class="p-4 text-sm">
                            <div class="font-black text-slate-950">{{ log.method }} {{ log.endpoint }}</div>
                            <div class="mt-1 text-xs font-bold text-slate-500">{{ log.scope_used || 'no scope' }} · {{ log.status_code || '—' }} · {{ log.ip || '—' }} · {{ log.created_at || '—' }}</div>
                        </article>
                        <div v-if="!recentLogs.length" class="p-6 text-center text-sm text-slate-500">No calls logged yet.</div>
                    </div>
                </section>
            </aside>
        </div>

        <Link href="/admin/platform/internal-app-access" class="mt-6 inline-flex rounded-md border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700">Back to Internal App Access</Link>
    </PlatformAdminShell>
</template>
