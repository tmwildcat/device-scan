<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    roleLabel?: string | null;
    view?: string | null;
    title?: string | null;
    subtitle?: string | null;
    members: {
        data: Array<any>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();

const tabSets: Record<string, Array<{ label: string; href: string; active: string }>> = {
    subscribers: [
        { label: 'Active Subscribers', href: '/admin/library/members?view=subscribers', active: 'subscribers' },
        { label: 'Suspended', href: '/admin/library/members?view=suspended', active: 'suspended' },
        { label: 'Usage / Exports', href: '/admin/library/members?view=usage_exports', active: 'usage_exports' },
    ],
    registered: [
        { label: 'Registered Users', href: '/admin/library/members?view=registered', active: 'registered' },
    ],
    support: [
        { label: 'Support', href: '/admin/library/members?view=support_access', active: 'support_access' },
    ],
    all: [
        { label: 'All', href: '/admin/library/members?view=all', active: 'all' },
        { label: 'Active Subscribers', href: '/admin/library/members?view=subscribers', active: 'subscribers' },
        { label: 'Registered Users', href: '/admin/library/members?view=registered', active: 'registered' },
        { label: 'Suspended', href: '/admin/library/members?view=suspended', active: 'suspended' },
        { label: 'Usage / Exports', href: '/admin/library/members?view=usage_exports', active: 'usage_exports' },
    ],
};

function tabsForView() {
    if (props.view === 'subscribers' || props.view === 'suspended' || props.view === 'usage_exports') {
        return tabSets.subscribers;
    }

    if (props.view === 'registered') {
        return tabSets.registered;
    }

    if (props.view === 'support_access') {
        return tabSets.support;
    }

    return tabSets.all;
}
</script>

<template>
    <Head :title="title || 'Members'" />

    <LibraryAdminShell
        :title="title || 'Members'"
        :subtitle="subtitle || 'Subscriber and registered member support, entitlement visibility, private upload metadata, and account operations.'"
        :role-label="roleLabel"
    >
        <div class="mb-5 flex flex-wrap gap-2">
            <Link
                v-for="tab in tabsForView()"
                :key="tab.active"
                :href="tab.href"
                class="rounded-md border px-3 py-2 text-sm font-bold"
                :class="view === tab.active || (!view && tab.active === 'all') ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700'"
            >
                {{ tab.label }}
            </Link>
        </div>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div v-if="members.data.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr><th class="px-4 py-3">Member</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">Plan</th><th class="px-4 py-3">Subscription</th><th class="px-4 py-3">Joined</th><th class="px-4 py-3">Last Activity</th><th class="px-4 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="member in members.data" :key="member.id">
                            <td class="px-4 py-4"><div class="font-black">{{ member.name }}</div><div class="mt-1 text-xs text-slate-500">{{ member.email }}</div></td>
                            <td class="px-4 py-4">{{ member.role_label }}</td>
                            <td class="px-4 py-4">{{ member.plan_code || 'None' }}</td>
                            <td class="px-4 py-4 capitalize">{{ member.subscription_status || 'registered' }}</td>
                            <td class="px-4 py-4">{{ member.created_at }}</td>
                            <td class="px-4 py-4">{{ member.last_activity }}</td>
                            <td class="px-4 py-4 text-right"><Link :href="member.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white">Open</Link></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-10 text-center">
                <h2 class="font-black">No members found</h2>
                <p class="mt-2 text-sm text-slate-600">Try a different member filter.</p>
            </div>
        </section>
    </LibraryAdminShell>
</template>
