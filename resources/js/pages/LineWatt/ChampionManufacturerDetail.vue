<script setup lang="ts">
import AdminShell from '@/components/linewatt/admin/AdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    champion: any;
    manufacturer: any;
}>();

const navItems = [{ label: 'Dashboard', href: '/champion' }];
</script>

<template>
    <Head :title="`${manufacturer.name} · Champion`" />

    <AdminShell
        workspace-title="Champion Dashboard"
        :context-name="champion.name"
        home-href="/champion"
        :role-label="roleLabel"
        :nav-items="navItems"
        :title="manufacturer.name"
        subtitle="Read-only manufacturer referral summary. Editing and billing actions are not available to Library Champions."
        :breadcrumbs="[
            { label: 'Dashboard', href: '/champion' },
            { label: manufacturer.name },
        ]"
    >
        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Manufacturer Summary</p>
                    <h2 class="mt-2 text-3xl font-black">{{ manufacturer.name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ manufacturer.slug }}</p>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-black uppercase text-slate-500">Plan</dt><dd class="mt-2 font-black">{{ manufacturer.plan }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-black uppercase text-slate-500">Subscription Status</dt><dd class="mt-2 font-black">{{ manufacturer.subscription_status }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-black uppercase text-slate-500">Subscribed From</dt><dd class="mt-2 font-black">{{ manufacturer.subscribed_from }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-black uppercase text-slate-500">Users</dt><dd class="mt-2 font-black">{{ manufacturer.users_count }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black">Inventory Summary</h3>
                    <dl class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-black uppercase text-slate-500">Datasheets</dt><dd class="mt-2 text-2xl font-black">{{ manufacturer.datasheets }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-black uppercase text-slate-500">Structured Engineering Data</dt><dd class="mt-2 text-2xl font-black">{{ manufacturer.records }}</dd></div>
                    </dl>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black">Commission Placeholder</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">No real commission or payout calculation is implemented yet.</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black">Recent Activity</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Activity details will appear after referral and subscription events are formalized.</p>
                </div>
                <Link href="/champion" class="block rounded-md border border-slate-200 bg-white px-4 py-2 text-center text-sm font-black text-slate-700 hover:bg-slate-50">
                    Back to Champion Dashboard
                </Link>
            </aside>
        </section>
    </AdminShell>
</template>
