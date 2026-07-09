<script setup lang="ts">
import { Activity, Sparkles } from 'lucide-vue-next';
</script>

<template>
    <aside
        aria-label="LineWatt promotion"
        class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
    >
        <div class="flex items-center gap-3 p-3">
            <div class="relative flex size-9 shrink-0 items-center justify-center rounded-md bg-slate-950 text-emerald-300">
                <Activity class="size-4" />
                <span class="absolute inset-0 rounded-md border border-emerald-300/40 motion-safe:animate-ping" />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                    <Sparkles class="size-3" />
                    LineWatt
                </div>
                <p class="truncate text-sm font-semibold text-slate-900">
                    Trusted Engineering Records for renewable energy teams
                </p>
            </div>
        </div>
    </aside>
</template>
