<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    company: { name: string; plan_label: string };
    canManage: boolean;
    mode: 'create' | 'edit';
    contact: any;
}>();

const form = useForm({
    country: props.contact.country || '',
    contact_type: props.contact.contact_type || 'general',
    contact_name: props.contact.contact_name || '',
    email: props.contact.email || '',
    phone: props.contact.phone || '',
    website: props.contact.website || '',
    region: props.contact.region || '',
    status: props.contact.status || 'active',
    notes: props.contact.notes || '',
});

function save(): void {
    if (props.mode === 'edit') {
        form.patch(`/admin/manufacturer/country-contacts/${props.contact.id}`);
        return;
    }

    form.post('/admin/manufacturer/country-contacts');
}
</script>

<template>
    <Head :title="mode === 'edit' ? 'Edit Country Contact' : 'Add Country Contact'" />

    <ManufacturerAdminShell
        :company="company"
        :title="mode === 'edit' ? 'Edit Country Contact' : 'Add Country Contact'"
        subtitle="Company-level regional contact data."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Company' }, { label: 'Country Contacts', href: '/admin/manufacturer/country-contacts' }, { label: mode === 'edit' ? 'Edit' : 'Add' }]"
    >
        <form class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="save">
            <div v-if="!canManage" class="mb-5 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">Manufacturer Users can view company master data but cannot save changes.</div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Country</span>
                    <input v-model="form.country" class="input mt-2" required />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Contact type</span>
                    <select v-model="form.contact_type" class="input mt-2">
                        <option value="general">General</option>
                        <option value="sales">Sales</option>
                        <option value="technical">Technical</option>
                        <option value="warranty">Warranty</option>
                        <option value="service">Service</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Contact name</span>
                    <input v-model="form.contact_name" class="input mt-2" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Email</span>
                    <input v-model="form.email" class="input mt-2" type="email" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Phone</span>
                    <input v-model="form.phone" class="input mt-2" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Website</span>
                    <input v-model="form.website" class="input mt-2" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Region</span>
                    <input v-model="form.region" class="input mt-2" />
                </label>
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Status</span>
                    <select v-model="form.status" class="input mt-2">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Notes</span>
                    <textarea v-model="form.notes" class="input mt-2 min-h-28" />
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link href="/admin/manufacturer/country-contacts" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-black text-slate-700">Cancel</Link>
                <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white disabled:opacity-40" :disabled="!canManage || form.processing" type="submit">Save contact</button>
            </div>
        </form>
    </ManufacturerAdminShell>
</template>
