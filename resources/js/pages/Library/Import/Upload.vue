<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { FileUp, UploadCloud } from 'lucide-vue-next';

const props = defineProps<{
    deviceType: 'module' | 'string_inverter' | 'central_inverter';
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const equipment = computed(() => {
    return {
        module: {
            title: 'Import PV Module Datasheet',
            label: 'PV Module',
            description:
                'Upload manufacturer PV module datasheets to extract electrical, mechanical, temperature, certification and packaging data.',
            features: [
                'Extract STC / NOCT / NMOT electrical characteristics',
                'Capture module dimensions, weight, glass, frame and junction box data',
                'Detect temperature coefficients and operating limits',
                'Prepare reviewed module records for the LineWatt equipment library',
            ],
        },
        string_inverter: {
            title: 'Import String Inverter Datasheet',
            label: 'String Inverter',
            description:
                'Upload string inverter datasheets to extract MPPT, DC input, AC output, efficiency, protection and communication data.',
            features: [
                'Extract MPPT count, string inputs and DC voltage windows',
                'Capture rated AC output, max current and grid connection data',
                'Read protection, communication and monitoring capabilities',
                'Prepare inverter records for PV Array Design and SLD workflows',
            ],
        },
        central_inverter: {
            title: 'Import Central Inverter Datasheet',
            label: 'Central Inverter',
            description:
                'Upload central inverter datasheets for utility-scale inverter data including DC input limits, AC output ratings and grid support features.',
            features: [
                'Extract DC input limits and operating voltage ranges',
                'Capture AC output, transformer interface and grid support data',
                'Read efficiency, protection and environmental specifications',
                'Prepare central inverter records for utility-scale engineering workflows',
            ],
        },
    }[props.deviceType];
});

const form = useForm<{
    device_type: string;
    datasheet: File | null;
}>({
    device_type: props.deviceType,
    datasheet: null,
});

const selectedFileName = computed(() => form.datasheet?.name ?? null);

function chooseFile() {
    fileInput.value?.click();
}

function setFile(file: File | null) {
    if (!file) return;

    form.datasheet = file;
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    setFile(target.files?.[0] ?? null);
}

function onDrop(event: DragEvent) {
    event.preventDefault();
    isDragging.value = false;

    setFile(event.dataTransfer?.files?.[0] ?? null);
}

function submit() {
    form.post('/library/import', {
        forceFormData: true,
    });
}
</script>

<template>
    <Head :title="equipment.title" />

    <div class="mx-auto max-w-6xl space-y-8 p-6">
        <div>
            <p class="text-sm font-semibold text-emerald-600">
                LineWatt Library · Import
            </p>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ equipment.title }}
            </h1>

            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-400">
                {{ equipment.description }}
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="lg:col-span-2">
                <div
                    class="rounded-2xl border-2 border-dashed bg-white p-8 text-center shadow-sm transition dark:bg-slate-900"
                    :class="isDragging
                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/30'
                        : 'border-slate-300 dark:border-slate-700'"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop="onDrop"
                >
                    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10">
                        <UploadCloud class="size-7" />
                    </div>

                    <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">
                        Drop datasheet here
                    </h2>

                    <p class="mt-2 text-sm text-slate-500">
                        PDF datasheets are supported now. Scanned PDF and image OCR can be added later.
                    </p>

                    <input
                        ref="fileInput"
                        type="file"
                        accept="application/pdf,.pdf"
                        class="hidden"
                        @change="onFileChange"
                    />

                    <button
                        type="button"
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-700"
                        @click="chooseFile"
                    >
                        <FileUp class="size-4" />
                        Choose PDF
                    </button>

                    <div
                        v-if="selectedFileName"
                        class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                    >
                        Selected: <span class="font-semibold">{{ selectedFileName }}</span>
                    </div>

                    <p
                        v-if="form.errors.datasheet"
                        class="mt-3 text-sm font-medium text-red-600"
                    >
                        {{ form.errors.datasheet }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-slate-900"
                        :disabled="!form.datasheet || form.processing"
                        @click="submit"
                    >
                        {{ form.processing ? 'Processing...' : `Import ${equipment.label}` }}
                    </button>
                </div>
            </section>

            <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                    What this import will do
                </h2>

                <ul class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-400">
                    <li
                        v-for="feature in equipment.features"
                        :key="feature"
                        class="flex gap-2"
                    >
                        <span class="mt-1 size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                        <span>{{ feature }}</span>
                    </li>
                </ul>

                <div class="mt-6 rounded-xl bg-slate-50 p-4 text-xs leading-5 text-slate-500 dark:bg-slate-950">
                    Uploaded datasheets are parsed into draft engineering records first. Engineers can review and approve before publishing to the library.
                </div>
            </aside>
        </div>
    </div>
</template>