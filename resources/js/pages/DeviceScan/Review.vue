<script setup lang="ts">
import PdfViewer from '@/device-scan/components/pdf/PdfViewer.vue';
import DeviceScanLayout from '@/layouts/DeviceScan/DeviceScanLayout.vue';
import { Head } from '@inertiajs/vue3';
import { reactive } from 'vue';

type FieldType = 'text' | 'number' | 'boolean' | 'select';

interface ReviewField {
    key: string;
    label: string;
    group: string;
    type: FieldType;
    unit?: string | null;
    required?: boolean;
    editable?: boolean;
    aliases?: string[];
    help?: string | null;
    validation?: {
        options?: string[];
        [key: string]: unknown;
    };
}

interface ReviewGroup {
    title: string;
    fields: ReviewField[];
}

const props = defineProps<{
    deviceType: string;
    deviceLabel: string;
    schema: ReviewGroup[];
    values: Record<string, string | number | boolean | null>;
    upload?: {
        device_type: string;
        path: string;
        url: string;
        preview_image_url?: string;
        original_name: string;
    } | null;
}>();

const form = reactive<Record<string, string | number | boolean | null>>({
    ...props.values,
});
</script>

<template>
    <Head :title="`Review ${deviceLabel}`" />

    <DeviceScanLayout>
        <div class="h-[calc(100vh-64px)] overflow-hidden bg-slate-50 dark:bg-slate-950">
            <div class="flex h-full flex-col px-6 py-5">
                <div class="mb-4 shrink-0">
                    <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                        DeviceScan Review
                    </p>

                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ deviceLabel }}
                    </h1>

                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Review extracted engineering parameters before CSV export.
                    </p>
                </div>

                <div class="grid min-h-0 flex-1 gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                    <section class="flex min-h-0 flex-col rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="mb-3 flex shrink-0 items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                                    Datasheet Preview
                                </h2>

                                <p
                                    v-if="upload?.original_name"
                                    class="mt-1 truncate text-xs text-slate-500"
                                >
                                    {{ upload.original_name }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                    PDF
                                </span>

                                <a
                                    v-if="upload?.url"
                                    :href="upload.url"
                                    target="_blank"
                                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                >
                                    Open
                                </a>
                            </div>
                        </div>

                        <div class="min-h-0 flex-1">
                            <PdfViewer
                                v-if="upload?.url"
                                :src="upload.url"
                            />

                            <div
                                v-else
                                class="flex h-full min-h-[600px] items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-center dark:border-slate-700 dark:bg-slate-950"
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

                    <section class="min-h-0 overflow-y-auto pr-2">
                        <div class="space-y-5">
                            <div
                                v-for="group in schema"
                                :key="group.title"
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
                            >
                                <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
                                    {{ group.title }}
                                </h2>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div
                                        v-for="field in group.fields"
                                        :key="field.key"
                                        class="space-y-1.5"
                                    >
                                        <label class="flex items-center gap-1 text-sm font-medium text-slate-700 dark:text-slate-300">
                                            <span>{{ field.label }}</span>
                                            <span v-if="field.required" class="text-red-500">*</span>
                                        </label>

                                        <select
                                            v-if="field.type === 'select'"
                                            v-model="form[field.key]"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                        >
                                            <option value="">Select</option>
                                            <option
                                                v-for="option in field.validation?.options ?? []"
                                                :key="option"
                                                :value="option"
                                            >
                                                {{ option }}
                                            </option>
                                        </select>

                                        <select
                                            v-else-if="field.type === 'boolean'"
                                            v-model="form[field.key]"
                                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                        >
                                            <option value="">Unknown</option>
                                            <option :value="true">Yes</option>
                                            <option :value="false">No</option>
                                        </select>

                                        <div v-else class="relative">
                                            <input
                                                v-model="form[field.key]"
                                                :type="field.type === 'number' ? 'number' : 'text'"
                                                step="any"
                                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                            />

                                            <span
                                                v-if="field.unit"
                                                class="pointer-events-none absolute right-3 top-2.5 text-xs text-slate-400"
                                            >
                                                {{ field.unit }}
                                            </span>
                                        </div>

                                        <p v-if="field.help" class="text-xs text-slate-500">
                                            {{ field.help }}
                                        </p>

                                        <p class="text-[11px] text-slate-400">
                                            CSV key: {{ field.key }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 py-4 dark:border-slate-800 dark:bg-slate-950">
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
                                    Download CSV
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </DeviceScanLayout>
</template>