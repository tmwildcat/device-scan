<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    statuses: Record<string, string>;
    companies: {
        data: Array<any>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    readonly?: boolean;
}>();
</script>

<template>
    <Head title="OEM Subscribers" />

    <LibraryAdminShell
        title="OEM Subscribers"
        subtitle="Manage subscribed manufacturer organisations and their onboarding status."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: readonly ? '/publisher' : '/admin/library' },
            { label: 'Equipment Manufacturers' },
            { label: 'Subscribers' },
        ]"
        :primary-action="readonly ? null : { label: 'New OEM Subscriber', href: '/admin/library/oem-subscribers/new' }"
    >
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <div>
                    <h2 class="font-black">Subscribed Manufacturer Organisations</h2>
                    <p class="mt-1 text-sm text-slate-600">Invitation status, plans, users, and library activity by manufacturer.</p>
                </div>
            </div>

            <div v-if="companies.data.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">OEM Subscriber</th>
                            <th class="px-4 py-3">Primary Contact</th>
                            <th class="px-4 py-3">Plan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Users</th>
                            <th class="px-4 py-3">Datasheets</th>
                            <th class="px-4 py-3">Engineering Data</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="company in companies.data" :key="company.uuid || company.id" class="hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                <div class="font-black text-slate-950">{{ company.name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ company.slug }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-semibold">{{ company.primary_contact_name || 'Pending' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ company.primary_contact_email || 'No contact email' }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-black text-sky-800">{{ company.plan }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-black text-slate-700">{{ company.status_label }}</span>
                            </td>
                            <td class="px-4 py-4">{{ company.users_count }}</td>
                            <td class="px-4 py-4">{{ company.datasheets }}</td>
                            <td class="px-4 py-4">{{ company.records }}</td>
                            <td class="px-4 py-4 text-right">
                                <Link :href="company.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">
                                    Open
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-10 text-center">
                <h2 class="font-black">No OEM Subscribers yet</h2>
                <p class="mt-2 text-sm text-slate-600">Create an invitation to onboard a manufacturer organisation.</p>
            </div>
        </section>

        <nav v-if="companies.links?.length > 3" class="mt-6 flex flex-wrap gap-2">
            <Link
                v-for="link in companies.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-md border px-3 py-2 text-sm font-bold"
                :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'"
                v-html="link.label"
            />
        </nav>
    </LibraryAdminShell>
</template>
