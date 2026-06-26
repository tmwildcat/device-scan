<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { GitCompare, ArrowRightLeft } from 'lucide-vue-next';
</script>

<template>
    <Head title="Compare Equipment" />

    <div class="mx-auto max-w-7xl space-y-8 p-6">

        <!-- Header -->

        <div>
            <p class="text-sm font-semibold text-emerald-600">
                LineWatt Library
            </p>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                Compare Equipment
            </h1>

            <p class="mt-3 max-w-3xl text-slate-600 dark:text-slate-400">
                Compare engineering specifications across PV modules, string
                inverters and central inverters using a standardized engineering
                data model.
            </p>
        </div>

        <!-- Comparison Types -->

        <div class="grid gap-6 md:grid-cols-3">

            <button
                class="rounded-2xl border bg-white p-6 text-left shadow-sm transition hover:border-emerald-500 hover:shadow dark:bg-slate-900"
            >
                <GitCompare class="mb-4 h-8 w-8 text-emerald-600" />

                <h2 class="font-semibold">
                    PV Modules
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Compare electrical, mechanical and temperature
                    characteristics across PV modules.
                </p>
            </button>

            <button
                class="rounded-2xl border bg-white p-6 text-left shadow-sm transition hover:border-emerald-500 hover:shadow dark:bg-slate-900"
            >
                <GitCompare class="mb-4 h-8 w-8 text-emerald-600" />

                <h2 class="font-semibold">
                    String Inverters
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Compare MPPT configuration, DC input, AC output,
                    efficiency and protection features.
                </p>
            </button>

            <button
                class="rounded-2xl border bg-white p-6 text-left shadow-sm transition hover:border-emerald-500 hover:shadow dark:bg-slate-900"
            >
                <GitCompare class="mb-4 h-8 w-8 text-emerald-600" />

                <h2 class="font-semibold">
                    Central Inverters
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Compare utility-scale inverter performance and grid
                    integration capabilities.
                </p>
            </button>

        </div>

        <!-- Planned Features -->

        <div class="rounded-2xl border bg-white p-8 shadow-sm dark:bg-slate-900">

            <h2 class="text-xl font-semibold">
                Planned Comparison Features
            </h2>

            <div class="mt-6 grid gap-6 md:grid-cols-2">

                <div>

                    <h3 class="font-semibold">
                        Engineering Comparison
                    </h3>

                    <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li>• Side-by-side specification tables</li>
                        <li>• Highlight specification differences</li>
                        <li>• Compare multiple manufacturers</li>
                        <li>• Engineering parameter filtering</li>
                    </ul>

                </div>

                <div>

                    <h3 class="font-semibold">
                        Reports
                    </h3>

                    <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li>• PDF comparison report</li>
                        <li>• Excel export</li>
                        <li>• Share comparison</li>
                        <li>• Save comparison templates</li>
                    </ul>

                </div>

            </div>

        </div>

        <!-- Preview -->

        <div class="rounded-2xl border border-dashed p-10 text-center">

            <ArrowRightLeft class="mx-auto mb-4 h-12 w-12 text-slate-400" />

            <h2 class="text-lg font-semibold">
                Comparison Workspace
            </h2>

            <p class="mt-2 text-slate-500">
                Select two or more equipment models from the LineWatt Library
                and generate a standardized engineering comparison.
            </p>

        </div>

    </div>
</template>