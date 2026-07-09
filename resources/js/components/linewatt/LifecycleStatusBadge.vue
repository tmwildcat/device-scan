<script setup lang="ts">
defineProps<{
    status?: string | null;
}>();

const labels: Record<string, string> = {
    uploaded: 'Uploaded',
    compiled: 'Compiled',
    review_required: 'Review Required',
    approved: 'Approved',
    published: 'Published',
    rejected: 'Rejected',
    discontinued: 'Discontinued',
    replaced: 'Replaced',
};

const classes: Record<string, string> = {
    uploaded: 'border-slate-200 bg-slate-50 text-slate-700',
    compiled: 'border-sky-200 bg-sky-50 text-sky-700',
    review_required: 'border-amber-200 bg-amber-50 text-amber-800',
    approved: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    published: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    rejected: 'border-rose-200 bg-rose-50 text-rose-700',
    discontinued: 'border-zinc-200 bg-zinc-50 text-zinc-700',
    replaced: 'border-violet-200 bg-violet-50 text-violet-700',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
        :class="classes[status || ''] || 'border-slate-200 bg-slate-50 text-slate-700'"
    >
        {{ labels[status || ''] || 'Draft' }}
    </span>
</template>
