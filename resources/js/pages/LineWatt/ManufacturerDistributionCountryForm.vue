<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    company: { name: string; plan_label: string };
    canManage: boolean;
    mode: 'create' | 'edit';
    country: any;
}>();

const form = useForm({
    country: props.country.country || '',
    region: props.country.region || '',
    availability_status: props.country.availability_status || 'available',
    channel_model: props.country.channel_model || '',
    distributor_name: props.country.distributor_name || '',
    sales_contact: props.country.sales_contact || '',
    service_contact: props.country.service_contact || '',
    notes: props.country.notes || '',
});

function save(): void {
    if (props.mode === 'edit') {
        form.patch(`/admin/manufacturer/company/distribution-countries/${props.country.id}`);
        return;
    }

    form.post('/admin/manufacturer/company/distribution-countries');
}
</script>

<template>
    <Head :title="mode === 'edit' ? 'Edit Distribution Country' : 'Add Distribution Country'" />

    <ManufacturerAdminShell
        :company="company"
        :title="mode === 'edit' ? 'Edit Distribution Country' : 'Add Distribution Country'"
        subtitle="Company-level market availability data."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Company' }, { label: 'Distribution Countries', href: '/admin/manufacturer/company/distribution-countries' }, { label: mode === 'edit' ? 'Edit' : 'Add' }]"
    >
        <form class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="save">
            <div v-if="!canManage" class="mb-5 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">Manufacturer Users can view company master data but cannot save changes.</div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Country</span>
                    <input v-model="form.country" class="input mt-2" required />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Region</span>
                    <input v-model="form.region" class="input mt-2" placeholder="South Asia, Europe..." />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Availability</span>
                    <select v-model="form.availability_status" class="input mt-2">
                        <option value="available">Available</option>
                        <option value="planned">Planned</option>
                        <option value="restricted">Restricted</option>
                        <option value="discontinued">Discontinued</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Channel model</span>
                    <input v-model="form.channel_model" class="input mt-2" placeholder="Distributor, Direct, Both..." />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Distributor name</span>
                    <input v-model="form.distributor_name" class="input mt-2" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Sales contact</span>
                    <input v-model="form.sales_contact" class="input mt-2" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Service contact</span>
                    <input v-model="form.service_contact" class="input mt-2" />
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Notes</span>
                    <textarea v-model="form.notes" class="input mt-2 min-h-28" />
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link href="/admin/manufacturer/company/distribution-countries" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-black text-slate-700">Cancel</Link>
                <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white disabled:opacity-40" :disabled="!canManage || form.processing" type="submit">Save country</button>
            </div>
        </form>
    </ManufacturerAdminShell>
</template>
