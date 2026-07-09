<script setup lang="ts">
import EntitlementDiagnostics from '@/components/linewatt/EntitlementDiagnostics.vue';
import WorkspaceHeader from '@/components/linewatt/WorkspaceHeader.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, ClipboardList, FileSpreadsheet, FileText, FileUp, Info, Layers3, Link2, ShieldCheck, UploadCloud, Zap } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    deviceType: 'module' | 'inverter';
    workspace: 'my-library' | 'central' | 'publisher' | 'partner';
    workspaceName: string;
    destinationLabel: string;
    postUrl: string;
    pvsystPostUrl?: string | null;
    backUrl: string;
    maxPdfSizeMb: number;
    malwareScanEnabled: boolean;
    lockedManufacturer?: string | null;
}>();

const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>();
const fileInput = ref<HTMLInputElement | null>(null);
const pvsystFileInput = ref<HTMLInputElement | null>(null);
const selectedFileName = ref<string | null>(null);
const selectedPvsystFileName = ref<string | null>(null);
const isDragging = ref(false);
const manufacturerSuggestions = ref<Array<{ label: string; value: string; matched: boolean; source: string }>>([]);
const manufacturerLoading = ref(false);
let manufacturerDebounce: ReturnType<typeof setTimeout> | null = null;
const importMethod = ref<'pdf' | 'url' | 'pvsyst'>('pdf');
const form = useForm({
    device_type: props.deviceType,
    manufacturer: '',
    product_name: '',
    source_url: '',
    notes: '',
    datasheet: null as File | null,
});
const pvsystForm = useForm({
    device_type: props.deviceType,
    input_type: 'paste' as 'paste' | 'xlsx',
    mapping_template: props.deviceType === 'inverter' ? 'pvsyst_inverter_component' : 'pvsyst_module_component',
    manufacturer: props.lockedManufacturer ?? '',
    model_name: '',
    series: '',
    pvsyst_data: '',
    pvsyst_file: null as File | null,
});

if (props.lockedManufacturer) {
    form.manufacturer = props.lockedManufacturer;
}

const importMethods = computed(() => [
    {
        key: 'pdf',
        label: 'Upload PDF Datasheet',
        description: 'Parse a manufacturer PDF with the compiler.',
        icon: FileText,
        disabled: false,
    },
    {
        key: 'url',
        label: 'Add from Manufacturer URL',
        description: 'Source from a manufacturer link. Coming next.',
        icon: Link2,
        disabled: true,
    },
    {
        key: 'pvsyst',
        label: 'Import from PVSyst',
        description: 'Create a record from structured component data.',
        icon: ClipboardList,
        disabled: !props.pvsystPostUrl,
    },
]);

const deviceTypes = [
    {
        key: 'module',
        label: 'Module',
        description: 'Solar PV module datasheet',
        icon: Layers3,
    },
    {
        key: 'inverter',
        label: 'Inverter',
        description: 'String, hybrid, storage or central inverter datasheet',
        icon: Zap,
    },
];

