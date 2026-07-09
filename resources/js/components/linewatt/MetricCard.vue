<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: string | number;
    icon?: Component;
    tone?: 'emerald' | 'sky' | 'amber' | 'rose' | 'violet' | 'slate';
}>();

const tones = {
    emerald: 'text-emerald-700',
    sky: 'text-sky-700',
    amber: 'text-amber-700',
    rose: 'text-rose-700',
    violet: 'text-violet-700',
    slate: 'text-slate-700',
};
</script>

<template>
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <component :is="icon" v-if="icon" class="size-5" :class="tones[tone || 'slate']" />
        <div class="mt-4 text-3xl font-black text-slate-950">{{ value }}</div>
        <div class="mt-1 text-sm font-semibold text-slate-600">{{ label }}</div>
    </div>
</template>
