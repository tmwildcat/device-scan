<script setup lang="ts">
defineProps<{
    status?: string | null;
}>();

const labels: Record<string, string> = {
    not_required: 'Review Not Required',
    pending_review: 'Pending Review',
    reviewed: 'Reviewed',
    flagged: 'Flagged',
};

const classes: Record<string, string> = {
    not_required: 'border-slate-200 bg-white text-slate-600',
    pending_review: 'border-amber-200 bg-amber-50 text-amber-800',
    reviewed: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    flagged: 'border-rose-200 bg-rose-50 text-rose-700',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
        :class="classes[status || ''] || 'border-slate-200 bg-white text-slate-600'"
    >
        {{ labels[status || ''] || 'Review Pending' }}
    </span>
</template>
