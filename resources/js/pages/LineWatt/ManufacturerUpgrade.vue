<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import PlaceholderAction from '@/components/linewatt/PlaceholderAction.vue';
import { Head } from '@inertiajs/vue3';
import { BadgeCheck, Megaphone, PanelsTopLeft } from 'lucide-vue-next';

defineProps<{
    company: {
        name: string;
        plan_label: string;
        manufacturer_role?: string | null;
        role_label: string;
        can_request_upgrade: boolean;
    };
}>();
</script>

<template>
    <Head title="Upgrade Manufacturer Plan" />

    <ManufacturerAdminShell
        :company="{ ...company, manufacturer_role_label: company.role_label, upgrade_message: company.can_request_upgrade ? null : 'Please contact your Manufacturer Administrator to upgrade your subscription.' }"
        :title="`Upgrade ${company.name}`"
        subtitle="Enterprise request placeholder. Paddle checkout and billing are not implemented yet."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Subscription' }]"
    >
            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="mt-2 text-slate-600">
                    Current plan: <span class="font-black text-slate-950">{{ company.plan_label }}</span>.
                    Manufacturer Pro is the v1 self-serve plan. Enterprise is handled as contact-sales until pricing is connected.
                </p>

                <div v-if="!company.can_request_upgrade" class="mt-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-800">
                    Please contact your Manufacturer Administrator to upgrade your subscription.
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-sky-200 bg-sky-50 p-5">
                        <Megaphone class="size-6 text-sky-700" />
                        <h2 class="mt-4 font-black text-sky-950">Manufacturer Pro</h2>
                        <p class="mt-2 text-sm leading-6 text-sky-800">
                            Datasheet management, review submissions, supporting documents, basic insights and promotion placeholders.
                        </p>
                        <span class="mt-4 inline-flex rounded-full bg-white px-3 py-1 text-xs font-black text-sky-800">Self-serve plan</span>
                    </div>
                    <div class="rounded-lg border border-violet-200 bg-violet-50 p-5">
                        <PanelsTopLeft class="size-6 text-violet-700" />
                        <h2 class="mt-4 font-black text-violet-950">Manufacturer Enterprise</h2>
                        <p class="mt-2 text-sm leading-6 text-violet-800">
                            Website integration, datasheet designer, advanced content distribution, APIs and multilingual datasheet workflows.
                        </p>
                        <PlaceholderAction class="mt-4" label="Request Enterprise" />
                    </div>
                </div>

                <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-5">
                    <BadgeCheck class="size-5 text-emerald-700" />
                    <h2 class="mt-3 font-black">Billing Placeholder</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Paddle checkout will connect here later. Manufacturer contracts can also be managed manually by Platform Admin.
                    </p>
                </div>
            </section>
    </ManufacturerAdminShell>
</template>
