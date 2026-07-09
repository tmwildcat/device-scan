<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ContactRound } from 'lucide-vue-next';

defineProps<{
    company: { name: string; plan_label: string };
    canManage: boolean;
    permissionMessage?: string | null;
    contacts: {
        data: Array<any>;
        links?: Array<{ url: string | null; label: string; active: boolean }>;
    } | null;
}>();

const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>();
const deleteForm = useForm({});

function removeContact(contact: any): void {
    if (!window.confirm(`Remove ${contact.country} ${contact.contact_type} contact?`)) {
        return;
    }

    deleteForm.delete(`/admin/manufacturer/country-contacts/${contact.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Country Contacts" />

    <ManufacturerAdminShell
        :company="company"
        title="Country Contacts"
        subtitle="Company-level sales, technical, warranty and service contacts by country."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Company' }, { label: 'Country Contacts' }]"
        :primary-action="canManage ? { label: 'Add Contact', href: '/admin/manufacturer/country-contacts/create' } : null"
    >
        <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ page.props.flash.success }}</div>
        <div v-if="permissionMessage" class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800">{{ permissionMessage }}</div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <ContactRound class="size-5 text-emerald-700" />
                <div>
                    <h2 class="text-lg font-black">Regional Contact Directory</h2>
                    <p class="mt-1 text-sm text-slate-600">Keep country-specific contacts separate from datasheet metadata and distribution availability.</p>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                        <tr>
                            <th class="px-3 py-3 text-left">Country</th>
                            <th class="px-3 py-3 text-left">Type</th>
                            <th class="px-3 py-3 text-left">Contact</th>
                            <th class="px-3 py-3 text-left">Email</th>
                            <th class="px-3 py-3 text-left">Phone</th>
                            <th class="px-3 py-3 text-left">Region</th>
                            <th class="px-3 py-3 text-left">Status</th>
                            <th class="px-3 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="contact in contacts?.data || []" :key="contact.id">
                            <td class="px-3 py-4 font-black">{{ contact.country }}</td>
                            <td class="px-3 py-4 capitalize">{{ contact.contact_type }}</td>
                            <td class="px-3 py-4">
                                <div>{{ contact.contact_name || 'Pending' }}</div>
                                <a v-if="contact.website" :href="contact.website" target="_blank" class="mt-1 block text-xs font-bold text-sky-700">Website</a>
                            </td>
                            <td class="px-3 py-4">{{ contact.email || 'Pending' }}</td>
                            <td class="px-3 py-4">{{ contact.phone || 'Pending' }}</td>
                            <td class="px-3 py-4">{{ contact.region || 'Pending' }}</td>
                            <td class="px-3 py-4 capitalize">{{ contact.status }}</td>
                            <td class="px-3 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <Link :href="contact.edit_href" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold hover:bg-slate-50">Edit</Link>
                                    <button class="rounded-md border border-red-200 px-3 py-2 text-xs font-bold text-red-700 disabled:opacity-40" :disabled="!canManage || deleteForm.processing" type="button" @click="removeContact(contact)">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="(contacts?.data || []).length === 0">
                            <td colspan="8" class="px-3 py-10 text-center text-slate-600">No country contacts have been added yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="contacts?.links?.length" class="mt-4 flex flex-wrap gap-2">
                <Link v-for="link in contacts.links" :key="link.label" :href="link.url || '#'" class="rounded-md border px-3 py-2 text-sm font-bold" :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 text-slate-700'" v-html="link.label" />
            </div>
        </section>
    </ManufacturerAdminShell>
</template>
