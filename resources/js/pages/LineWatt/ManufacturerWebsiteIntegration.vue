<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    company: {
        name: string;
        plan_code: string;
        plan_label: string;
        can_upgrade: boolean;
        upgrade_message: string | null;
    };
}>();

const plans = [
    { name: 'Pro', options: ['Datasheet management', 'Review submissions', 'Supporting documents', 'Basic insights placeholders', 'Promotions placeholders where enabled'] },
    { name: 'Enterprise', options: ['Full manufacturer engineering page', 'Website embed components', 'Datasheet designer', 'Advanced content distribution', 'Engineering APIs and multilingual datasheet workflow placeholders'] },
];
</script>

<template>
    <Head title="Website Integration" />
    <ManufacturerAdminShell
        :company="company"
        title="Website Integration"
        subtitle="Website integration is planned for v1.1. This page prepares the roadmap without shipping widget JavaScript or public integration endpoints yet."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Website Integration' }]"
    >

            <section class="grid gap-6 lg:grid-cols-3">
                <div v-for="plan in plans" :key="plan.name" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-black">{{ plan.name }}</h2>
                        <span v-if="company.plan_label === plan.name" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-800">Current</span>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm font-bold text-slate-700">
                        <li v-for="option in plan.options" :key="option">✓ {{ option }}</li>
                    </ul>
                </div>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_420px]">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Powered by LineWatt Library</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Future website components will expose official datasheet pages, model family views, supporting documents and contact widgets. Search ranking and validation remain independent of integrations.</p>
                    <div class="mt-4 rounded-md border border-dashed border-slate-300 bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Integration status</div>
                        <div class="mt-2 text-xl font-black">Planned for v1.1</div>
                    </div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black">Generated Embed Code</h2>
                    <textarea class="mt-4 min-h-36 w-full rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-500" disabled value="<!-- Website integration placeholder. Widget JavaScript is not implemented yet. -->" />
                    <button class="mt-3 w-full cursor-not-allowed rounded-md bg-slate-200 px-4 py-2 text-sm font-black text-slate-500" disabled>Generate embed code</button>
                    <Link v-if="company.can_upgrade" href="/admin/manufacturer/upgrade" class="mt-4 inline-flex text-sm font-black text-violet-700">Request upgrade →</Link>
                    <p v-else-if="company.upgrade_message" class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">{{ company.upgrade_message }}</p>
                </div>
            </section>
    </ManufacturerAdminShell>
</template>
