<script setup lang="ts">
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    manufacturer: any;
    datasheets: { data: Array<any>; links: Array<{ url: string | null; label: string; active: boolean }> };
    records: { data: Array<any>; links: Array<{ url: string | null; label: string; active: boolean }> };
}>();

function labelize(value?: string | null): string {
    return value ? value.replaceAll('_', ' ') : '—';
}
</script>

<template>
    <Head :title="`${manufacturer.name} · Manufacturer Inventory`" />

    <LibraryAdminShell
        :title="manufacturer.name"
        subtitle="Manufacturer inventory across source datasheets and Structured Engineering Data."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Equipment Manufacturers' },
            { label: 'All Manufacturers', href: '/admin/library/manufacturers' },
            { label: manufacturer.name },
        ]"
        :primary-action="manufacturer.is_subscribed ? null : { label: 'Create OEM Subscriber', href: manufacturer.create_oem_href }"
    >
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Manufacturer Profile Summary</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950">{{ manufacturer.name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ manufacturer.headquarters_country }} · {{ labelize(manufacturer.status) }}</p>
                </div>
                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-black capitalize text-slate-700">
                    {{ labelize(manufacturer.subscriber_status) }}
                </span>
            </div>

            <dl class="mt-5 grid gap-4 md:grid-cols-4">
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Device Types</dt>
                    <dd class="mt-2 font-black">{{ manufacturer.device_types?.map(labelize).join(', ') || '—' }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Datasheets</dt>
                    <dd class="mt-2 text-2xl font-black">{{ manufacturer.datasheets }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Engineering Data</dt>
                    <dd class="mt-2 text-2xl font-black">{{ manufacturer.records }}</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Latest Activity</dt>
                    <dd class="mt-2 font-black">{{ manufacturer.last_updated }}</dd>
                </div>
            </dl>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <h3 class="font-black">Datasheets</h3>
                </div>
                <div v-if="datasheets.data.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                            <tr><th class="px-4 py-3">Datasheet</th><th class="px-4 py-3">Device</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Uploaded</th><th class="px-4 py-3 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="datasheet in datasheets.data" :key="datasheet.uuid">
                                <td class="px-4 py-4 font-black">{{ datasheet.title }}</td>
                                <td class="px-4 py-4 capitalize">{{ labelize(datasheet.device_type) }}</td>
                                <td class="px-4 py-4"><LifecycleStatusBadge :status="datasheet.status" /></td>
                                <td class="px-4 py-4">{{ datasheet.uploaded_at }}</td>
                                <td class="px-4 py-4 text-right"><Link :href="datasheet.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">Open</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="p-8 text-sm text-slate-600">No datasheets found for this manufacturer.</p>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <h3 class="font-black">Structured Engineering Data</h3>
                </div>
                <div v-if="records.data.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                            <tr><th class="px-4 py-3">Engineering Data</th><th class="px-4 py-3">Device</th><th class="px-4 py-3">Validation</th><th class="px-4 py-3 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="record in records.data" :key="record.uuid || record.id">
                                <td class="px-4 py-4 font-black">{{ record.display_name || record.model_name || record.model_series || 'Engineering Data Set' }}</td>
                                <td class="px-4 py-4 capitalize">{{ labelize(record.device_type) }}</td>
                                <td class="px-4 py-4"><ValidationStatusBadge :status="record.validation_status" /></td>
                                <td class="px-4 py-4 text-right"><Link :href="record.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">Open</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="p-8 text-sm text-slate-600">No Structured Engineering Data found for this manufacturer.</p>
            </div>
        </section>
    </LibraryAdminShell>
</template>
