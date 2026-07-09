<script setup lang="ts">
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    roleLabel?: string | null;
    company: any;
}>();

const tabs = ['Overview', 'Datasheets', 'Structured Engineering Data', 'Users', 'Submissions', 'Promotions', 'Company Profile', 'Factory Locations', 'Distribution', 'Activity', 'Admin Actions'];
const activeTab = ref('Overview');

const initials = computed(() => String(props.company.name || 'OEM').split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase());
</script>

<template>
    <Head :title="`${company.name} · OEM`" />

    <LibraryAdminShell
        :title="company.name"
        subtitle="OEM account, submissions, users, company data, and library governance controls."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Equipment Manufacturers' },
            { label: 'Subscribers', href: '/admin/library/oem-subscribers' },
            { label: company.name },
        ]"
    >
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex size-16 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-sky-500 text-xl font-black text-white">
                        {{ initials }}
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">OEM Account</p>
                        <h2 class="text-2xl font-black text-slate-950">{{ company.name }}</h2>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-black text-sky-800">{{ company.plan }}</span>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-black text-slate-700 capitalize">{{ company.status || 'active' }}</span>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-lg bg-slate-50 px-4 py-3">
                        <div class="text-xl font-black">{{ company.datasheets }}</div>
                        <div class="text-xs font-bold text-slate-500">Datasheets</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-4 py-3">
                        <div class="text-xl font-black">{{ company.records }}</div>
                        <div class="text-xs font-bold text-slate-500">Data Sets</div>
                    </div>
                    <div class="rounded-lg bg-amber-50 px-4 py-3">
                        <div class="text-xl font-black text-amber-800">{{ company.submissions_count }}</div>
                        <div class="text-xs font-bold text-amber-800">Submissions</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-6 overflow-x-auto">
            <div class="flex gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab"
                    type="button"
                    class="shrink-0 rounded-md border px-3 py-2 text-sm font-black"
                    :class="activeTab === tab ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                    @click="activeTab = tab"
                >
                    {{ tab }}
                </button>
            </div>
        </div>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div v-if="activeTab === 'Overview'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Factory Locations</div>
                    <div class="mt-3 text-2xl font-black">{{ company.factory_count }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Distribution Countries</div>
                    <div class="mt-3 text-2xl font-black">{{ company.distribution_count }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Supporting Documents</div>
                    <div class="mt-3 text-2xl font-black">{{ company.supporting_documents_count }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Last Activity</div>
                    <div class="mt-3 text-lg font-black">{{ company.last_activity || 'Pending' }}</div>
                </div>
            </div>

            <div v-else-if="activeTab === 'Datasheets'">
                <h3 class="font-black">Datasheets</h3>
                <div v-if="company.datasheets?.length" class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                            <tr><th class="px-4 py-3">Filename</th><th class="px-4 py-3">Product</th><th class="px-4 py-3">Device</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Uploaded</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="datasheet in company.datasheets" :key="datasheet.uuid || datasheet.id">
                                <td class="px-4 py-3 font-black">{{ datasheet.filename || 'Datasheet' }}</td>
                                <td class="px-4 py-3">{{ datasheet.product_name || 'Pending' }}</td>
                                <td class="px-4 py-3 capitalize">{{ datasheet.device_type }}</td>
                                <td class="px-4 py-3"><LifecycleStatusBadge :status="datasheet.status" /></td>
                                <td class="px-4 py-3">{{ datasheet.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="mt-4 text-sm text-slate-600">No datasheets found for this OEM.</p>
            </div>

            <div v-else-if="activeTab === 'Structured Engineering Data' || activeTab === 'Submissions'">
                <h3 class="font-black">{{ activeTab }}</h3>
                <div v-if="company.records?.length" class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                            <tr><th class="px-4 py-3">Data Set</th><th class="px-4 py-3">Device</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Validation</th><th class="px-4 py-3 text-right">Action</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="record in company.records" :key="record.uuid || record.id">
                                <td class="px-4 py-3 font-black">{{ record.display_name || record.model_name || record.model_series || 'Engineering Data Set' }}</td>
                                <td class="px-4 py-3 capitalize">{{ record.device_type }}</td>
                                <td class="px-4 py-3"><LifecycleStatusBadge :status="record.status" /></td>
                                <td class="px-4 py-3"><ValidationStatusBadge :status="record.validation_status" /></td>
                                <td class="px-4 py-3 text-right"><Link :href="record.review_href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">Open</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="mt-4 text-sm text-slate-600">No structured engineering data found for this OEM.</p>
            </div>

            <div v-else-if="activeTab === 'Users'">
                <h3 class="font-black">Users</h3>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <article v-for="user in company.users" :key="user.id" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="font-black">{{ user.name }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ user.email }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-600">{{ user.role }}</span>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-600">{{ user.status }}</span>
                        </div>
                    </article>
                </div>
            </div>

            <div v-else-if="activeTab === 'Admin Actions'" class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <button class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-left font-black text-slate-500" disabled>Approve / Suspend OEM</button>
                <button class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-left font-black text-slate-500" disabled>Block Content</button>
                <button class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-left font-black text-slate-500" disabled>View as OEM</button>
                <button class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-left font-black text-slate-500" disabled>Manage Users</button>
                <button class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-left font-black text-slate-500" disabled>Manage Subscription</button>
                <button class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-left font-black text-slate-500" disabled>Request Corrections</button>
            </div>

            <div v-else class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6">
                <h3 class="font-black">{{ activeTab }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Operational summary placeholder for {{ activeTab.toLowerCase() }}. This tab will use OEM-specific data and activity as those workflows deepen.
                </p>
            </div>
        </section>
    </LibraryAdminShell>
</template>
