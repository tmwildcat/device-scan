<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import MetricCard from '@/components/linewatt/MetricCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BadgeCheck, ClipboardCheck, DatabaseZap, Factory, Megaphone, Search, Tags, Users } from 'lucide-vue-next';

defineProps<{
    roleLabel?: string | null;
    summary: {
        published_records: number;
        pending_approval: number;
        datasheets: number;
        manufacturers: number;
        members: number;
        promotions: number;
    };
    placeholder?: {
        title: string;
        description: string;
    } | null;
}>();

const businessActions = [
    { label: 'Review & Approval', href: '/admin/library/approval-queue?view=pending_approval', icon: ClipboardCheck },
    { label: 'Datasheets', href: '/admin/library/datasheets?view=new_uploads', icon: DatabaseZap },
    { label: 'Engineering Data', href: '/admin/library/engineering-data?view=pending_approval', icon: BadgeCheck },
    { label: 'Manufacturers', href: '/admin/library/manufacturers', icon: Factory },
    { label: 'Promotions', href: '/admin/library/promotions', icon: Megaphone },
    { label: 'Champions', href: '/admin/library/champions', icon: Users },
    { label: 'Power Search', href: '/admin/library/power-search', icon: Tags },
    { label: 'Discovery', href: '/admin/business/discovery', icon: Search },
];
</script>

<template>
    <Head title="Business Administration" />

    <LibraryAdminShell
        title="Business Administration"
        subtitle="Business operations for library growth, OEM subscriber operations, promotions, champions and library oversight."
        :role-label="roleLabel"
        :primary-action="{ label: 'Review Queue', href: '/admin/library/approval-queue?view=pending_approval' }"
        :secondary-actions="[{ label: 'Search', href: '/search' }]"
    >
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <Link href="/admin/library/engineering-data?view=published" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="BadgeCheck" label="Published Records" :value="summary.published_records" tone="emerald" />
            </Link>
            <Link href="/admin/library/approval-queue?view=pending_approval" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="ClipboardCheck" label="Pending Approval" :value="summary.pending_approval" tone="amber" />
            </Link>
            <Link href="/admin/library/datasheets?view=new_uploads" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="DatabaseZap" label="Datasheets" :value="summary.datasheets" tone="sky" />
            </Link>
            <Link href="/admin/library/manufacturers" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="Factory" label="Manufacturers" :value="summary.manufacturers" tone="violet" />
            </Link>
            <Link href="/admin/library/members?view=subscribers" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="Users" label="Members" :value="summary.members" tone="sky" />
            </Link>
            <Link href="/admin/library/promotions" class="block transition hover:-translate-y-0.5 hover:shadow-md">
                <MetricCard :icon="Megaphone" label="Promotions" :value="summary.promotions" tone="amber" />
            </Link>
        </section>

        <section v-if="placeholder" class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Business Workspace</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950">{{ placeholder.title }}</h2>
            <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">{{ placeholder.description }}</p>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Business Actions</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Operate and Grow the Library</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">
                    Admin users can oversee library work and commercial growth. Low-level platform operations remain in Super Admin.
                </p>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <Link
                    v-for="action in businessActions"
                    :key="action.href"
                    :href="action.href"
                    class="rounded-lg border border-slate-200 p-4 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:bg-emerald-50"
                >
                    <component :is="action.icon" class="size-5 text-emerald-700" />
                    <div class="mt-3 font-black text-slate-950">{{ action.label }}</div>
                </Link>
            </div>
        </section>
    </LibraryAdminShell>
</template>
