<script setup lang="ts">
defineProps<{
    grade?: string | null;
    score?: number | null;
}>();

const classes: Record<string, string> = {
    A: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    B: 'border-sky-200 bg-sky-50 text-sky-700',
    C: 'border-amber-200 bg-amber-50 text-amber-800',
    D: 'border-orange-200 bg-orange-50 text-orange-800',
    F: 'border-rose-200 bg-rose-50 text-rose-700',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
        :class="classes[grade || ''] || 'border-slate-200 bg-white text-slate-600'"
    >
        Grade {{ grade || 'N/A' }}
        <span v-if="score !== null && score !== undefined" class="ml-1 opacity-75">
            {{ Math.round(score) }}
        </span>
    </span>
</template>
