<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    champions: { data: Array<any>; links: Array<{ url: string | null; label: string; active: boolean }> };
    statuses: Record<string, string>;
    commissionTypes: Record<string, string>;
}>();

const page = usePage<{ flash?: { success?: string | null } }>();
const form = useForm({
    name: '',
    email: '',
    phone: '',
    organisation: '',
    status: 'active',
    referral_code: '',
    commission_type: 'custom',
    commission_value: '',
    notes: '',
});

function submit(): void {
    form.post('/admin/library/champions', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Library Champions" />

    <LibraryAdminShell
        title="Library Champions"
        subtitle="Referral partners who help introduce and onboard manufacturer organisations."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Library Management' },
            { label: 'Champions' },
        ]"
    >
        <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <h2 class="font-black">Champion Roster</h2>
                    <p class="mt-1 text-sm text-slate-600">Commission tracking is a placeholder; no payouts are calculated or sent.</p>
                </div>
                <div v-if="champions.data.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Referral Code</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="champion in champions.data" :key="champion.uuid" class="hover:bg-slate-50/70">
                                <td class="px-4 py-4">
                                    <div class="font-black">{{ champion.name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ champion.organisation || 'Independent' }}</div>
                                </td>
                                <td class="px-4 py-4">{{ champion.email }}</td>
                                <td class="px-4 py-4 font-black">{{ champion.referral_code }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-black capitalize text-slate-700">{{ champion.status }}</span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <Link :href="champion.routes.show" class="inline-flex rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Open</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center">
                    <h2 class="font-black">No Library Champions yet</h2>
                    <p class="mt-2 text-sm text-slate-600">Create a champion profile and assign referral codes during OEM onboarding.</p>
                </div>
            </div>

            <form class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="font-black text-slate-950">Create Champion</h2>
                <p class="mt-1 text-sm text-slate-600">Creates a champion profile and user role. Password invitation remains a placeholder.</p>
                <div class="mt-5 grid gap-4">
                    <label class="block text-sm font-bold">Name
                        <input v-model="form.name" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                        <span v-if="form.errors.name" class="text-xs text-red-700">{{ form.errors.name }}</span>
                    </label>
                    <label class="block text-sm font-bold">Email
                        <input v-model="form.email" type="email" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                        <span v-if="form.errors.email" class="text-xs text-red-700">{{ form.errors.email }}</span>
                    </label>
                    <label class="block text-sm font-bold">Referral Code
                        <input v-model="form.referral_code" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" placeholder="CHAMPION-..." />
                        <span v-if="form.errors.referral_code" class="text-xs text-red-700">{{ form.errors.referral_code }}</span>
                    </label>
                    <label class="block text-sm font-bold">Organisation
                        <input v-model="form.organisation" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm font-bold">Status
                            <select v-model="form.status" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2">
                                <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </label>
                        <label class="block text-sm font-bold">Commission
                            <select v-model="form.commission_type" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2">
                                <option v-for="(label, value) in commissionTypes" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </label>
                    </div>
                    <label class="block text-sm font-bold">Commission Value
                        <input v-model="form.commission_value" type="number" min="0" step="0.01" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                    </label>
                    <label class="block text-sm font-bold">Notes
                        <textarea v-model="form.notes" class="mt-2 min-h-24 w-full rounded-md border border-slate-200 px-3 py-2" />
                    </label>
                </div>
                <button class="mt-5 w-full rounded-md bg-slate-950 px-4 py-2.5 text-sm font-black text-white hover:bg-slate-800" :disabled="form.processing">
                    Create Champion
                </button>
            </form>
        </section>

        <nav v-if="champions.links?.length > 3" class="mt-6 flex flex-wrap gap-2">
            <Link
                v-for="link in champions.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-md border px-3 py-2 text-sm font-bold"
                :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'"
                v-html="link.label"
            />
        </nav>
    </LibraryAdminShell>
</template>