const pvsystTemplateFields = computed(() => {
    const moduleFields = [
        ['rated_max_power_w', 'Rated max power', 'W'],
        ['open_circuit_voltage_v', 'Open circuit voltage', 'V'],
        ['maximum_power_voltage_v', 'Maximum power voltage', 'V'],
        ['short_circuit_current_a', 'Short circuit current', 'A'],
        ['maximum_power_current_a', 'Maximum power current', 'A'],
        ['module_efficiency_percent', 'Module efficiency', '%'],
        ['length_mm', 'Length', 'mm'],
        ['width_mm', 'Width', 'mm'],
        ['thickness_mm', 'Thickness', 'mm'],
        ['weight_kg', 'Weight', 'kg'],
        ['maximum_system_voltage', 'Maximum system voltage', 'V'],
        ['maximum_series_fuse_rating', 'Maximum series fuse rating', 'A'],
        ['temperature_coefficient_pmax', 'Temperature coefficient Pmax', '%/°C'],
        ['temperature_coefficient_voc', 'Temperature coefficient Voc', '%/°C'],
        ['temperature_coefficient_isc', 'Temperature coefficient Isc', '%/°C'],
    ];
    const inverterFields = [
        ['rated_ac_power', 'Rated AC power', 'W'],
        ['max_ac_power', 'Max AC power', 'W'],
        ['rated_apparent_power', 'Rated apparent power', 'VA'],
        ['max_dc_voltage', 'Max DC voltage', 'V'],
        ['startup_voltage', 'Startup voltage', 'V'],
        ['rated_dc_voltage', 'Rated DC voltage', 'V'],
        ['mppt_voltage_range', 'MPPT voltage range', 'V'],
        ['mppt_count', 'MPPT count', ''],
        ['strings_per_mppt', 'Strings per MPPT', ''],
        ['max_input_current', 'Max input current', 'A'],
        ['max_short_circuit_current', 'Max short circuit current', 'A'],
        ['rated_ac_voltage', 'Rated AC voltage', 'V'],
        ['rated_frequency', 'Rated frequency', 'Hz'],
        ['rated_output_current', 'Rated output current', 'A'],
        ['max_output_current', 'Max output current', 'A'],
        ['power_factor', 'Power factor', ''],
        ['thd', 'THD', '%'],
    ];

    return (pvsystForm.device_type === 'inverter' ? inverterFields : moduleFields).map((field, index) => ({
        key: field[0],
        label: field[1],
        unit: field[2],
        sequence: index + 1,
        value: pvsystPreviewValues.value[index] ?? '',
    }));
});
const uploadTitle = computed(() => {
    if (props.workspace === 'my-library') {
        return 'Compile My PDF Datasheet';
    }

    return props.workspace === 'partner' ? 'Submit Datasheet' : 'Upload Datasheet';
});

const uploadDescription = computed(() => props.workspace === 'my-library'
    ? "Can't find the equipment you're looking for? Compile your own datasheet and save it to My Private Datasets."
    : 'PDFs are staged privately, checked, scanned and then queued for Engineering Record compilation.');

const chooseFile = () => {
    fileInput.value?.click();
};

const setFile = (file: File | null) => {
    selectedFileName.value = file?.name ?? null;
    form.datasheet = file;
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    setFile(target.files?.[0] ?? null);
};

const onDrop = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;
    setFile(event.dataTransfer?.files?.[0] ?? null);
};

const submit = () => {
    form.post(props.postUrl, {
        forceFormData: true,
    });
};

const submitPvsyst = () => {
    if (!props.pvsystPostUrl) {
        return;
    }

    pvsystForm.post(props.pvsystPostUrl, {
        forceFormData: true,
    });
};

const pvsystPreviewValues = computed(() => {
    return pvsystForm.pvsyst_data
        .split(/\r\n|\r|\n|,|;|\t/)
        .map((value) => value.trim())
        .filter(Boolean);
});

function selectImportMethod(method: 'pdf' | 'url' | 'pvsyst'): void {
    const candidate = importMethods.value.find((item) => item.key === method);

    if (candidate?.disabled) {
        return;
    }

    importMethod.value = method;
    manufacturerSuggestions.value = [];
}

function choosePvsystFile(): void {
    pvsystFileInput.value?.click();
}

function setPvsystFile(file: File | null): void {
    selectedPvsystFileName.value = file?.name ?? null;
    pvsystForm.pvsyst_file = file;
}

function onPvsystFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    setPvsystFile(target.files?.[0] ?? null);
}

function selectDeviceType(deviceType: 'module' | 'inverter'): void {
    form.device_type = deviceType;
    pvsystForm.device_type = deviceType;
    pvsystForm.mapping_template = deviceType === 'inverter' ? 'pvsyst_inverter_component' : 'pvsyst_module_component';
    manufacturerSuggestions.value = [];
}

