<script setup lang="ts">
import ExtractionDebugPanel from '@/device-scan/components/debug/ExtractionDebugPanel.vue';
import PdfViewer from '@/device-scan/components/pdf/PdfViewer.vue';
import { Head } from '@inertiajs/vue3';

interface DatasheetCell {
    value: string | number | boolean | null;
    display_value?: string | null;
    unit?: string | null;
}

interface DatasheetRow {
    label: string;
    cells: DatasheetCell[];
}

interface DatasheetTable {
    title?: string | null;
    rows: DatasheetRow[];
}

interface DatasheetModelGroup {
    name: string;
    models: string[];
    tables: DatasheetTable[];
}

interface Datasheet {
    device_type: string;
    manufacturer?: string | null;
    title?: string | null;
    page_count?: number | null;
    status: string;
    metadata?: Record<string, unknown>;
    model_groups: DatasheetModelGroup[];
}

const props = defineProps<{
    deviceType: string;
    deviceLabel: string;
    datasheet?: Datasheet | null;
    sourceDocument?: any | null;
    upload?: {
        device_type: string;
        path: string;
        url: string;
        preview_image_url?: string;
        original_name: string;
    } | null;
}>();

const tableCount = () =>
    props.datasheet?.model_groups?.reduce(
        (count, group) => count + (group.tables?.length ?? 0),
        0,
    ) ?? 0;

const modelCount = () =>
    props.datasheet?.model_groups?.reduce(
        (count, group) => count + (group.models?.length ?? 0),
        0,
    ) ?? 0;
</script>

<template>
    <Head :title="`Review ${deviceLabel}`" />

    <div class="min-h-[calc(100vh-64px)] bg-slate-50 px-6 py-5 dark:bg-slate-950">
        <div class="mx-auto max-w-7xl space-y-6">
            <header>
                <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                    DeviceScan Review
                </p>

                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Review {{ deviceLabel }} Datasheet
                </h1>

                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    Review the scanned datasheet structure before saving it to the library.
                </p>
            </header>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(360px,0.9fr)]">
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-200 p-4 dark:border-slate-800">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                    Datasheet Preview
                                </h2>

                                <p v-if="upload?.original_name" class="mt-1 truncate text-xs text-slate-500">
                                    {{ upload.original_name }}
                                </p>
                            </div>

                            <a
                                v-if="upload?.url"
                                :href="upload.url"
                                target="_blank"
                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Open PDF
                            </a>
                        </div>
                    </div>

                    <div class="p-4">
                        <PdfViewer v-if="upload?.url" :src="upload.url" />

                        <div
                            v-else
                            class="flex h-[520px] items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-center dark:border-slate-700 dark:bg-slate-950"
                        >
                            <div>
                                <div class="text-lg font-semibold text-slate-700 dark:text-slate-200">
                                    PDF preview will appear here
                                </div>
                                <p class="mt-2 max-w-sm text-sm text-slate-500">
                                    Upload a datasheet to begin.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Document Summary
                        </h2>

                        <div class="mt-5 grid gap-3 text-sm">
                            <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                                <span class="text-slate-500">Title</span>
                                <span class="max-w-[220px] truncate font-semibold text-slate-900 dark:text-white">
                                    {{ datasheet?.title || upload?.original_name || '—' }}
                                </span>
                            </div>

                            <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                                <span class="text-slate-500">Device Type</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ deviceLabel }}</span>
                            </div>

                            <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                                <span class="text-slate-500">Pages</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ datasheet?.page_count ?? 0 }}</span>
                            </div>

                            <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                                <span class="text-slate-500">Model Groups</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ datasheet?.model_groups?.length ?? 0 }}</span>
                            </div>

                            <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                                <span class="text-slate-500">Models</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ modelCount() }}</span>
                            </div>

                            <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                                <span class="text-slate-500">Tables</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ tableCount() }}</span>
                            </div>

                            <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                                <span class="text-slate-500">Status</span>
                                <span class="font-semibold text-emerald-600">{{ datasheet?.status ?? 'uploaded' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                        >
                            Back
                        </button>

                        <button
                            type="button"
                            class="rounded-xl bg-emerald-500 px-5 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-600"
                        >
                            Save Datasheet
                        </button>
                    </div>
                </section>
            </div>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Detected Tables
                    </h2>
                </div>

                <div v-if="datasheet?.model_groups?.length" class="space-y-5 p-5">
                    <div
                        v-for="group in datasheet.model_groups"
                        :key="group.name"
                        class="rounded-xl border border-slate-200 dark:border-slate-800"
                    >
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                            <h3 class="font-semibold text-slate-900 dark:text-white">
                                {{ group.name }}
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Models:
                                <span v-if="group.models.length">{{ group.models.join(', ') }}</span>
                                <span v-else>None detected yet</span>
                            </p>
                        </div>

                        <div class="space-y-4 p-4">
                            <details
                                v-for="(table, tableIndex) in group.tables"
                                :key="`${group.name}-${tableIndex}`"
                                open
                                class="rounded-xl border border-slate-200 dark:border-slate-800"
                            >
                                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    {{ table.title || `Table ${tableIndex + 1}` }}
                                    <span class="ml-2 text-xs font-normal text-slate-500">
                                        {{ table.rows.length }} row(s)
                                    </span>
                                </summary>

                                <div class="overflow-x-auto border-t border-slate-200 dark:border-slate-800">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                            <tr v-for="row in table.rows" :key="row.label">
                                                <td class="w-64 bg-slate-50 px-4 py-3 font-medium text-slate-700 dark:bg-slate-950 dark:text-slate-300">
                                                    {{ row.label }}
                                                </td>

                                                <td class="px-4 py-3">
                                                    <div class="flex flex-wrap gap-2">
                                                        <span
                                                            v-for="(cell, cellIndex) in row.cells"
                                                            :key="cellIndex"
                                                            class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                                        >
                                                            {{ cell.display_value ?? cell.value ?? '—' }}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </details>

                            <div
                                v-if="!group.tables.length"
                                class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700"
                            >
                                No tables detected yet.
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="p-8 text-center text-sm text-slate-500"
                >
                    No datasheet structure detected yet.
                </div>
            </section>

            <section>
                <details class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <summary class="cursor-pointer px-5 py-4 text-sm font-semibold text-slate-900 dark:text-white">
                        Debug Output
                    </summary>

                    <div class="border-t border-slate-200 p-5 dark:border-slate-800">
                        <ExtractionDebugPanel
                            :datasheet="datasheet"
                            :source-document="sourceDocument"
                            :upload="upload"
                            :extraction="null"
                            :metadata="datasheet?.metadata ?? null"
                            :tables="datasheet?.model_groups?.[0]?.tables ?? []"
                            :matrices="[]"
                        />
                    </div>
                </details>
            </section>
        </div>
    </div>
</template>