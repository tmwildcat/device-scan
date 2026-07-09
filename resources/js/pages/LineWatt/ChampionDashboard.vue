<script setup lang="ts">
import AdminShell from '@/components/linewatt/admin/AdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    champion: any;
    summary: Record<string, any>;
    manufacturers: Array<any>;
}>();

const navItems = [{ label: 'Dashboard', href: '/champion' }];
</script>

<template>
    <Head title="Champion Dashboard" />

    <AdminShell
        workspace-title="Champion Dashboard"
        :context-name="champion.name"
        home-href="/champion"
        :role-label="roleLabel"
        :nav-items="navItems"
        title="Champion Dashboard"
        subtitle="Read-only view of manufacturers recruited through your referral code."
    >
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Referral Code</p>
            <div class="mt-2 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-black">{{ champion.referral_code }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ champion.organisation || champion.email }}</p>
                </div>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-black capitalize text-slate-700">{{ champion.status }}</span>
            </div>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Recruited Manufacturers</div>
                <div class="mt-3 text-3xl font-black">{{ summary.recruited_manufacturers }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Active Subscriptions</div>
                <div class="mt-3 text-3xl font-black">{{ summary.active_subscriptions }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Pending Invitations</div>
                <div class="mt-3 text-3xl font-black">{{ summary.pending_invitations }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Estimated Commission</div>
                <div class="mt-3 text-xl font-black">{{ summary.estimated_commission }}</div>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="font-black">Recruited Manufacturers</h2>
                <p class="mt-1 text-sm text-slate-600">Read-only manufacturer subscription and library footprint.</p>
            </div>
            <div v-if="manufacturers.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Manufacturer</th>
                            <th class="px-4 py-3">Subscription Type</th>
                            <th class="px-4 py-3">Subscribed From</th>
                            <th class="px-4 py-3">Subscription Status</th>
                            <th class="px-4 py-3">Invitation Status</th>
                            <th class="px-4 py-3">Datasheets</th>
                            <th class="px-4 py-3">Engineering Data Sets</th>
                            <th class="px-4 py-3">Last Activity</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="manufacturer in manufacturers" :key="manufacturer.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-4 font-black">{{ manufacturer.name }}</td>
                            <td class="px-4 py-4">{{ manufacturer.plan }}</td>
                            <td class="px-4 py-4">{{ manufacturer.subscribed_from }}</td>
                            <td class="px-4 py-4">{{ manufacturer.subscription_status }}</td>
                            <td class="px-4 py-4">{{ manufacturer.invitation_status }}</td>
                            <td class="px-4 py-4">{{ manufacturer.datasheets }}</td>
                            <td class="px-4 py-4">{{ manufacturer.records }}</td>
                            <td class="px-4 py-4">{{ manufacturer.last_activity }}</td>
                            <td class="px-4 py-4 text-right">
                                <Link :href="manufacturer.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Open</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-10 text-center">
                <h2 class="font-black">No recruited manufacturers yet</h2>
                <p class="mt-2 text-sm text-slate-600">Manufacturers assigned to your referral code will appear here.</p>
            </div>
        </section>
    </AdminShell>
</template>
