<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    status?: string | null;
    requests: {
        data: Array<any>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
</script>

<template>
    <Head title="Manufacturer Requests" />

    <LibraryAdminShell
        title="Manufacturer Requests"
        subtitle="Approval queue for manufacturer access. Manufacturer Admin accounts are created only after verification."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Equipment Manufacturers' },
            { label: 'Manufacturer Requests' },
        ]"
    >
        <div class="mb-5 flex flex-wrap gap-2">
            <Link href="/admin/library/partner-requests" class="rounded-md border px-3 py-2 text-sm font-bold" :class="!status ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700'">All</Link>
            <Link href="/admin/library/partner-requests?status=pending" class="rounded-md border px-3 py-2 text-sm font-bold" :class="status === 'pending' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700'">Pending</Link>
            <Link href="/admin/library/partner-requests?status=more_information_requested" class="rounded-md border px-3 py-2 text-sm font-bold" :class="status === 'more_information_requested' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700'">More Info</Link>
            <Link href="/admin/library/partner-requests?status=approved" class="rounded-md border px-3 py-2 text-sm font-bold" :class="status === 'approved' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700'">Approved</Link>
            <Link href="/admin/library/partner-requests?status=rejected" class="rounded-md border px-3 py-2 text-sm font-bold" :class="status === 'rejected' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700'">Rejected</Link>
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div v-if="requests.data.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr><th class="px-4 py-3">Company</th><th class="px-4 py-3">Brand</th><th class="px-4 py-3">Country</th><th class="px-4 py-3">Domain</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Submitted</th><th class="px-4 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="request in requests.data" :key="request.uuid">
                            <td class="px-4 py-4">
                                <div class="font-black">{{ request.company_name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ request.contact_person }} · {{ request.contact_email }}</div>
                            </td>
                            <td class="px-4 py-4">{{ request.requested_manufacturer_brand }}</td>
                            <td class="px-4 py-4">{{ request.country }}</td>
                            <td class="px-4 py-4">{{ request.official_email_domain }}</td>
                            <td class="px-4 py-4 capitalize">{{ request.status.replaceAll('_', ' ') }}</td>
                            <td class="px-4 py-4">{{ request.created_at }}</td>
                            <td class="px-4 py-4 text-right"><Link :href="request.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">Open</Link></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-10 text-center">
                <h2 class="font-black">No partner requests</h2>
                <p class="mt-2 text-sm text-slate-600">New OEM applications will appear here for review.</p>
            </div>
        </section>
    </LibraryAdminShell>
</template>
