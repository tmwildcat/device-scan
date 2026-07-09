<script setup lang="ts">
import PlatformAdminShell from '@/components/linewatt/admin/PlatformAdminShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    workspace: { role_label?: string | null; environment?: string | null; health?: string | null };
    scopes: string[];
    environments: string[];
}>();

const form = useForm({
    name: '',
    description: '',
    environment: 'local',
    allowed_domains: '',
    scopes: [] as string[],
});

function toggleScope(scope: string): void {
    form.scopes = form.scopes.includes(scope)
        ? form.scopes.filter((item) => item !== scope)
        : [...form.scopes, scope];
}
</script>

<template>
    <Head title="Register Internal Application" />

    <PlatformAdminShell
        title="Register Application"
        subtitle="Create first-party access for a LineWatt-owned app or internal service."
        :role-label="workspace.role_label"
        :environment="workspace.environment"
        :health="workspace.health"
        :breadcrumbs="[
            { label: 'Platform', href: '/admin/platform' },
            { label: 'Internal App Access', href: '/admin/platform/internal-app-access' },
            { label: 'Register Application' },
        ]"
    >
        <form class="grid gap-6 lg:grid-cols-[1fr_380px]" @submit.prevent="form.post('/admin/platform/internal-app-access')">
            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid gap-5">
                    <label class="block">
                        <span class="text-sm font-black text-slate-700">Application Name</span>
                        <input v-model="form.name" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-3 font-semibold" required />
                        <div v-if="form.errors.name" class="mt-1 text-sm font-bold text-red-700">{{ form.errors.name }}</div>
                    </label>

                    <label class="block">
                        <span class="text-sm font-black text-slate-700">Description</span>
                        <textarea v-model="form.description" class="mt-2 min-h-28 w-full rounded-md border border-slate-200 px-3 py-3 font-semibold" />
                    </label>

                    <label class="block">
                        <span class="text-sm font-black text-slate-700">Environment</span>
                        <select v-model="form.environment" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-3 font-semibold">
                            <option v-for="environment in props.environments" :key="environment" :value="environment">{{ environment }}</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-black text-slate-700">Allowed Domains</span>
                        <textarea v-model="form.allowed_domains" class="mt-2 min-h-24 w-full rounded-md border border-slate-200 px-3 py-3 font-mono text-sm" placeholder="https://studio.linewatt.com&#10;https://app.swem2m.com" />
                        <p class="mt-1 text-xs font-semibold text-slate-500">One domain per line. Used for review and future enforcement.</p>
                    </label>
                </div>
            </section>

            <aside class="space-y-5">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Scopes</h2>
                    <div class="mt-4 space-y-2">
                        <label v-for="scope in scopes" :key="scope" class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 text-sm font-bold">
                            <input type="checkbox" :checked="form.scopes.includes(scope)" @change="toggleScope(scope)" />
                            <span>{{ scope }}</span>
                        </label>
                    </div>
                </section>

                <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-950 shadow-sm">
                    <h2 class="font-black">Secret shown once</h2>
                    <p class="mt-2 text-sm leading-6">After registration, copy the generated secret immediately. It will not be shown again.</p>
                </section>

                <div class="flex gap-2">
                    <button class="rounded-md bg-slate-950 px-4 py-3 text-sm font-black text-white" type="submit" :disabled="form.processing">
                        Register Application
                    </button>
                    <Link href="/admin/platform/internal-app-access" class="rounded-md border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700">Cancel</Link>
                </div>
            </aside>
        </form>
    </PlatformAdminShell>
</template>
