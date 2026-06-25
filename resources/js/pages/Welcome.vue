<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="LineWatt DeviceScan" />

    <main class="min-h-screen bg-slate-950 text-white">
        <section class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-8">
            <header class="flex items-center justify-between">
                <div class="text-xl font-bold tracking-tight">
                    LineWatt <span class="text-emerald-400">DeviceScan</span>
                </div>

                <nav class="flex items-center gap-3 text-sm">
                    <Link
                        href="/login"
                        class="rounded-lg px-4 py-2 text-slate-300 hover:text-white"
                    >
                        Log in
                    </Link>

                    <Link
                        href="/register"
                        class="rounded-lg bg-emerald-400 px-4 py-2 font-semibold text-slate-950 hover:bg-emerald-300"
                    >
                        Get Started
                    </Link>
                </nav>
            </header>

            <div class="grid flex-1 items-center gap-12 py-20 lg:grid-cols-2">
                <div>
                    <p class="mb-4 inline-flex rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-2 text-sm text-emerald-300">
                        Renewable energy equipment intelligence
                    </p>

                    <h1 class="max-w-4xl text-5xl font-bold tracking-tight sm:text-6xl">
                        Convert equipment datasheets into engineering-ready CSV files.
                    </h1>

                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                        Upload solar module and inverter datasheets, extract key engineering
                        parameters, review the values, and export CSV files for LineWatt,
                        PVSyst, design studies, comparison, and procurement workflows.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-4">
                        <Link
                            href="/register"
                            class="rounded-xl bg-emerald-400 px-6 py-3 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-400/20 hover:bg-emerald-300"
                        >
                            Start Scanning
                        </Link>

                        <a
                            href="#features"
                            class="rounded-xl border border-white/15 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10"
                        >
                            See Features
                        </a>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl">
                    <div class="rounded-2xl bg-slate-900 p-5">
                        <div class="mb-4 text-sm font-semibold text-slate-300">
                            Datasheet → Structured Data
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-xl border border-white/10 bg-slate-800 p-4">
                                <div class="text-xs uppercase text-slate-400">Input</div>
                                <div class="mt-1 font-semibold">Solar Module Datasheet.pdf</div>
                            </div>

                            <div class="text-center text-emerald-300">↓ Scan & Extract</div>

                            <div class="rounded-xl border border-emerald-400/30 bg-emerald-400/10 p-4">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <div class="text-slate-400">Pmax</div>
                                        <div class="font-semibold">550 W</div>
                                    </div>
                                    <div>
                                        <div class="text-slate-400">Voc</div>
                                        <div class="font-semibold">49.5 V</div>
                                    </div>
                                    <div>
                                        <div class="text-slate-400">Vmp</div>
                                        <div class="font-semibold">41.2 V</div>
                                    </div>
                                    <div>
                                        <div class="text-slate-400">Efficiency</div>
                                        <div class="font-semibold">21.3%</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center text-emerald-300">↓ Export</div>

                            <div class="rounded-xl border border-white/10 bg-slate-800 p-4 font-semibold">
                                LineWatt / PVSyst CSV
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="border-t border-white/10 bg-slate-900/80 px-6 py-20">
            <div class="mx-auto grid max-w-7xl gap-6 md:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h3 class="text-lg font-bold">Module Datasheets</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Extract power, voltage, current, efficiency, temperature coefficients,
                        dimensions, warranty, and compliance fields.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h3 class="text-lg font-bold">Inverter Datasheets</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Extract AC power, MPPT ranges, DC voltage, currents, protection,
                        communication, and grid connection parameters.
                    </p>
                </div>

                <div class="rounded-2xl border border-white/10 bg-white/5 p-6">
                    <h3 class="text-lg font-bold">Engineering CSV</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Export reviewed data in LineWatt-compatible and PVSyst-style CSV
                        formats for design and procurement workflows.
                    </p>
                </div>
            </div>
        </section>
    </main>
</template>