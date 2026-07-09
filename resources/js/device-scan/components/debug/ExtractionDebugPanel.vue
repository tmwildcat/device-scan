<script setup lang="ts">
defineProps<{
    datasheet?: any | null;
    upload?: any | null;
    sourceDocument?: any | null;
    extraction?: any | null;
    metadata?: any | null;
    tables?: any[];
    matrices?: any[];
}>();
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
            Debug Output
        </h3>

        <div class="mt-4 grid gap-3 text-xs md:grid-cols-5">
            <div class="rounded-lg bg-white p-3 dark:bg-slate-900">
                <div class="text-slate-500">Pages</div>
                <div class="mt-1 font-semibold">
                    {{ datasheet?.page_count ?? sourceDocument?.page_count ?? extraction?.page_count ?? '-' }}
                </div>
            </div>

            <div class="rounded-lg bg-white p-3 dark:bg-slate-900">
                <div class="text-slate-500">Model Groups</div>
                <div class="mt-1 font-semibold">
                    {{ datasheet?.model_groups?.length ?? 0 }}
                </div>
            </div>

            <div class="rounded-lg bg-white p-3 dark:bg-slate-900">
                <div class="text-slate-500">Tables</div>
                <div class="mt-1 font-semibold">
                    {{ tables?.length ?? datasheet?.metadata?.table_count ?? 0 }}
                </div>
            </div>

            <div class="rounded-lg bg-white p-3 dark:bg-slate-900">
                <div class="text-slate-500">Warnings</div>
                <div class="mt-1 font-semibold">
                    {{ datasheet?.metadata?.warnings?.length ?? sourceDocument?.warnings?.length ?? 0 }}
                </div>
            </div>

            <div class="rounded-lg bg-white p-3 dark:bg-slate-900">
                <div class="text-slate-500">Status</div>
                <div class="mt-1 font-semibold">
                    {{ datasheet?.status ?? '-' }}
                </div>
            </div>
        </div>

        <div v-if="datasheet?.metadata?.warnings?.length || sourceDocument?.warnings?.length"
            class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            <div class="font-semibold">Warnings</div>

            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li v-for="(warning, index) in (datasheet?.metadata?.warnings ?? sourceDocument?.warnings ?? [])"
                    :key="index">
                    {{ warning }}
                </li>
            </ul>
        </div>

        <details class="mt-4" open>
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Rendered Pages + Raw Extracted Text
            </summary>

            <div class="mt-3 space-y-5">
                <div v-for="page in sourceDocument?.pages ?? []" :key="page.number"
                    class="overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-200 px-4 py-2 text-xs font-semibold dark:border-slate-800">
                        Page {{ page.number }}
                    </div>

                    <details class="mt-4" open>
                        <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                            OCR Blocks ({{ page.ocr?.blocks?.length ?? 0 }})
                        </summary>

                        <div v-for="(block, index) in page.ocr?.blocks ?? []" :key="index"
                            class="mt-4 rounded-xl border border-slate-300 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">

                                <span class="rounded bg-slate-100 px-2 py-1 font-semibold dark:bg-slate-800">
                                    Block {{ Number(index) + 1 }}
                                </span>

                                <span
                                    class="rounded bg-blue-100 px-2 py-1 font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ block.metadata?.engineering_type ?? 'unknown' }}
                                </span>

                                <span
                                    class="rounded bg-green-100 px-2 py-1 font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                    Score {{ block.metadata?.engineering_score ?? 0 }}
                                </span>

                                <span class="text-slate-500">
                                    x={{ block.left }}
                                    y={{ block.top }}
                                    w={{ block.width }}
                                    h={{ block.height }}
                                </span>

                            </div>

                            <pre
                                class="whitespace-pre-wrap rounded-lg bg-slate-50 p-3 text-xs leading-5 dark:bg-slate-950">{{ block.text }}</pre>

                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs text-slate-500">
                                    Classifier Scores
                                </summary>

                                <pre class="mt-2 rounded bg-slate-900 p-3 text-xs text-slate-100">{{
                                    JSON.stringify(block.metadata?.engineering_scores ?? {}, null, 2)
                                }}</pre>

                            </details>

                        </div>
                    </details>


                    <details class="mt-4" open>
                        <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                            Table Regions ({{ page.ocr?.table_regions?.length ?? 0 }})
                        </summary>

                        <div v-for="(region, index) in page.ocr?.table_regions ?? []" :key="index"
                            class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 p-4 dark:border-emerald-700 dark:bg-emerald-950/20">
                            <div class="mb-3 flex flex-wrap items-center gap-2">

                                <span class="rounded bg-white px-2 py-1 text-xs font-semibold dark:bg-slate-800">
                                    Region {{ Number(index) + 1 }}
                                </span>

                                <span
                                    class="rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ region.type }}
                                </span>

                                <span
                                    class="rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                    score {{ region.metadata?.engineering_score ?? 0 }}
                                </span>

                                <span class="text-xs text-slate-500">
                                    x={{ region.left }}
                                    y={{ region.top }}
                                    w={{ region.width }}
                                    h={{ region.height }}
                                </span>

                            </div>

                            <pre
                                class="whitespace-pre-wrap rounded-lg bg-white p-3 text-xs leading-5 dark:bg-slate-900">{{ region.block.text }}</pre>

                        </div>
                    </details>


                    <details class="mt-4" open>
                        <summary class="cursor-pointer text-sm font-semibold">
                            Grids ({{ page.ocr?.grids?.length ?? 0 }})
                        </summary>

                        <div v-for="(grid, gIndex) in page.ocr?.grids ?? []" :key="gIndex"
                            class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <div class="mb-3 flex flex-wrap gap-3">

                                <span class="rounded bg-white px-2 py-1 font-semibold">
                                    {{ grid.type }}
                                </span>

                                <span>
                                    Columns {{ grid.columns.length }}
                                </span>

                                <span>
                                    Rows {{ grid.rows.length }}
                                </span>

                                <span>
                                    Cells {{ grid.cells.length }}
                                </span>

                            </div>

                            <div v-if="grid.header_detection"
                                class="mb-4 rounded-lg border border-slate-200 bg-white p-3 text-xs dark:border-slate-700 dark:bg-slate-900">
                                <div class="mb-2 font-semibold text-slate-900 dark:text-white">
                                    Header Detection
                                </div>

                                <div class="grid gap-2 md:grid-cols-2">
                                    <div>
                                        <span class="text-slate-500">Parameter Column:</span>
                                        <span class="ml-2 font-semibold">
                                            {{ grid.header_detection.parameter_column ?? 'Not detected' }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-slate-500">Model Header Row:</span>
                                        <span class="ml-2 font-semibold">
                                            {{ grid.header_detection.model_header_row ?? 'Not detected' }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-slate-500">Value Columns:</span>
                                        <span class="ml-2 font-semibold">
                                            {{ grid.header_detection.value_columns?.join(', ') || 'None' }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-slate-500">Data Rows:</span>
                                        <span class="ml-2 font-semibold">
                                            {{ grid.header_detection.data_rows?.join(', ') || 'None' }}
                                        </span>
                                    </div>
                                </div>
                            </div>


                            <table class="w-full border text-xs">
    <thead>
        <tr>
            <th class="border p-2">Row</th>
            <th class="border p-2">Col</th>
            <th class="border p-2">Text</th>
            <th class="border p-2">Canonical</th>
            <th class="border p-2">X</th>
            <th class="border p-2">Y</th>
            <th class="border p-2">Source</th>
            <th class="border p-2">OCR Text</th>
            <th class="border p-2">Native Text</th>
            <th class="border p-2">Native Words</th>
        </tr>
    </thead>

    <tbody>
        <tr
            v-for="(cell, cIndex) in grid.cells"
            :key="cIndex"
        >
            <td class="border p-1">{{ cell.row }}</td>

            <td class="border p-1">{{ cell.column }}</td>

            <td class="border p-1 font-medium">
                {{ cell.text }}
            </td>

            <td class="border p-1">
                {{ cell.metadata?.canonical_parameter ?? '-' }}
            </td>

            <td class="border p-1">{{ cell.left }}</td>

            <td class="border p-1">{{ cell.top }}</td>

            <td class="border p-1">
                <span
                    class="rounded px-2 py-1 text-[11px] font-semibold"
                    :class="
                        cell.text_source === 'native_pdf'
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                            : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                    "
                >
                    {{ cell.text_source ?? 'ocr' }}
                </span>
            </td>

            <td class="border p-1 text-slate-500">
                {{ cell.ocr_text ?? '-' }}
            </td>

            <td class="border p-1 text-sky-700 dark:text-sky-300">
                {{ cell.native_text ?? '-' }}
            </td>

            <td class="border p-1 text-center">
                {{ cell.metadata?.native_word_count ?? 0 }}
            </td>
        </tr>
    </tbody>
</table>

                        </div>

                    </details>


                    <details class="mt-4" open>
                        <summary class="cursor-pointer text-sm font-semibold">
                            Engineering Tables ({{ page.ocr?.engineering_tables?.length ?? 0 }})
                        </summary>

                        <div v-for="(table, tIndex) in page.ocr?.engineering_tables ?? []" :key="tIndex"
                            class="mt-4 rounded-xl border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-950/20">
                            <div class="mb-3 flex flex-wrap gap-3 text-xs">
                                <span class="rounded bg-white px-2 py-1 font-semibold dark:bg-slate-800">
                                    Table {{ Number(tIndex) + 1 }}
                                </span>

                                <span
                                    class="rounded bg-purple-100 px-2 py-1 font-semibold text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                    {{ table.type }}
                                </span>

                                <span>
                                    Models: {{ table.models?.join(', ') || '-' }}
                                </span>

                                <span>
                                    Rows: {{ table.rows?.length ?? 0 }}
                                </span>
                            </div>

                            <div
                                class="overflow-auto rounded-lg border border-purple-200 bg-white dark:border-purple-800 dark:bg-slate-900">
                                <table class="min-w-full text-left text-xs">
                                    <thead class="bg-purple-100 dark:bg-purple-900/40">
                                        <tr>
                                            <th class="border p-2">Parameter</th>
                                            <th class="border p-2">Unit</th>
                                            <th v-for="model in table.models ?? []" :key="model" class="border p-2">
                                                {{ model }}
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr v-for="(row, rIndex) in table.rows ?? []" :key="rIndex">
                                            <td class="border p-2 font-semibold">
                                                {{ row.parameter }}
                                            </td>

                                            <td class="border p-2">
                                                {{ row.unit ?? '-' }}
                                            </td>

                                            <td v-for="model in table.models ?? []" :key="model" class="border p-2">
                                                {{ row.values?.[model] ?? '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs text-slate-500">
                                    Engineering Table JSON
                                </summary>

                                <pre
                                    class="mt-2 max-h-80 overflow-auto rounded bg-slate-900 p-3 text-xs text-slate-100">{{
                                        JSON.stringify(table, null, 2)
                                    }}</pre>
                            </details>
                        </div>
                    </details>

                    <details class="mt-4" open>
                        <summary class="cursor-pointer text-sm font-semibold">
                            Canonical Module Electrical
                            ({{ page.ocr?.module_electrical?.length ?? 0 }})
                        </summary>

                        <div v-for="(electrical, eIndex) in page.ocr?.module_electrical ?? []" :key="eIndex"
                            class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-950/20">
                            <div class="mb-3 flex flex-wrap gap-3 text-xs">
                                <span class="rounded bg-white px-2 py-1 font-semibold dark:bg-slate-800">
                                    Electrical {{ Number(eIndex) + 1 }}
                                </span>

                                <span>
                                    Variants: {{ electrical.variants?.length ?? 0 }}
                                </span>
                            </div>

                            <div v-if="electrical.variants?.length"
                                class="overflow-auto rounded-lg border border-emerald-200 bg-white dark:border-emerald-800 dark:bg-slate-900">
                                <table class="min-w-full text-left text-xs">
                                    <thead class="bg-emerald-100 dark:bg-emerald-900/40">
                                        <tr>
                                            <th class="border p-2">Pmax W</th>
                                            <th class="border p-2">Voc V</th>
                                            <th class="border p-2">Vmp V</th>
                                            <th class="border p-2">Isc A</th>
                                            <th class="border p-2">Imp A</th>
                                            <th class="border p-2">Efficiency %</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr v-for="(variant, vIndex) in electrical.variants ?? []" :key="vIndex">
                                            <td class="border p-2">
                                                {{ variant.rated_max_power_w ?? '-' }}
                                            </td>
                                            <td class="border p-2">
                                                {{ variant.open_circuit_voltage_v ?? '-' }}
                                            </td>
                                            <td class="border p-2">
                                                {{ variant.maximum_power_voltage_v ?? '-' }}
                                            </td>
                                            <td class="border p-2">
                                                {{ variant.short_circuit_current_a ?? '-' }}
                                            </td>
                                            <td class="border p-2">
                                                {{ variant.maximum_power_current_a ?? '-' }}
                                            </td>
                                            <td class="border p-2">
                                                {{ variant.module_efficiency_percent ?? '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-else
                                class="rounded-lg border border-dashed border-emerald-300 p-4 text-xs text-slate-500 dark:border-emerald-800">
                                No canonical electrical variants extracted.
                            </div>

                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs text-slate-500">
                                    Canonical Module Electrical JSON
                                </summary>

                                <pre
                                    class="mt-2 max-h-80 overflow-auto rounded bg-slate-900 p-3 text-xs text-slate-100">{{
                                        JSON.stringify(electrical, null, 2)
                                    }}</pre>
                            </details>
                        </div>
                    </details>


                    <details class="mt-4">
                        <summary>OCR Words</summary>

                        <pre class="max-h-80 overflow-auto">
                        {{ JSON.stringify(page.ocr, null, 2) }}
                        </pre>
                    </details>

                    <details class="mt-4" open>
                        <summary>OCR Lines</summary>

                        <div v-for="(line, index) in page.ocr?.lines ?? []" :key="index"
                            class="border-b border-slate-200 py-2 text-xs">
                            <div class="font-semibold">
                                {{ line.text }}
                            </div>

                            <div class="text-slate-500">
                                y={{ line.top }}
                                x={{ line.left }}
                                h={{ line.height }}
                                words={{ line.words.length }}
                            </div>
                        </div>
                    </details>

                    <div class="grid gap-4 p-4 lg:grid-cols-2">
                        <div>
                            <div class="mb-2 text-xs font-semibold text-slate-500">
                                Rendered Page
                            </div>

                            <img v-if="page.image_url" :src="page.image_url"
                                class="max-h-[640px] w-full rounded-lg border border-slate-200 object-contain dark:border-slate-800" />

                            <div v-else
                                class="flex h-64 items-center justify-center rounded-lg border border-dashed border-slate-300 text-xs text-slate-500 dark:border-slate-700">
                                No rendered page image.
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 text-xs font-semibold text-slate-500">
                                Detected Sections
                            </div>

                            <div v-if="page.sections?.length"
                                class="mb-4 space-y-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900 dark:bg-emerald-950/30">
                                <div v-for="section in page.sections"
                                    :key="`${page.number}-${section.type}-${section.title}`"
                                    class="rounded-md bg-white px-3 py-2 text-xs dark:bg-slate-900">
                                    <div class="font-semibold text-emerald-700 dark:text-emerald-300">
                                        {{ section.type }}
                                    </div>

                                    <div class="mt-1 text-slate-600 dark:text-slate-300">
                                        {{ section.title }}
                                    </div>

                                    <div v-if="section.metadata?.matched_keyword"
                                        class="mt-1 text-[11px] text-slate-400">
                                        matched: {{ section.metadata.matched_keyword }}
                                    </div>
                                </div>
                            </div>

                            <div v-else
                                class="mb-4 rounded-lg border border-dashed border-slate-300 p-4 text-xs text-slate-500 dark:border-slate-700">
                                No sections detected on this page.
                            </div>

                            <div class="mb-2 text-xs font-semibold text-slate-500">
                                Raw Text
                            </div>

                            <pre
                                class="max-h-[640px] overflow-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs leading-5 text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200">{{ page.text?.content || 'No text extracted.' }}</pre>
                        </div>
                    </div>
                </div>

                <div v-if="!(sourceDocument?.pages?.length)"
                    class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500 dark:border-slate-700">
                    No source document pages available.
                </div>
            </div>
        </details>



        <details class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Raw Datasheet JSON
            </summary>

            <pre
                class="mt-3 max-h-96 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(datasheet, null, 2) }}</pre>
        </details>

        <details class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Raw SourceDocument JSON
            </summary>

            <pre
                class="mt-3 max-h-96 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(sourceDocument, null, 2) }}</pre>
        </details>

        <details class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Raw Upload JSON
            </summary>

            <pre
                class="mt-3 max-h-80 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(upload, null, 2) }}</pre>
        </details>

        <details v-if="metadata" class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Legacy Metadata JSON
            </summary>

            <pre
                class="mt-3 max-h-80 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(metadata, null, 2) }}</pre>
        </details>

        <details v-if="matrices?.length" class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Legacy Matrices JSON
            </summary>

            <pre
                class="mt-3 max-h-80 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(matrices, null, 2) }}</pre>
        </details>
    </div>
</template>