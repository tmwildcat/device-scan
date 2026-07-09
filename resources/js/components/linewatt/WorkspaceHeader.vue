<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    eyebrow: string;
    title: string;
    description: string;
    workspaceName: string;
    tenantOrPartner?: string | null;
}>();

const page = usePage();
const user = computed(() => (page.props as any).auth?.user ?? null);
const roleLabel = computed(() => user.value?.role_label || user.value?.role || '');
const subscriptionStatus = computed(() => String(user.value?.subscription_status || '').toLowerCase());
const badgeLabel = computed(() => {
    if (! roleLabel.value) {
        return '';
    }

    if (subscriptionStatus.value && ! ['active', 'manual access'].includes(subscriptionStatus.value)) {
        return `${roleLabel.value} · ${subscriptionStatus.value.replaceAll('_', ' ')}`;
    }

    return roleLabel.value;
});
const badgeTone = computed(() => {
    if (['inactive', 'expired', 'cancelled', 'canceled', 'past_due', 'unpaid'].includes(subscriptionStatus.value)) {
        return 'border-rose-200 bg-rose-50 text-rose-800';
    }

    if (roleLabel.value.toLowerCase() === 'subscriber') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    }

    return 'border-slate-200 bg-slate-50 text-slate-700';
});
</script>

<template>
    <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">
                    {{ eyebrow }}
                </p>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">
                    {{ title }}
                </h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    {{ description }}
                </p>
            </div>
            <span
                v-if="badgeLabel"
                class="shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-bold"
                :class="badgeTone"
            >
                {{ badgeLabel }}
            </span>
        </div>
    </section>
</template>
