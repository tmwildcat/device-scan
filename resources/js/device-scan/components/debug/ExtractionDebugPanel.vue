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

        <div
            v-if="datasheet?.metadata?.warnings?.length || sourceDocument?.warnings?.length"
            class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
        >
            <div class="font-semibold">Warnings</div>

            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li
                    v-for="(warning, index) in (datasheet?.metadata?.warnings ?? sourceDocument?.warnings ?? [])"
                    :key="index"
                >
                    {{ warning }}
                </li>
            </ul>
        </div>

        <details class="mt-4" open>
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Rendered Pages + Raw Extracted Text
            </summary>

            <div class="mt-3 space-y-5">
                <div
                    v-for="page in sourceDocument?.pages ?? []"
                    :key="page.number"
                    class="overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="border-b border-slate-200 px-4 py-2 text-xs font-semibold dark:border-slate-800">
                        Page {{ page.number }}
                    </div>

                    <div class="grid gap-4 p-4 lg:grid-cols-2">
                        <div>
                            <div class="mb-2 text-xs font-semibold text-slate-500">
                                Rendered Page
                            </div>

                            <img
                                v-if="page.image_url"
                                :src="page.image_url"
                                class="max-h-[640px] w-full rounded-lg border border-slate-200 object-contain dark:border-slate-800"
                            />

                            <div
                                v-else
                                class="flex h-64 items-center justify-center rounded-lg border border-dashed border-slate-300 text-xs text-slate-500 dark:border-slate-700"
                            >
                                No rendered page image.
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 text-xs font-semibold text-slate-500">
                                Raw Text
                            </div>

                            <pre class="max-h-[640px] overflow-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs leading-5 text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200">{{ page.text?.content || 'No text extracted.' }}</pre>
                        </div>
                    </div>
                </div>

                <div
                    v-if="!(sourceDocument?.pages?.length)"
                    class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500 dark:border-slate-700"
                >
                    No source document pages available.
                </div>
            </div>
        </details>

        <details class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Raw Datasheet JSON
            </summary>

            <pre class="mt-3 max-h-96 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(datasheet, null, 2) }}</pre>
        </details>

        <details class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Raw SourceDocument JSON
            </summary>

            <pre class="mt-3 max-h-96 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(sourceDocument, null, 2) }}</pre>
        </details>

        <details class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Raw Upload JSON
            </summary>

            <pre class="mt-3 max-h-80 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(upload, null, 2) }}</pre>
        </details>

        <details v-if="metadata" class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Legacy Metadata JSON
            </summary>

            <pre class="mt-3 max-h-80 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(metadata, null, 2) }}</pre>
        </details>

        <details v-if="matrices?.length" class="mt-4">
            <summary class="cursor-pointer text-xs font-semibold text-slate-600 dark:text-slate-300">
                Legacy Matrices JSON
            </summary>

            <pre class="mt-3 max-h-80 overflow-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-100">{{ JSON.stringify(matrices, null, 2) }}</pre>
        </details>
    </div>
</template>