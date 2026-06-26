<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Download,
    FileSpreadsheet,
    FileJson,
    FileText,
    FileArchive,
} from 'lucide-vue-next';
</script>

<template>
    <Head title="Export Library" />

    <div class="mx-auto max-w-7xl space-y-8 p-6">

        <!-- Header -->
        <div>
            <p class="text-sm font-semibold text-emerald-600">
                LineWatt Library
            </p>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                Export
            </h1>

            <p class="mt-3 max-w-3xl text-slate-600 dark:text-slate-400">
                Export engineering equipment data, standardized datasheets and
                library content for use within LineWatt Studio and external
                engineering workflows.
            </p>
        </div>

        <!-- Export Types -->

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">

            <button
                class="rounded-2xl border bg-white p-6 text-left shadow-sm transition hover:border-emerald-500 hover:shadow dark:bg-slate-900"
            >
                <FileText class="mb-4 h-8 w-8 text-emerald-600" />

                <h2 class="font-semibold">
                    Equipment Datasheet
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Generate standardized LineWatt PDF datasheets for individual
                    equipment.
                </p>
            </button>

            <button
                class="rounded-2xl border bg-white p-6 text-left shadow-sm transition hover:border-emerald-500 hover:shadow dark:bg-slate-900"
            >
                <FileSpreadsheet class="mb-4 h-8 w-8 text-emerald-600" />

                <h2 class="font-semibold">
                    Excel
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Export engineering library to Microsoft Excel.
                </p>
            </button>

            <button
                class="rounded-2xl border bg-white p-6 text-left shadow-sm transition hover:border-emerald-500 hover:shadow dark:bg-slate-900"
            >
                <FileJson class="mb-4 h-8 w-8 text-emerald-600" />

                <h2 class="font-semibold">
                    JSON
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Export structured engineering data for APIs and
                    integrations.
                </p>
            </button>

            <button
                class="rounded-2xl border bg-white p-6 text-left shadow-sm transition hover:border-emerald-500 hover:shadow dark:bg-slate-900"
            >
                <FileArchive class="mb-4 h-8 w-8 text-emerald-600" />

                <h2 class="font-semibold">
                    Library Package
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    Package equipment, metadata and engineering records for
                    backup or transfer.
                </p>
            </button>

        </div>

        <!-- Roadmap -->

        <div class="rounded-2xl border bg-white p-8 shadow-sm dark:bg-slate-900">

            <h2 class="text-xl font-semibold">
                Planned Export Capabilities
            </h2>

            <div class="mt-6 grid gap-6 md:grid-cols-2">

                <div>
                    <h3 class="font-semibold">
                        Engineering Documents
                    </h3>

                    <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li>• Standardized Equipment Datasheets (PDF)</li>
                        <li>• Equipment Catalogue (PDF)</li>
                        <li>• Comparison Reports (PDF)</li>
                        <li>• Manufacturer Datasheet Bundle</li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold">
                        Engineering Data
                    </h3>

                    <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li>• CSV Export</li>
                        <li>• Excel Export</li>
                        <li>• JSON Export</li>
                        <li>• LineWatt Library Package</li>
                    </ul>
                </div>

            </div>

        </div>

        <!-- Footer CTA -->

        <div class="flex justify-end">

            <button
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white shadow hover:bg-emerald-700"
                disabled
            >
                <Download class="h-5 w-5" />
                Export (Coming Soon)
            </button>

        </div>

    </div>
</template>