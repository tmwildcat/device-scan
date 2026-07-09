<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    roleLabel?: string | null;
    member: any;
    routes: { suspend: string; reactivate: string; back: string };
}>();

const page = usePage<{ flash?: { success?: string | null } }>();

function suspend(): void {
    router.post(props.routes.suspend, {}, { preserveScroll: true });
}

function reactivate(): void {
    router.post(props.routes.reactivate, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${member.name} · Member`" />

    <LibraryAdminShell
        :title="member.name"
        subtitle="Member account, subscription summary, entitlements, private upload metadata and support actions."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Members', href: routes.back },
            { label: member.name },
        ]"
    >
        <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ page.props.flash.success }}</div>

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-black">Account Summary</h2>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        <div><dt class="text-xs font-black uppercase text-slate-500">Email</dt><dd class="mt-1">{{ member.email }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Role</dt><dd class="mt-1">{{ member.role_label }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Plan</dt><dd class="mt-1">{{ member.plan_code || 'None' }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Subscription</dt><dd class="mt-1 capitalize">{{ member.subscription_status || 'registered' }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Uploads</dt><dd class="mt-1">{{ member.uploads_count }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Private Records</dt><dd class="mt-1">{{ member.private_records_count }}</dd></div>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-black">Entitlements</h2>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span v-for="entitlement in member.entitlements" :key="entitlement" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ entitlement }}</span>
                    </div>
                    <p class="mt-4 rounded-md border border-dashed border-slate-300 p-3 text-sm text-slate-600">Entitlement override management is a placeholder here and will remain separate from Paddle billing.</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-black">Private Uploads</h2>
                    <div v-if="member.datasheets.length" class="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-200">
                        <div v-for="datasheet in member.datasheets" :key="datasheet.uuid" class="p-3 text-sm">
                            <div class="font-black">{{ datasheet.filename || datasheet.product_name || 'Datasheet' }}</div>
                            <div class="mt-1 text-slate-500">{{ datasheet.manufacturer || 'Unknown' }} · {{ datasheet.device_type }} · {{ datasheet.status }}</div>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-600">No private uploads found.</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-black">Private Engineering Records</h2>
                    <div v-if="member.records.length" class="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-200">
                        <div v-for="record in member.records" :key="record.uuid" class="p-3 text-sm">
                            <div class="font-black">{{ record.display_name || 'Engineering Record' }}</div>
                            <div class="mt-1 text-slate-500">{{ record.manufacturer || 'Unknown' }} · {{ record.device_type }} · {{ record.status }}</div>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-600">No private Engineering Records found.</p>
                </div>
            </div>

            <aside class="sticky top-20 self-start rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-black">Support Actions</h2>
                <div class="mt-4 grid gap-2">
                    <button v-if="member.subscription_status !== 'suspended'" class="rounded-md border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-black text-red-800" type="button" @click="suspend">Suspend Member</button>
                    <button v-else class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-black text-emerald-800" type="button" @click="reactivate">Reactivate Member</button>
                    <button class="rounded-md border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-black text-slate-400" disabled>Reset Password Placeholder</button>
                    <button class="rounded-md border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-black text-slate-400" disabled>Manage Entitlement Override</button>
                    <button class="rounded-md border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-black text-slate-400" disabled>Open Support Issue</button>
                </div>
                <Link :href="routes.back" class="mt-4 inline-flex text-sm font-black text-emerald-700">Back to Members</Link>
            </aside>
        </section>
    </LibraryAdminShell>
</template>
