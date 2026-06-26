<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="LineWatt Library" />

    <div class="space-y-6">

        <div>
            <h1 class="text-3xl font-bold">
                LineWatt Library
            </h1>

            <p class="mt-2 text-slate-500">
                Engineering equipment library for LineWatt Studio.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border p-6">
                <div class="text-sm text-slate-500">PV Modules</div>
                <div class="mt-2 text-3xl font-bold">0</div>
            </div>

            <div class="rounded-xl border p-6">
                <div class="text-sm text-slate-500">String Inverters</div>
                <div class="mt-2 text-3xl font-bold">0</div>
            </div>

            <div class="rounded-xl border p-6">
                <div class="text-sm text-slate-500">Central Inverters</div>
                <div class="mt-2 text-3xl font-bold">0</div>
            </div>

            <div class="rounded-xl border p-6">
                <div class="text-sm text-slate-500">Pending Imports</div>
                <div class="mt-2 text-3xl font-bold">0</div>
            </div>

        </div>

    </div>
</template>