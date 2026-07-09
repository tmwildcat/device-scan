<script setup lang="ts">
import PlatformAdminShell from '@/components/linewatt/admin/PlatformAdminShell.vue';
import MetricCard from '@/components/linewatt/MetricCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Activity,
    AlertTriangle,
    Bell,
    CheckCircle2,
    Database,
    HardDrive,
    KeyRound,
    ListChecks,
    LockKeyhole,
    ServerCog,
    ShieldCheck,
    TerminalSquare,
    Users,
} from 'lucide-vue-next';

type Card = { label: string; value: string | number; tone?: string };
type Panel = { title: string; body: string };
type Table = { columns: string[]; rows: Array<Array<string | number | null>> };
type Check = { label: string; ok: boolean; note?: string | null };

const props = defineProps<{
    workspace: {
        name: string;
        role?: string | null;
        role_label?: string | null;
        environment?: string | null;
        health?: string | null;
    };
    section: {
        key: string;
        title: string;
        subtitle?: string | null;
        cards?: Card[];
        rows?: Array<{ label: string; value: string | number | null }>;
        panels?: Panel[];
        table?: Table | null;
        checks?: Check[];
    };
    summary: {
        users: number;
        subscribers: number;
        manufacturers: number;
        platform_roles: number;
        datasheets?: number;
        engineering_records?: number;
        failed_jobs?: number;
        notification_failures?: number;
    };
}>();

const iconFor = (label: string) => {
    const normalized = label.toLowerCase();
    if (normalized.includes('security') || normalized.includes('alert')) return ShieldCheck;
    if (normalized.includes('storage') || normalized.includes('disk')) return HardDrive;
    if (normalized.includes('queue') || normalized.includes('job')) return ServerCog;
    if (normalized.includes('notification') || normalized.includes('email')) return Bell;
    if (normalized.includes('database') || normalized.includes('data')) return Database;
    if (normalized.includes('key') || normalized.includes('api')) return KeyRound;
    if (normalized.includes('user') || normalized.includes('role')) return Users;
    if (normalized.includes('debug') || normalized.includes('developer')) return TerminalSquare;

    return Activity;
};

const toneFor = (tone?: string) => {
    if (tone === 'amber') return 'border-amber-200 bg-amber-50 text-amber-900';
    if (tone === 'sky') return 'border-sky-200 bg-sky-50 text-sky-900';
    if (tone === 'violet') return 'border-violet-200 bg-violet-50 text-violet-900';

    return 'border-emerald-200 bg-emerald-50 text-emerald-900';
};

const dashboardSummary = [
    { label: 'Users', value: props.summary.users, icon: Users, tone: 'sky' },
    { label: 'Platform Roles', value: props.summary.platform_roles, icon: ShieldCheck, tone: 'amber' },
    { label: 'Datasheets', value: props.summary.datasheets ?? 0, icon: Database, tone: 'emerald' },
    { label: 'Engineering Records', value: props.summary.engineering_records ?? 0, icon: ListChecks, tone: 'violet' },
];
</script>

<template>
    <Head :title="section.title" />

    <PlatformAdminShell
        :title="section.title"
        :subtitle="section.subtitle"
        :role-label="workspace.role_label"
        :environment="workspace.environment"
        :health="workspace.health"
        :breadcrumbs="[
            { label: 'Platform', href: '/admin/platform' },
            { label: section.title },
        ]"
        :primary-action="section.key === 'developer-tools' ? { label: 'Route List', href: '/admin/platform/developer-tools' } : null"
    >
        <section v-if="section.key === 'dashboard'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <MetricCard
                v-for="card in dashboardSummary"
                :key="card.label"
                :icon="card.icon"
                :label="card.label"
                :value="card.value"
                :tone="card.tone"
            />
        </section>

        <section v-if="section.cards?.length" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4" :class="{ 'mt-0': section.key !== 'dashboard' }">
            <article
                v-for="card in section.cards"
                :key="card.label"
                class="rounded-lg border bg-white p-5 shadow-sm"
                :class="toneFor(card.tone)"
            >
                <component :is="iconFor(card.label)" class="size-5" />
                <div class="mt-4 text-xs font-black uppercase tracking-[0.14em] opacity-70">{{ card.label }}</div>
                <div class="mt-2 break-words text-2xl font-black">{{ card.value }}</div>
            </article>
        </section>

        <section v-if="section.rows?.length" class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-xl font-black text-slate-950">Status Details</h2>
            </div>
            <dl class="divide-y divide-slate-100">
                <div v-for="row in section.rows" :key="row.label" class="grid gap-3 px-5 py-4 md:grid-cols-[260px_1fr]">
                    <dt class="text-sm font-black text-slate-500">{{ row.label }}</dt>
                    <dd class="break-words text-sm font-semibold text-slate-900">{{ row.value ?? '—' }}</dd>
                </div>
            </dl>
        </section>

        <section v-if="section.checks?.length" class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black text-slate-950">Security Checklist</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                <article v-for="check in section.checks" :key="check.label" class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-start gap-3">
                        <CheckCircle2 v-if="check.ok" class="mt-0.5 size-5 text-emerald-700" />
                        <AlertTriangle v-else class="mt-0.5 size-5 text-amber-700" />
                        <div>
                            <div class="font-black text-slate-950">{{ check.label }}</div>
                            <p v-if="check.note" class="mt-1 text-sm leading-6 text-slate-600">{{ check.note }}</p>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section v-if="section.table" class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-xl font-black text-slate-950">Recent Items</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.14em] text-slate-400">
                        <tr>
                            <th v-for="column in section.table.columns" :key="column" class="px-5 py-3">{{ column }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(row, index) in section.table.rows" :key="index">
                            <td v-for="(value, valueIndex) in row" :key="valueIndex" class="px-5 py-4 font-semibold text-slate-700">
                                {{ value ?? '—' }}
                            </td>
                        </tr>
                        <tr v-if="!section.table.rows.length">
                            <td :colspan="section.table.columns.length" class="px-5 py-12 text-center text-slate-500">
                                No rows available.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="section.panels?.length" class="mt-6 grid gap-4 md:grid-cols-2">
            <article v-for="panel in section.panels" :key="panel.title" class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-950">{{ panel.title }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ panel.body }}</p>
            </article>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-slate-950 p-5 text-white shadow-sm">
            <div class="text-xs font-black uppercase tracking-[0.16em] text-emerald-300">Platform Boundary</div>
            <p class="mt-2 text-sm leading-6 text-slate-200">
                This Super Admin console is for infrastructure, health, security and system controls. Business, library, manufacturer and subscriber workflows remain outside this workspace.
            </p>
            <Link href="/admin/platform" class="mt-4 inline-flex rounded-md bg-white px-4 py-2 text-sm font-black text-slate-950">Back to Platform Dashboard</Link>
        </section>
    </PlatformAdminShell>
</template>
