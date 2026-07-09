<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    statuses: Record<string, string>;
    company: any;
}>();
</script>

<template>
    <Head :title="`${company.name} · OEM Subscriber`" />

    <LibraryAdminShell
        :title="company.name"
        subtitle="OEM Subscriber onboarding, subscription status, invitation state, and manufacturer administration context."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Equipment Manufacturers' },
            { label: 'Subscribers', href: '/admin/library/oem-subscribers' },
            { label: company.name },
        ]"
    >
        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Subscribed Manufacturer Organisation</p>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h2 class="text-3xl font-black text-slate-950">{{ company.name }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ company.slug }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-black text-sky-800">{{ company.plan }}</span>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-black text-slate-700">{{ company.status_label }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black text-slate-950">Primary Contact</h3>
                    <dl class="mt-4 grid gap-3 md:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Name</dt>
                            <dd class="mt-2 font-black">{{ company.primary_contact_name || 'Pending' }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Email</dt>
                            <dd class="mt-2 font-black">{{ company.primary_contact_email || 'Pending' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black text-slate-950">Champion / Referral</h3>
                    <dl class="mt-4 grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Champion</dt>
                            <dd class="mt-2 font-black">{{ company.champion?.name || 'Not assigned' }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Referral Code</dt>
                            <dd class="mt-2 font-black">{{ company.champion?.referral_code || company.referral_code || 'None' }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Status</dt>
                            <dd class="mt-2 font-black capitalize">{{ company.champion?.status || '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black text-slate-950">Onboarding Status Flow</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <article v-for="step in company.onboarding_steps" :key="step.label" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="font-black">{{ step.label }}</h4>
                                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-600">{{ step.status }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ step.description }}</p>
                        </article>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black text-slate-950">Users</h3>
                    <div v-if="company.users?.length" class="mt-4 grid gap-3 md:grid-cols-2">
                        <article v-for="user in company.users" :key="user.id" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="font-black">{{ user.name }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ user.email }}</div>
                            <div class="mt-3 text-xs font-black uppercase tracking-[0.12em] text-slate-500">{{ user.role }}</div>
                        </article>
                    </div>
                    <p v-else class="mt-4 text-sm text-slate-600">No Manufacturer Admin has accepted the invitation yet.</p>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black text-slate-950">Invitation</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Send this link to the primary contact. Email delivery is a placeholder in this milestone.</p>
                    <div v-if="company.invitation_url" class="mt-4 break-all rounded-md bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                        {{ company.invitation_url }}
                    </div>
                    <a v-if="company.invitation_url" :href="company.invitation_url" class="mt-4 inline-flex rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800">
                        Open Invitation
                    </a>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-black text-slate-950">Library Footprint</h3>
                    <dl class="mt-4 grid gap-3">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Datasheets</dt>
                            <dd class="mt-2 text-2xl font-black">{{ company.datasheets }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Engineering Data</dt>
                            <dd class="mt-2 text-2xl font-black">{{ company.records }}</dd>
                        </div>
                    </dl>
                </div>

                <Link href="/admin/library/oem-subscribers" class="block rounded-md border border-slate-200 bg-white px-4 py-2 text-center text-sm font-black text-slate-700 hover:bg-slate-50">
                    Back to OEM Subscribers
                </Link>
            </aside>
        </section>
    </LibraryAdminShell>
</template>
