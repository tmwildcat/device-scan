<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BatteryCharging,
    Bot,
    Boxes,
    ChartNoAxesCombined,
    CircuitBoard,
    DatabaseZap,
    FileSearch,
    FileText,
    GitCompare,
    Layers3,
    Library,
    PanelsTopLeft,
    PlugZap,
    SolarPanel,
    Sparkles,
    UploadCloud,
    Workflow,
    Zap,
} from 'lucide-vue-next';
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-full bg-slate-50/60 p-4 dark:bg-slate-950 md:p-6">
        <div class="mx-auto max-w-7xl space-y-8">

            <!-- HERO -->
            <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-sm dark:border-emerald-900/40 dark:bg-slate-900">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-white to-sky-50 dark:from-emerald-950/30 dark:via-slate-900 dark:to-sky-950/20"></div>

                <div class="relative grid gap-8 p-8 lg:grid-cols-[1.3fr_.7fr] lg:p-10">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            <Sparkles class="size-3.5" />
                            LineWatt Library
                        </div>

                        <h1 class="mt-5 max-w-4xl text-4xl font-black tracking-tight text-slate-950 dark:text-white md:text-5xl">
                            Transform Manufacturer Datasheets into Engineering Data
                        </h1>

                        <p class="mt-5 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-300">
                            Renewable energy manufacturers publish equipment specifications as PDF datasheets.
                            LineWatt Library converts those documents into structured engineering data that can be
                            searched, compared and consumed by digital renewable energy applications.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Link
                                href="/library/import/module"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700"
                            >
                                <UploadCloud class="size-5" />
                                Scan PV Module Datasheet
                                <ArrowRight class="size-4" />
                            </Link>

                            <Link
                                href="/library/equipment/modules"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                <Library class="size-5" />
                                Browse Equipment
                            </Link>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                            <span class="rounded-full bg-white/80 px-3 py-1 ring-1 ring-slate-200 dark:bg-slate-950/70 dark:ring-slate-800">Multi-model datasheets</span>
                            <span class="rounded-full bg-white/80 px-3 py-1 ring-1 ring-slate-200 dark:bg-slate-950/70 dark:ring-slate-800">Engineering matrix extraction</span>
                            <span class="rounded-full bg-white/80 px-3 py-1 ring-1 ring-slate-200 dark:bg-slate-950/70 dark:ring-slate-800">Structured equipment library</span>
                            <span class="rounded-full bg-white/80 px-3 py-1 ring-1 ring-slate-200 dark:bg-slate-950/70 dark:ring-slate-800">LineWatt-ready data</span>
                        </div>
                    </div>

                    <div class="grid gap-4">
                        <Link
                            href="/library/import/string-inverter"
                            class="group rounded-2xl border border-slate-200 bg-white/85 p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-950/70"
                        >
                            <div class="flex items-start gap-4">
                                <div class="rounded-2xl bg-sky-50 p-3 text-sky-600 dark:bg-sky-950 dark:text-sky-300">
                                    <PlugZap class="size-6" />
                                </div>

                                <div>
                                    <h2 class="font-bold text-slate-900 dark:text-white">
                                        Scan String Inverter Datasheet
                                    </h2>

                                    <p class="mt-1 text-sm leading-6 text-slate-500">
                                        Extract MPPT, DC input, AC output, efficiency, protection and communication data.
                                    </p>

                                    <div class="mt-3 text-sm font-semibold text-emerald-600">
                                        Start import →
                                    </div>
                                </div>
                            </div>
                        </Link>

                        <Link
                            href="/library/import/central-inverter"
                            class="group rounded-2xl border border-slate-200 bg-white/85 p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-950/70"
                        >
                            <div class="flex items-start gap-4">
                                <div class="rounded-2xl bg-indigo-50 p-3 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300">
                                    <Zap class="size-6" />
                                </div>

                                <div>
                                    <h2 class="font-bold text-slate-900 dark:text-white">
                                        Scan Central Inverter Datasheet
                                    </h2>

                                    <p class="mt-1 text-sm leading-6 text-slate-500">
                                        Capture utility-scale inverter limits, AC ratings and grid support specifications.
                                    </p>

                                    <div class="mt-3 text-sm font-semibold text-emerald-600">
                                        Start import →
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- FLOW + STATS -->
            <section class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-bold text-slate-950 dark:text-white">
                        From PDF Datasheets to Digital Engineering
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        LineWatt Library turns manufacturer PDFs into a canonical equipment record that can power design,
                        SLD, comparison and reporting workflows.
                    </p>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-5 dark:bg-slate-950">
                            <FileText class="size-7 text-emerald-600" />
                            <h3 class="mt-4 font-bold">Manufacturer Datasheet</h3>
                            <p class="mt-2 text-sm text-slate-500">PDF datasheets from any module or inverter manufacturer.</p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-5 dark:bg-slate-950">
                            <FileSearch class="size-7 text-emerald-600" />
                            <h3 class="mt-4 font-bold">Engineering Extraction</h3>
                            <p class="mt-2 text-sm text-slate-500">Detect tables, values, scenarios and specification metadata.</p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-5 dark:bg-slate-950">
                            <DatabaseZap class="size-7 text-emerald-600" />
                            <h3 class="mt-4 font-bold">Structured Library</h3>
                            <p class="mt-2 text-sm text-slate-500">Create reusable digital records for engineering applications.</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <SolarPanel class="size-7 text-emerald-600" />
                        <div class="mt-4 text-sm text-slate-500">PV Modules</div>
                        <div class="mt-1 text-4xl font-black">0</div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <PlugZap class="size-7 text-sky-600" />
                        <div class="mt-4 text-sm text-slate-500">String Inverters</div>
                        <div class="mt-1 text-4xl font-black">0</div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <Boxes class="size-7 text-indigo-600" />
                        <div class="mt-4 text-sm text-slate-500">Central Inverters</div>
                        <div class="mt-1 text-4xl font-black">0</div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <Layers3 class="size-7 text-orange-600" />
                        <div class="mt-4 text-sm text-slate-500">Manufacturers</div>
                        <div class="mt-1 text-4xl font-black">0</div>
                    </div>
                </div>
            </section>

            <!-- ENGINEERING APPLICATIONS -->
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                    <div>
                        <h2 class="text-xl font-bold text-slate-950 dark:text-white">
                            Engineering Applications
                        </h2>

                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Structured equipment data can be used across renewable energy engineering workflows,
                            from design calculations to SLD generation and AI-assisted analysis.
                        </p>
                    </div>

                    <Link
                        href="/library/compare"
                        class="inline-flex items-center gap-2 text-sm font-bold text-emerald-600"
                    >
                        Compare equipment
                        <ArrowRight class="size-4" />
                    </Link>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-100 p-5 dark:border-slate-800">
                        <ChartNoAxesCombined class="size-6 text-emerald-600" />
                        <h3 class="mt-3 font-bold">Solar PV Design</h3>
                        <p class="mt-2 text-sm text-slate-500">Use canonical module and inverter data in PV engineering workflows.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-100 p-5 dark:border-slate-800">
                        <CircuitBoard class="size-6 text-emerald-600" />
                        <h3 class="mt-3 font-bold">PV Array Design</h3>
                        <p class="mt-2 text-sm text-slate-500">Support string sizing, voltage checks and inverter loading calculations.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-100 p-5 dark:border-slate-800">
                        <BatteryCharging class="size-6 text-emerald-600" />
                        <h3 class="mt-3 font-bold">BESS Design</h3>
                        <p class="mt-2 text-sm text-slate-500">Connect equipment knowledge to hybrid solar and storage workflows.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-100 p-5 dark:border-slate-800">
                        <Workflow class="size-6 text-emerald-600" />
                        <h3 class="mt-3 font-bold">SLD Generation</h3>
                        <p class="mt-2 text-sm text-slate-500">Feed equipment symbols, ratings and protection data into SLD tools.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-100 p-5 dark:border-slate-800">
                        <GitCompare class="size-6 text-emerald-600" />
                        <h3 class="mt-3 font-bold">Equipment Comparison</h3>
                        <p class="mt-2 text-sm text-slate-500">Compare modules and inverters using one standardized data model.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-100 p-5 dark:border-slate-800">
                        <PanelsTopLeft class="size-6 text-emerald-600" />
                        <h3 class="mt-3 font-bold">Engineering Reports</h3>
                        <p class="mt-2 text-sm text-slate-500">Generate standardized equipment datasheets and project documents.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-100 p-5 dark:border-slate-800">
                        <Bot class="size-6 text-emerald-600" />
                        <h3 class="mt-3 font-bold">AI Engineering Apps</h3>
                        <p class="mt-2 text-sm text-slate-500">Make manufacturer data usable by AI copilots and automation tools.</p>
                    </div>

                    <div class="rounded-2xl border border-slate-100 p-5 dark:border-slate-800">
                        <Library class="size-6 text-emerald-600" />
                        <h3 class="mt-3 font-bold">Digital Libraries</h3>
                        <p class="mt-2 text-sm text-slate-500">Build a trusted internal source of truth for renewable energy equipment.</p>
                    </div>
                </div>
            </section>

            <!-- RECENT ACTIVITY + STATUS -->
            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-bold">Recent Activity</h2>

                    <div class="mt-6 rounded-2xl border border-dashed border-slate-300 p-8 text-center dark:border-slate-700">
                        <FileText class="mx-auto size-10 text-slate-400" />
                        <h3 class="mt-4 font-bold">No imports yet</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            Scan your first PV module datasheet to begin building the library.
                        </p>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-bold">System Status</h2>

                    <div class="mt-6 space-y-3 text-sm">
                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                            <span>PDF text extraction</span>
                            <span class="font-bold text-emerald-600">Active</span>
                        </div>

                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                            <span>Engineering matrix detection</span>
                            <span class="font-bold text-emerald-600">Active</span>
                        </div>

                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                            <span>PV module import</span>
                            <span class="font-bold text-emerald-600">Active</span>
                        </div>

                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                            <span>String inverter import</span>
                            <span class="font-bold text-slate-500">Planned</span>
                        </div>

                        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                            <span>Export and compare</span>
                            <span class="font-bold text-slate-500">Planned</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ECOSYSTEM FOOTER -->
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 text-white shadow-sm dark:border-slate-800">
                <div class="grid gap-8 p-8 lg:grid-cols-[.9fr_1.1fr] lg:p-10">
                    <div>
                        <p class="text-sm font-bold text-emerald-400">
                            Part of the LineWatt Engineering Ecosystem
                        </p>

                        <h2 class="mt-3 text-3xl font-black">
                            One engineering equipment library. Multiple engineering applications.
                        </h2>

                        <p class="mt-4 text-sm leading-6 text-slate-300">
                            The structured data created in LineWatt Library can power design, SLD,
                            proposal, monitoring and digital twin applications across the renewable energy lifecycle.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <a href="#" class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10 transition hover:bg-white/10">
                            <h3 class="font-bold">LineWatt Studio</h3>
                            <p class="mt-2 text-sm text-slate-300">Solar PV • BESS • Engineering Design</p>
                            <div class="mt-4 text-sm font-bold text-emerald-400">Learn more →</div>
                        </a>

                        <a href="#" class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10 transition hover:bg-white/10">
                            <h3 class="font-bold">LineWatt SLD</h3>
                            <p class="mt-2 text-sm text-slate-300">Automatic Single Line Diagrams</p>
                            <div class="mt-4 text-sm font-bold text-emerald-400">Learn more →</div>
                        </a>

                        <a href="#" class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10 transition hover:bg-white/10">
                            <h3 class="font-bold">LineWatt Proposal</h3>
                            <p class="mt-2 text-sm text-slate-300">Equipment Selection • BOQ • Reports</p>
                            <div class="mt-4 text-sm font-bold text-emerald-400">Learn more →</div>
                        </a>

                        <a href="#" class="rounded-2xl bg-white/5 p-5 ring-1 ring-white/10 transition hover:bg-white/10">
                            <h3 class="font-bold">LineWatt EMS</h3>
                            <p class="mt-2 text-sm text-slate-300">Monitoring • Digital Twin • Asset Registry</p>
                            <div class="mt-4 text-sm font-bold text-emerald-400">Learn more →</div>
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>