function queueManufacturerSearch(): void {
    if (manufacturerDebounce) {
        clearTimeout(manufacturerDebounce);
    }

    if (currentManufacturer().trim().length < 2) {
        manufacturerSuggestions.value = [];
        return;
    }

    manufacturerLoading.value = true;
    manufacturerDebounce = setTimeout(fetchManufacturers, 300);
}

async function fetchManufacturers(): Promise<void> {
    const params = new URLSearchParams({
        q: currentManufacturer().trim(),
        device_type: currentDeviceType(),
    });
    const response = await fetch(`/uploads/manufacturers?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });
    manufacturerSuggestions.value = response.ok ? await response.json() : [];
    manufacturerLoading.value = false;
}

function selectManufacturer(suggestion: { value: string }): void {
    if (importMethod.value === 'pvsyst') {
        pvsystForm.manufacturer = suggestion.value;
    } else {
        form.manufacturer = suggestion.value;
    }

    manufacturerSuggestions.value = [];
}

function currentManufacturer(): string {
    return importMethod.value === 'pvsyst' ? pvsystForm.manufacturer : form.manufacturer;
}

function currentDeviceType(): string {
    return importMethod.value === 'pvsyst' ? pvsystForm.device_type : form.device_type;
}
</script>

<template>
    <Head :title="uploadTitle" />

    <div class="min-h-screen bg-slate-50 text-slate-950">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <WorkspaceHeader
                eyebrow="Secure Datasheet Intake"
                :title="uploadTitle"
                :description="uploadDescription"
                :workspace-name="props.workspaceName"
                :tenant-or-partner="props.destinationLabel"
            />

            <div v-if="page.props.flash?.success" class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ page.props.flash.success }}
            </div>

            <section class="mt-8 grid gap-6 lg:grid-cols-[1fr_340px]">
                <div class="space-y-6">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-black text-slate-950">Import Method</h2>
                                <p class="mt-1 text-sm text-slate-600">Choose how this Engineering Record should be created.</p>
                            </div>
                            <Link
                                :href="props.backUrl"
                                class="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                            >
                                Back
                            </Link>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-3">
                            <button
                                v-for="method in importMethods"
                                :key="method.key"
                                type="button"
                                class="rounded-lg border p-4 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                                :class="importMethod === method.key ? 'border-emerald-200 bg-emerald-50 ring-2 ring-emerald-100' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                                :disabled="method.disabled"
                                @click="selectImportMethod(method.key as 'pdf' | 'url' | 'pvsyst')"
                            >
                                <component :is="method.icon" class="size-5" :class="importMethod === method.key ? 'text-emerald-700' : 'text-slate-500'" />
                                <div class="mt-3 flex items-center gap-2">
                                    <h3 class="font-bold text-slate-950">{{ method.label }}</h3>
                                    <CheckCircle2 v-if="importMethod === method.key" class="size-4 text-emerald-700" />
                                </div>
                                <p class="mt-1 text-sm text-slate-600">{{ method.description }}</p>
                            </button>
                        </div>

                        <div class="mt-6 border-t border-slate-100 pt-5">
                            <h3 class="text-sm font-black uppercase tracking-[0.12em] text-slate-400">Product Type</h3>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <button
                                v-for="deviceType in deviceTypes"
                                :key="deviceType.key"
                                type="button"
                                class="rounded-lg border p-4 text-left transition"
                                :class="form.device_type === deviceType.key ? 'border-emerald-200 bg-emerald-50 ring-2 ring-emerald-100' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                                @click="selectDeviceType(deviceType.key as 'module' | 'inverter')"
                            >
                                <component :is="deviceType.icon" class="size-5" :class="form.device_type === deviceType.key ? 'text-emerald-700' : 'text-slate-500'" />
                                <div class="mt-3 flex items-center gap-2">
                                    <h3 class="font-bold text-slate-950">{{ deviceType.label }} upload</h3>
                                    <CheckCircle2 v-if="form.device_type === deviceType.key" class="size-4 text-emerald-700" />
                                </div>
                                <p class="mt-1 text-sm text-slate-600">{{ deviceType.description }}.</p>
                            </button>
                        </div>
                    </div>

                    <div v-if="importMethod === 'url'" class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-900 shadow-sm">
                        Manufacturer URL import is planned next. Use PDF upload or PVSyst import for this milestone.
                    </div>

                    <form v-if="importMethod === 'pdf'" class="space-y-6" @submit.prevent="submit">
                        <div class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2">
                            <label class="relative block text-sm font-bold text-slate-700">
                                Manufacturer <span class="text-red-600">*</span>
                                <input
                                    v-model="form.manufacturer"
                                    type="text"
                                    class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                    placeholder="Start typing manufacturer..."
                                    required
                                    :readonly="Boolean(props.lockedManufacturer)"
                                    @input="props.lockedManufacturer ? undefined : queueManufacturerSearch"
                                />
                                <p v-if="props.lockedManufacturer" class="mt-1 text-xs font-semibold text-emerald-700">
                                    Manufacturer is locked to your company account.
                                </p>
                                <div v-if="!props.lockedManufacturer && (manufacturerSuggestions.length || manufacturerLoading)" class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                    <div v-if="manufacturerLoading" class="px-3 py-2 text-sm text-slate-500">Searching...</div>
                                    <button v-for="suggestion in manufacturerSuggestions" :key="suggestion.value" type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="selectManufacturer(suggestion)">
                                        <span class="font-black">{{ suggestion.label }}</span>
                                        <span class="ml-2 text-xs text-slate-500">{{ suggestion.source }}</span>
                                    </button>
                                </div>
                                <p v-else-if="!props.lockedManufacturer && form.manufacturer.trim().length >= 2 && !manufacturerLoading" class="mt-1 text-xs font-semibold text-slate-500">
                                    No canonical match selected yet. A new manufacturer name is allowed only when no good match exists.
                                </p>
                                <p v-if="form.errors.manufacturer" class="mt-1 text-xs font-bold text-red-700">{{ form.errors.manufacturer }}</p>
                            </label>
                            <label class="block text-sm font-bold text-slate-700">
                                Datasheet family / series / title
                                <input
                                    v-model="form.product_name"
                                    type="text"
                                    class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                    placeholder="Recommended"
                                />
                            </label>
                            <label class="block text-sm font-bold text-slate-700 md:col-span-2">
                                Manufacturer source URL
                                <input
                                    v-model="form.source_url"
                                    type="url"
                                    class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                    placeholder="Optional, used for external-source attribution and PDF distribution policy"
                                />
                                <p v-if="form.errors.source_url" class="mt-1 text-xs font-bold text-red-700">{{ form.errors.source_url }}</p>
                            </label>
                            <label class="block text-sm font-bold text-slate-700 md:col-span-2">
                                Notes
                                <textarea
                                    v-model="form.notes"
                                    class="mt-2 min-h-24 w-full rounded-md border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                    placeholder="Optional upload context for reviewers..."
                                />
                            </label>
                        </div>

                        <div
                            class="rounded-lg border-2 border-dashed bg-white p-8 text-center shadow-sm transition"
                            :class="isDragging ? 'border-emerald-400 bg-emerald-50' : 'border-slate-300'"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop="onDrop"
                        >
                            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                <UploadCloud class="size-7" />
                            </div>
                            <h2 class="mt-4 text-lg font-black text-slate-950">Select PDF Datasheet</h2>
                            <p class="mt-2 text-sm text-slate-600">
                                Accepted type: PDF only. Maximum size: {{ props.maxPdfSizeMb }} MB.
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
                                class="mt-6 inline-flex items-center gap-2 rounded-md bg-slate-950 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800"
                                @click="chooseFile"
                            >
                                <FileUp class="size-4" />
                                Choose PDF
                            </button>

                            <div
                                v-if="selectedFileName"
                                class="mx-auto mt-5 flex max-w-xl items-center justify-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700"
                            >
                                <FileText class="size-4 text-slate-500" />
                                {{ selectedFileName }}
                            </div>

                            <p v-if="form.errors.datasheet" class="mt-4 text-sm font-semibold text-red-700">
                                {{ form.errors.datasheet }}
                            </p>

                            <button
                                type="submit"
                                class="mt-6 inline-flex items-center gap-2 rounded-md bg-slate-950 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 disabled:cursor-wait disabled:opacity-60"
                                :disabled="form.processing || !form.datasheet"
                            >
                                <ShieldCheck class="size-4" />
                                {{ form.processing ? 'Checking...' : 'Secure Upload & Compile' }}
                            </button>
                        </div>
                    </form>

                    <form v-if="importMethod === 'pvsyst'" class="space-y-6" @submit.prevent="submitPvsyst">
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-black text-slate-950">Import from PVSyst</h2>
                                    <p class="mt-1 text-sm text-slate-600">
                                        Structured component data imported from PVSyst. No PDF preview is created for this source.
                                    </p>
                                </div>
                                <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black uppercase tracking-[0.12em] text-sky-800">Structured Input</span>
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-2">
                                <label class="relative block text-sm font-bold text-slate-700">
                                    Manufacturer <span class="text-red-600">*</span>
                                    <input
                                        v-model="pvsystForm.manufacturer"
                                        type="text"
                                        class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                        placeholder="Start typing manufacturer..."
                                        required
                                        :readonly="Boolean(props.lockedManufacturer)"
                                        @input="props.lockedManufacturer ? undefined : queueManufacturerSearch"
                                    />
                                    <div v-if="!props.lockedManufacturer && (manufacturerSuggestions.length || manufacturerLoading)" class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg">
                                        <div v-if="manufacturerLoading" class="px-3 py-2 text-sm text-slate-500">Searching...</div>
                                        <button v-for="suggestion in manufacturerSuggestions" :key="suggestion.value" type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="selectManufacturer(suggestion)">
                                            <span class="font-black">{{ suggestion.label }}</span>
                                            <span class="ml-2 text-xs text-slate-500">{{ suggestion.source }}</span>
                                        </button>
                                    </div>
                                    <p v-if="pvsystForm.errors.manufacturer" class="mt-1 text-xs font-bold text-red-700">{{ pvsystForm.errors.manufacturer }}</p>
                                </label>

                                <label class="block text-sm font-bold text-slate-700">
                                    Model name <span class="text-red-600">*</span>
                                    <input
                                        v-model="pvsystForm.model_name"
                                        type="text"
                                        class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                        placeholder="e.g. JKM600N-78HL4"
                                        required
                                    />
                                    <p v-if="pvsystForm.errors.model_name" class="mt-1 text-xs font-bold text-red-700">{{ pvsystForm.errors.model_name }}</p>
                                </label>

                                <label class="block text-sm font-bold text-slate-700">
                                    Family / series
                                    <input
                                        v-model="pvsystForm.series"
                                        type="text"
                                        class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                        placeholder="Optional"
                                    />
                                </label>

                                <label class="block text-sm font-bold text-slate-700">
                                    Semantic map template
                                    <select
                                        v-model="pvsystForm.mapping_template"
                                        class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                    >
                                        <option value="pvsyst_module_component" :disabled="pvsystForm.device_type !== 'module'">PVSyst Module Component</option>
                                        <option value="pvsyst_inverter_component" :disabled="pvsystForm.device_type !== 'inverter'">PVSyst Inverter Component</option>
                                    </select>
                                </label>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <button type="button" class="rounded-md px-4 py-2 text-sm font-black" :class="pvsystForm.input_type === 'paste' ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700'" @click="pvsystForm.input_type = 'paste'">
                                    Paste CSV / table
                                </button>
                                <button type="button" class="rounded-md px-4 py-2 text-sm font-black" :class="pvsystForm.input_type === 'xlsx' ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700'" @click="pvsystForm.input_type = 'xlsx'">
                                    Upload components.xlsx
                                </button>
                            </div>

                            <div v-if="pvsystForm.input_type === 'paste'" class="mt-5">
                                <label class="block text-sm font-bold text-slate-700">
                                    PVSyst component data
                                    <textarea
                                        v-model="pvsystForm.pvsyst_data"
                                        class="mt-2 min-h-44 w-full rounded-md border border-slate-200 px-3 py-2 font-mono text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                                        placeholder="Paste CSV, semicolon, tab-delimited, or data-only rows..."
                                    />
                                </label>
                                <p v-if="pvsystForm.errors.pvsyst_data" class="mt-1 text-xs font-bold text-red-700">{{ pvsystForm.errors.pvsyst_data }}</p>
                            </div>

                            <div v-else class="mt-5 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                                <FileSpreadsheet class="mx-auto size-8 text-emerald-700" />
                                <h3 class="mt-3 font-black text-slate-950">Upload PVSyst components.xlsx</h3>
                                <p class="mt-1 text-sm text-slate-600">Labels and values are auto-mapped where present; sequence fallback uses the selected template.</p>
                                <input ref="pvsystFileInput" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden" @change="onPvsystFileChange" />
                                <button type="button" class="mt-4 rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white" @click="choosePvsystFile">
                                    Choose XLSX
                                </button>
                                <p v-if="selectedPvsystFileName" class="mt-3 text-sm font-semibold text-slate-700">{{ selectedPvsystFileName }}</p>
                                <p v-if="pvsystForm.errors.pvsyst_file" class="mt-1 text-xs font-bold text-red-700">{{ pvsystForm.errors.pvsyst_file }}</p>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-black text-slate-950">Preview Mapping</h2>
                            <p class="mt-1 text-sm text-slate-600">Sequence-number mapping is used for data-only PVSyst exports. XLSX labels override this map when detected.</p>
                            <div class="mt-4 overflow-hidden rounded-lg border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3">Position</th>
                                            <th class="px-4 py-3">Canonical field</th>
                                            <th class="px-4 py-3">Unit</th>
                                            <th class="px-4 py-3">Preview value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <tr v-for="field in pvsystTemplateFields" :key="field.key">
                                            <td class="px-4 py-3 font-mono text-slate-500">{{ field.sequence }}</td>
                                            <td class="px-4 py-3 font-bold text-slate-900">{{ field.label }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ field.unit || '—' }}</td>
                                            <td class="px-4 py-3 text-slate-700">{{ field.value || '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <button
                                type="submit"
                                class="mt-5 inline-flex items-center gap-2 rounded-md bg-slate-950 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 disabled:cursor-wait disabled:opacity-60"
                                :disabled="pvsystForm.processing || !pvsystForm.manufacturer || !pvsystForm.model_name || (pvsystForm.input_type === 'paste' ? !pvsystForm.pvsyst_data : !pvsystForm.pvsyst_file)"
                            >
                                <ShieldCheck class="size-4" />
                                {{ pvsystForm.processing ? 'Importing...' : 'Create Engineering Record' }}
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="space-y-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <Info class="size-5 text-sky-700" />
                        <h2 class="mt-4 font-bold text-slate-950">Workflow Status</h2>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <p class="rounded-md bg-emerald-50 p-3 font-semibold text-emerald-800">
                                Destination: {{ props.destinationLabel }}
                            </p>
                            <p class="rounded-md bg-slate-50 p-3">
                                Security checks run before permanent storage. Accepted files are queued for compilation.
                            </p>
                            <p class="rounded-md bg-slate-50 p-3">
                                Malware scanner:
                                <span class="font-bold">{{ props.malwareScanEnabled ? 'enabled' : 'local/dev hook' }}</span>
                            </p>
                        </div>
                    </div>

                    <EntitlementDiagnostics />
                </aside>
            </section>
        </main>
    </div>
</template>
