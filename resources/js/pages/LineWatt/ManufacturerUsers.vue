<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { KeyRound, ShieldAlert, UserPlus, Users } from 'lucide-vue-next';

const props = defineProps<{
    company: {
        name: string;
        plan_label: string;
        max_users?: number | null;
        user_count: number;
        can_manage_users: boolean;
        permission_message?: string | null;
    };
    users: Array<{
        id: number;
        name: string;
        email: string;
        role?: string | null;
        role_label: string;
        status: string;
        last_updated?: string | null;
    }>;
}>();

const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>();
const form = useForm({ email: '', role: 'manufacturer_user' });

function invite(): void {
    form.post('/admin/manufacturer/users/invite', { preserveScroll: true });
}
</script>

<template>
    <Head title="Manufacturer Users" />

    <ManufacturerAdminShell
        :company="company"
        title="Users"
        subtitle="Manage Manufacturer Admin and Manufacturer User access for this OEM account."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Users' }]"
    >
            <div class="mb-5 flex flex-wrap gap-2 text-xs font-bold">
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-800">{{ company.plan_label }}</span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ company.user_count }} / {{ company.max_users || '∞' }} users</span>
            </div>

            <div v-if="page.props.flash?.success" class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ page.props.flash.success }}</div>
            <div v-if="page.props.flash?.error" class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">{{ page.props.flash.error }}</div>

            <section class="mt-6 grid gap-6 lg:grid-cols-[1fr_340px]">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <Users class="size-5 text-emerald-700" />
                        <h2 class="text-lg font-black">Team</h2>
                    </div>
                    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                                <tr>
                                    <th class="px-3 py-3 text-left">User</th>
                                    <th class="px-3 py-3 text-left">Role</th>
                                    <th class="px-3 py-3 text-left">Status</th>
                                    <th class="px-3 py-3 text-left">Updated</th>
                                    <th class="px-3 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="member in users" :key="member.id">
                                    <td class="px-3 py-4">
                                        <div class="font-black">{{ member.name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ member.email }}</div>
                                    </td>
                                    <td class="px-3 py-4">{{ member.role_label }}</td>
                                    <td class="px-3 py-4 capitalize">{{ member.status }}</td>
                                    <td class="px-3 py-4">{{ member.last_updated || 'Pending' }}</td>
                                    <td class="px-3 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 disabled:opacity-45" :disabled="!company.can_manage_users" type="button">Suspend</button>
                                            <button class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 disabled:opacity-45" :disabled="!company.can_manage_users" type="button">Disable</button>
                                            <button class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 disabled:opacity-45" :disabled="!company.can_manage_users" type="button">Remove</button>
                                            <button class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 disabled:opacity-45" :disabled="!company.can_manage_users" type="button">Reset Password</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <UserPlus class="size-5 text-sky-700" />
                            <h2 class="font-black">Invite User</h2>
                        </div>
                        <p v-if="!company.can_manage_users" class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">{{ company.permission_message }}</p>
                        <form class="mt-4 space-y-3" @submit.prevent="invite">
                            <input v-model="form.email" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" :disabled="!company.can_manage_users" placeholder="user@manufacturer.com" type="email" />
                            <select v-model="form.role" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" :disabled="!company.can_manage_users">
                                <option value="manufacturer_user">Manufacturer User</option>
                                <option value="manufacturer_admin">Manufacturer Admin</option>
                            </select>
                            <button class="w-full rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white disabled:opacity-45" :disabled="!company.can_manage_users || form.processing" type="submit">Invite user</button>
                        </form>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <KeyRound class="size-5 text-violet-700" />
                        <h2 class="mt-3 font-black">Password Reset</h2>
                        <p class="mt-2 text-sm text-slate-600">Reset/change password remains an invitation-flow placeholder until email delivery is connected.</p>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <ShieldAlert class="size-5 text-amber-700" />
                        <h2 class="mt-3 font-black">Permissions</h2>
                        <p class="mt-2 text-sm text-slate-600">Manufacturer Users cannot manage subscription, create users, delete users or upgrade plans.</p>
                    </div>
                </aside>
            </section>
    </ManufacturerAdminShell>
</template>
