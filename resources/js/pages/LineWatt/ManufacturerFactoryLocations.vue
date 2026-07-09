<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Factory } from 'lucide-vue-next';

defineProps<{
    company: { name: string; plan_label: string };
    canManage: boolean;
    permissionMessage?: string | null;
    locations: {
        data: Array<any>;
        links?: Array<{ url: string | null; label: string; active: boolean }>;
    } | null;
}>();

const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>();
const deleteForm = useForm({});

function removeLocation(location: any): void {
    if (!window.confirm(`Remove ${location.factory_name}?`)) {
        return;
    }

    deleteForm.delete(`/admin/manufacturer/company/factories/${location.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Factory Locations" />

    <ManufacturerAdminShell
        :company="company"
        title="Factory Locations"
        subtitle="Company-level manufacturing locations. These are not datasheet-specific metadata."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Company' }, { label: 'Factory Locations' }]"
        :primary-action="canManage ? { label: 'Add Factory', href: '/admin/manufacturer/company/factories/create' } : null"
    >
        <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ page.props.flash.success }}</div>
        <div v-if="permissionMessage" class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">{{ permissionMessage }}</div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <Factory class="size-5 text-emerald-700" />
                <div>
                    <h2 class="text-lg font-black">Manufacturing Sites</h2>
                    <p class="mt-1 text-sm text-slate-600">Track factory location, capability, certification and lifecycle status.</p>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                        <tr>
                            <th class="px-3 py-3 text-left">Factory</th>
                            <th class="px-3 py-3 text-left">Location</th>
                            <th class="px-3 py-3 text-left">Product Types</th>
                            <th class="px-3 py-3 text-left">Capacity</th>
                            <th class="px-3 py-3 text-left">Certifications</th>
                            <th class="px-3 py-3 text-left">Status</th>
                            <th class="px-3 py-3 text-left">Updated</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="location in locations?.data || []" :key="location.id">
                            <td class="px-3 py-4">
                                <div class="font-black">{{ location.factory_name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ location.address || 'Address pending' }}</div>
                            </td>
                            <td class="px-3 py-4">{{ [location.city, location.state, location.country].filter(Boolean).join(', ') || 'Pending' }}</td>
                            <td class="px-3 py-4">{{ location.product_types || 'Pending' }}</td>
                            <td class="px-3 py-4">{{ location.production_capacity || 'Pending' }}</td>
                            <td class="px-3 py-4">{{ location.certifications || 'Pending' }}</td>
                            <td class="px-3 py-4 capitalize">{{ location.status }}</td>
                            <td class="px-3 py-4">{{ location.updated || 'Pending' }}</td>
                            <td class="px-3 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <Link :href="location.edit_href" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold hover:bg-slate-50">Edit</Link>
                                    <button class="rounded-md border border-red-200 px-3 py-2 text-xs font-bold text-red-700 disabled:opacity-40" :disabled="!canManage || deleteForm.processing" type="button" @click="removeLocation(location)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="(locations?.data || []).length === 0">
                            <td colspan="8" class="px-3 py-10 text-center text-slate-600">No factory locations have been added yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="locations?.links?.length" class="mt-4 flex flex-wrap gap-2">
                <Link v-for="link in locations.links" :key="link.label" :href="link.url || '#'" class="rounded-md border px-3 py-2 text-sm font-bold" :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 text-slate-700'" v-html="link.label" />
            </div>
        </section>
    </ManufacturerAdminShell>
</template>
