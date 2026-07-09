<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    company: { name: string; plan_label: string };
    canManage: boolean;
    mode: 'create' | 'edit';
    location: any;
}>();

const form = useForm({
    factory_name: props.location.factory_name || '',
    country: props.location.country || '',
    state: props.location.state || '',
    city: props.location.city || '',
    address: props.location.address || '',
    product_types: props.location.product_types || '',
    production_capacity: props.location.production_capacity || '',
    certifications: props.location.certifications || '',
    status: props.location.status || 'active',
    notes: props.location.notes || '',
});

function save(): void {
    if (props.mode === 'edit') {
        form.patch(`/admin/manufacturer/company/factories/${props.location.id}`);
        return;
    }

    form.post('/admin/manufacturer/company/factories');
}
</script>

<template>
    <Head :title="mode === 'edit' ? 'Edit Factory Location' : 'Add Factory Location'" />

    <ManufacturerAdminShell
        :company="company"
        :title="mode === 'edit' ? 'Edit Factory Location' : 'Add Factory Location'"
        subtitle="Company-level manufacturing site data."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Company' }, { label: 'Factory Locations', href: '/admin/manufacturer/company/factories' }, { label: mode === 'edit' ? 'Edit' : 'Add' }]"
    >
        <form class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="save">
            <div v-if="!canManage" class="mb-5 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">Manufacturer Users can view company master data but cannot save changes.</div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Factory name</span>
                    <input v-model="form.factory_name" class="input mt-2" required />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Country</span>
                    <input v-model="form.country" class="input mt-2" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">State</span>
                    <input v-model="form.state" class="input mt-2" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">City</span>
                    <input v-model="form.city" class="input mt-2" />
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Address</span>
                    <input v-model="form.address" class="input mt-2" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Product types</span>
                    <input v-model="form.product_types" class="input mt-2" placeholder="Modules, Inverters..." />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Production capacity</span>
                    <input v-model="form.production_capacity" class="input mt-2" placeholder="e.g. 2 GW/year" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Certifications</span>
                    <input v-model="form.certifications" class="input mt-2" placeholder="ISO 9001, ISO 14001..." />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Status</span>
                    <select v-model="form.status" class="input">
                        <option value="active">Active</option>
                        <option value="planned">Planned</option>
                        <option value="inactive">Inactive</option>
                        <option value="closed">Closed</option>
                    </select>
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Notes</span>
                    <textarea v-model="form.notes" class="input mt-2 min-h-28" />
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link href="/admin/manufacturer/company/factories" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-black text-slate-700">Cancel</Link>
                <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white disabled:opacity-40" :disabled="!canManage || form.processing" type="submit">Save factory</button>
            </div>
        </form>
    </ManufacturerAdminShell>
</template>
