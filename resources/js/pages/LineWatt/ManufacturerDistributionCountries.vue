<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Globe2 } from 'lucide-vue-next';

defineProps<{
    company: { name: string; plan_label: string };
    canManage: boolean;
    permissionMessage?: string | null;
    countries: {
        data: Array<any>;
        links?: Array<{ url: string | null; label: string; active: boolean }>;
    } | null;
}>();

const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>();
const deleteForm = useForm({});

function removeCountry(country: any): void {
    if (!window.confirm(`Remove distribution country ${country.country}?`)) {
        return;
    }

    deleteForm.delete(`/admin/manufacturer/company/distribution-countries/${country.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Distribution Countries" />

    <ManufacturerAdminShell
        :company="company"
        title="Distribution Countries"
        subtitle="Company-level country availability and channel model."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Company' }, { label: 'Distribution Countries' }]"
        :primary-action="canManage ? { label: 'Add Country', href: '/admin/manufacturer/company/distribution-countries/create' } : null"
    >
        <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ page.props.flash.success }}</div>
        <div v-if="permissionMessage" class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">{{ permissionMessage }}</div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <Globe2 class="size-5 text-emerald-700" />
                <div>
                    <h2 class="text-lg font-black">Market Availability</h2>
                    <p class="mt-1 text-sm text-slate-600">Track where this manufacturer sells, supports, services and distributes products.</p>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                        <tr>
                            <th class="px-3 py-3 text-left">Country</th>
                            <th class="px-3 py-3 text-left">Region</th>
                            <th class="px-3 py-3 text-left">Availability</th>
                            <th class="px-3 py-3 text-left">Channel</th>
                            <th class="px-3 py-3 text-left">Distributor</th>
                            <th class="px-3 py-3 text-left">Contacts</th>
                            <th class="px-3 py-3 text-left">Updated</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="country in countries?.data || []" :key="country.id">
                            <td class="px-3 py-4 font-black">{{ country.country }}</td>
                            <td class="px-3 py-4">{{ country.region || 'Pending' }}</td>
                            <td class="px-3 py-4 capitalize">{{ country.availability_status }}</td>
                            <td class="px-3 py-4">{{ country.channel_model || 'Pending' }}</td>
                            <td class="px-3 py-4">{{ country.distributor_name || 'Pending' }}</td>
                            <td class="px-3 py-4">
                                <div>{{ country.sales_contact || 'Sales pending' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ country.service_contact || 'Service pending' }}</div>
                            </td>
                            <td class="px-3 py-4">{{ country.updated || 'Pending' }}</td>
                            <td class="px-3 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <Link :href="country.edit_href" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold hover:bg-slate-50">Edit</Link>
                                    <button class="rounded-md border border-red-200 px-3 py-2 text-xs font-bold text-red-700 disabled:opacity-40" :disabled="!canManage || deleteForm.processing" type="button" @click="removeCountry(country)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="(countries?.data || []).length === 0">
                            <td colspan="8" class="px-3 py-10 text-center text-slate-600">No distribution countries have been added yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="countries?.links?.length" class="mt-4 flex flex-wrap gap-2">
                <Link v-for="link in countries.links" :key="link.label" :href="link.url || '#'" class="rounded-md border px-3 py-2 text-sm font-bold" :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 text-slate-700'" v-html="link.label" />
            </div>
        </section>
    </ManufacturerAdminShell>
</template>
