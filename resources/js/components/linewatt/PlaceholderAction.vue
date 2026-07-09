<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        label: string;
        icon?: Component;
        variant?: 'dark' | 'light' | 'outline';
    }>(),
    {
        variant: 'outline',
    },
);
</script>

<template>
    <button
        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-bold transition"
        :class="{
            'bg-slate-950 text-white opacity-70': variant === 'dark',
            'border border-slate-200 bg-white text-slate-400': variant === 'outline',
            'bg-slate-100 text-slate-400': variant === 'light',
        }"
        disabled
        title="Coming in the next milestone."
        type="button"
    >
        <component :is="icon" v-if="icon" class="size-4" />
        {{ label }}
    </button>
</template>
