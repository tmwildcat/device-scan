<script setup lang="ts">
defineProps<{
    status?: string | null;
}>();

const labels: Record<string, string> = {
    clean: 'Clean',
    warnings: 'Warnings',
    errors: 'Errors',
};

const classes: Record<string, string> = {
    clean: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    warnings: 'border-amber-200 bg-amber-50 text-amber-800',
    errors: 'border-rose-200 bg-rose-50 text-rose-700',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
        :class="classes[status || ''] || 'border-slate-200 bg-white text-slate-600'"
    >
        {{ labels[status || ''] || 'Not Validated' }}
    </span>
</template>
