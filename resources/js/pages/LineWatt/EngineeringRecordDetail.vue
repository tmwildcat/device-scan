<script setup lang="ts">
import EntitlementDiagnostics from '@/components/linewatt/EntitlementDiagnostics.vue';
import EngineeringFieldTable from '@/components/linewatt/EngineeringFieldTable.vue';
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import PlaceholderAction from '@/components/linewatt/PlaceholderAction.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Head, Link } from '@inertiajs/vue3';
import { Download, GitCompare, ShieldCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type TableRow = {
    model?: string;
    field: string;
    value: string;
    unit?: string;
    normalized?: string;
    confidence?: string;
    page?: string;
    section?: string;
    sourceText?: string;
};

const props = defineProps<{
    record: any;
    libraryDebug: boolean;
    pdfPolicy?: any;
    exportOptions?: Array<{
        label: string;
        format: string;
        enabled: boolean;
        reason?: string | null;
        href: string;
    }>;
    compiledSummary?: {
        available: boolean;
        message?: string;
        manufacturer?: string | null;
        series?: string | null;
        model_count?: number | null;
        has_electrical?: boolean;
        has_validation?: boolean;
        top_level_sections?: string[];
    } | null;
    compiledRecord?: {
        overview?: Record<string, any>;
        electrical?: Record<string, any>;
        general?: Record<string, any>;
        protection?: any;
        warranty?: any;
        applications?: any;
        validation?: any;
        source?: Record<string, any>;
        raw_json?: Record<string, any>;
    } | null;
}>();

const { dir, t } = useLineWattI18n();
const tabs = [
    'Overview',
    'Electrical',
    'Mechanical / General',
    'Operating',
    'Protection',
    'Warranty',
    'Applications',
    'Validation',
    'Source',
    'Downloads',
    'Comparison',
];

const activeTab = ref('Overview');
const rawJson = computed(() => (props.libraryDebug ? props.compiledRecord?.raw_json ?? null : null));
const isModule = computed(() => props.record.device_type === 'module');
const isInverter = computed(() => props.record.device_type === 'inverter');
const exportOptions = computed(() => props.exportOptions ?? []);
const datasheetExport = computed(() => exportOptions.value.find((option) => option.format === 'datasheet'));
const pdfPolicy = computed(() => props.pdfPolicy ?? null);

function hasData(value: any): boolean {
    if (value === null || value === undefined) {
        return false;
    }

    if (Array.isArray(value)) {
        return value.length > 0;
    }

    if (typeof value === 'object') {
        return Object.entries(value).some(([key, nestedValue]) => key !== 'metadata' && hasData(nestedValue));
    }

    return value !== '';
}

function pretty(value: any): string {
    return JSON.stringify(value, null, 2);
}

function titleize(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function isSourceValue(value: any): boolean {
    return Boolean(
        value
            && typeof value === 'object'
            && !Array.isArray(value)
            && ('value' in value || 'source_text' in value || 'source_page' in value || 'confidence' in value),
    );
}

function displayValue(value: any): string {
    if (value === null || value === undefined || value === '') {
        return t('publicManufacturer.pending');
    }

    if (typeof value === 'boolean') {
        return value ? t('results.yes') : t('detail.no');
    }

    if (Array.isArray(value)) {
        return value.map((item) => displayValue(item)).join(', ');
    }

    if (typeof value === 'object') {
        if (isSourceValue(value)) {
            return displayValue(value.value ?? value.normalized_value);
        }

        return Object.entries(value)
            .filter(([key]) => key !== 'metadata')
            .map(([key, nestedValue]) => `${titleize(key)}: ${displayValue(nestedValue)}`)
            .join(' | ');
    }

    return String(value);
}

function rowFromValue(field: string, value: any, model?: string): TableRow | null {
    if (!hasData(value) || field === 'metadata') {
        return null;
    }

    if (isSourceValue(value)) {
        return {
            model,
            field: titleize(field),
            value: displayValue(value.value ?? value.normalized_value),
            unit: value.unit ?? '',
            normalized: value.normalized_value !== null && value.normalized_value !== undefined ? displayValue(value.normalized_value) : '',
            confidence: value.confidence !== null && value.confidence !== undefined ? `${Math.round(Number(value.confidence) * 100)}%` : '',
            page: value.source_page !== null && value.source_page !== undefined ? String(value.source_page) : '',
            section: value.source_section ? titleize(String(value.source_section)) : '',
            sourceText: value.source_text ? String(value.source_text) : '',
        };
    }

    return {
        model,
        field: titleize(field),
        value: displayValue(value),
    };
}

function modelName(model: any): string {
    return model?.model
        || model?.display_name
        || model?.model_name
        || model?.model_series
        || (model?.power_class_w ? `${model.power_class_w} W` : '')
        || t('detail.record');
}

function tabLabel(tab: string): string {
    const labels: Record<string, string> = {
        Overview: t('detail.overview'),
        Electrical: t('detail.electrical'),
        'Mechanical / General': t('detail.mechanicalGeneral'),
        Operating: t('detail.operating'),
        Protection: t('detail.protection'),
        Warranty: t('detail.warranty'),
        Applications: t('detail.applications'),
        Validation: t('detail.validation'),
        Source: t('compare.source'),
        Downloads: t('detail.downloads'),
        Comparison: t('nav.compare'),
    };

    return labels[tab] || tab;
}

function rowsFromSection(section: any): TableRow[] {
    if (!hasData(section)) {
        return [];
    }

    if (Array.isArray(section)) {
        return section
            .flatMap((item, index) => rowsFromSection({ [`item_${index + 1}`]: item }))
            .filter(Boolean);
    }

    if (section?.models && Array.isArray(section.models)) {
        return section.models.flatMap((model: any) => {
            const fields = model.fields ?? model;
            return Object.entries(fields)
                .filter(([key]) => !['metadata', 'model', 'display_name', 'model_name', 'model_series', 'model_variants', 'power_class_w'].includes(key))
                .map(([key, value]) => rowFromValue(key, value, modelName(model)))
                .filter((row): row is TableRow => row !== null);
        });
    }

    if (typeof section === 'object') {
        return Object.entries(section)
            .filter(([key]) => key !== 'metadata')
            .map(([key, value]) => rowFromValue(key, value))
            .filter((row): row is TableRow => row !== null);
    }

    return [rowFromValue('value', section)].filter((row): row is TableRow => row !== null);
}

function overviewRows(value: Record<string, any> | undefined | null): TableRow[] {
    if (!value) {
        return [];
    }

    return Object.entries(value)
        .map(([key, nestedValue]) => rowFromValue(key, nestedValue))
        .filter((row): row is TableRow => row !== null);
}

function validationRows(validation: any): Array<Record<string, string>> {
    const issues = validation?.issues;
    if (!Array.isArray(issues)) {
        return [];
    }

    return issues.map((issue: any) => ({
        severity: displayValue(issue.severity),
        code: displayValue(issue.code),
        message: displayValue(issue.message),
        model: displayValue(issue.model ?? issue.model_name),
        field: displayValue(issue.field),
        value: displayValue(issue.value),
        source: displayValue(issue.context ?? issue.source),
    }));
}

function sourceRows(source: Record<string, any> | undefined | null): TableRow[] {
    if (!source) {
        return [];
    }

    const rows: TableRow[] = [];
    for (const [section, value] of Object.entries(source)) {
        if (section === 'raw_json' || !hasData(value)) {
            continue;
        }

        if (Array.isArray(value)) {
            rows.push({ field: titleize(section), value: value.map((item) => displayValue(item)).join(', ') });
        } else if (typeof value === 'object') {
            for (const [key, nestedValue] of Object.entries(value)) {
                const row = rowFromValue(`${section}_${key}`, nestedValue);
                if (row) {
                    rows.push(row);
                }
            }
        } else {
            rows.push({ field: titleize(section), value: displayValue(value) });
        }
    }

    return rows;
}
</script>

<template>
    <Head :title="record.display_name || t('compare.engineeringRecord')" />

    <div class="min-h-screen bg-slate-50 text-slate-950" :dir="dir">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="mb-6">
                <Link href="/search" class="text-sm font-bold text-emerald-700 hover:text-emerald-800">
                    {{ t('detail.backToSearch') }}
                </Link>
            </div>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">
                            {{ t('compare.engineeringRecord') }}
                        </p>
                        <h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">
                            {{ record.display_name || record.model_name || record.model_series || t('compare.engineeringRecord') }}
                        </h1>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ record.manufacturer || t('myLibrary.unknownManufacturer') }}
                            <span v-if="record.series"> · {{ record.series }}</span>
                            <span v-if="record.device_type"> · {{ record.device_type.replace('_', ' ') }}</span>
                        </p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <LifecycleStatusBadge :status="record.status" />
                        </div>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a
                                v-if="datasheetExport?.enabled"
                                :href="datasheetExport.href"
                                class="inline-flex items-center gap-2 rounded-md bg-slate-950 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800"
                            >
                                <Download class="size-4" />
                                {{ t('detail.downloadPdf') }}
                            </a>
                            <button
                                v-else
                                type="button"
                                class="inline-flex cursor-not-allowed items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-400"
                                :title="datasheetExport?.reason || t('detail.signInToDownload')"
                                disabled
                            >
                                <Download class="size-4" />
                                {{ t('detail.downloadPdf') }}
                            </button>
                            <PlaceholderAction :label="t('nav.compare')" />
                            <PlaceholderAction :label="t('manufacturer.review')" />
                        </div>
                        <div
                            v-if="pdfPolicy"
                            class="mt-5 max-w-2xl rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.12em] text-slate-600">
                                    {{ t('compare.source') }}: {{ pdfPolicy.source_label }}
                                </span>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.12em] text-slate-600">
                                    PDF: {{ pdfPolicy.pdf_label }}
                                </span>
                            </div>
                            <p v-if="!datasheetExport?.enabled && pdfPolicy.source_url" class="mt-3 text-slate-600">
                                {{ t('detail.hostedPdfUnavailable') }}
                                <a
                                    :href="pdfPolicy.source_url"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="font-bold text-emerald-700 hover:text-emerald-800"
                                >
                                    {{ t('detail.viewAtManufacturer') }}
                                </a>.
                            </p>
                            <p v-else-if="!datasheetExport?.enabled" class="mt-3 text-slate-600">
                                {{ t('detail.pdfRestricted') }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h2 class="text-sm font-bold uppercase tracking-[0.16em] text-slate-500">
                            {{ t('detail.identity') }}
                        </h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">{{ t('search.modelSeries') }}</dt>
                                <dd class="text-right font-semibold text-slate-900">{{ record.model_series || t('publicManufacturer.pending') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">{{ t('compare.modelName') }}</dt>
                                <dd class="text-right font-semibold text-slate-900">{{ record.model_name || t('publicManufacturer.pending') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">{{ t('manufacturerProducts.power') }}</dt>
                                <dd class="text-right font-semibold text-slate-900">
                                    <span v-if="record.power_class_w">{{ record.power_class_w }} W</span>
                                    <span v-else-if="record.power_class_kw">{{ record.power_class_kw }} kW</span>
                                    <span v-else>{{ t('publicManufacturer.pending') }}</span>
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">{{ t('search.technology') }}</dt>
                                <dd class="text-right font-semibold text-slate-900">{{ record.technology || t('publicManufacturer.pending') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </section>

            <div class="mt-6">
                <EntitlementDiagnostics />
            </div>

            <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50 p-2">
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        class="shrink-0 rounded-md px-3 py-2 text-sm font-semibold"
                        :class="activeTab === tab ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-white hover:text-slate-950'"
                        type="button"
                        @click="activeTab = tab"
                    >
                        {{ tabLabel(tab) }}
                    </button>
                </div>

                <div class="p-6">
                    <div v-if="activeTab === 'Overview'" class="grid gap-6 lg:grid-cols-3">
                        <div class="rounded-lg border border-slate-200 p-5">
                            <ShieldCheck class="size-5 text-emerald-700" />
                            <h3 class="mt-4 font-bold">{{ t('detail.compiledSummary') }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ t('detail.compiledSummaryHelp') }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-5 lg:col-span-2">
                            <table v-if="overviewRows(compiledRecord?.overview).length" class="min-w-full divide-y divide-slate-200 text-sm">
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="row in overviewRows(compiledRecord?.overview)" :key="row.field">
                                        <th class="w-56 py-3 pr-4 text-left font-semibold text-slate-500">{{ row.field }}</th>
                                        <td class="py-3 font-semibold text-slate-900">{{ row.value }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p v-else class="text-sm leading-6 text-slate-600">
                                {{ compiledSummary?.message || t('detail.noCompiledSummary') }}
                            </p>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'Electrical'" class="space-y-6">
                        <section v-if="isModule" class="rounded-lg border border-slate-200 p-5">
                            <h3 class="font-bold text-slate-950">{{ t('detail.stcElectrical') }}</h3>
                            <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.electrical?.electrical_stc)" />
                        </section>

                        <template v-if="isInverter">
                            <section class="rounded-lg border border-slate-200 p-5">
                                <h3 class="font-bold text-slate-950">{{ t('detail.dcInput') }}</h3>
                                <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.electrical?.dc_input)" />
                            </section>
                            <section class="rounded-lg border border-slate-200 p-5">
                                <h3 class="font-bold text-slate-950">{{ t('detail.acOutput') }}</h3>
                                <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.electrical?.ac_output)" />
                            </section>
                            <section class="rounded-lg border border-slate-200 p-5">
                                <h3 class="font-bold text-slate-950">{{ t('detail.ratedPowerConditions') }}</h3>
                                <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.electrical?.rated_power_conditions)" />
                            </section>
                        </template>
                    </div>

                    <div v-else-if="activeTab === 'Mechanical / General'" class="space-y-6">
                        <section class="rounded-lg border border-slate-200 p-5">
                            <h3 class="font-bold text-slate-950">{{ isInverter ? t('detail.generalCentralSpecific') : t('detail.mechanical') }}</h3>
                            <EngineeringFieldTable :rows="rowsFromSection(isInverter ? compiledRecord?.general?.central_specific : compiledRecord?.general?.mechanical)" />
                        </section>
                        <section v-if="isModule" class="rounded-lg border border-slate-200 p-5">
                            <h3 class="font-bold text-slate-950">{{ t('publicManufacturer.certifications') }}</h3>
                            <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.general?.certifications)" />
                        </section>
                        <section v-if="isModule" class="rounded-lg border border-slate-200 p-5">
                            <h3 class="font-bold text-slate-950">{{ t('detail.packaging') }}</h3>
                            <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.general?.packaging)" />
                        </section>
                    </div>

                    <div v-else-if="activeTab === 'Operating'" class="space-y-6">
                        <section class="rounded-lg border border-slate-200 p-5">
                            <h3 class="font-bold text-slate-950">{{ t('detail.operatingConditions') }}</h3>
                            <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.general?.operating_conditions)" />
                        </section>
                        <section class="rounded-lg border border-slate-200 p-5">
                            <h3 class="font-bold text-slate-950">{{ t('detail.temperatureCharacteristics') }}</h3>
                            <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.general?.temperature_characteristics)" />
                        </section>
                    </div>

                    <section v-else-if="activeTab === 'Protection'" class="rounded-lg border border-slate-200 p-5">
                        <h3 class="font-bold text-slate-950">{{ t('detail.protection') }}</h3>
                        <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.protection)" />
                    </section>

                    <section v-else-if="activeTab === 'Warranty'" class="rounded-lg border border-slate-200 p-5">
                        <h3 class="font-bold text-slate-950">{{ t('detail.warranty') }}</h3>
                        <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.warranty)" />
                    </section>

                    <section v-else-if="activeTab === 'Applications'" class="rounded-lg border border-slate-200 p-5">
                        <h3 class="font-bold text-slate-950">{{ t('detail.applications') }}</h3>
                        <EngineeringFieldTable :rows="rowsFromSection(compiledRecord?.applications)" />
                    </section>

                    <section v-else-if="activeTab === 'Validation'" class="rounded-lg border border-slate-200 p-5">
                        <h3 class="font-bold text-slate-950">{{ t('detail.validationIssues') }}</h3>
                        <div v-if="validationRows(compiledRecord?.validation).length" class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                                    <tr>
                                        <th class="px-3 py-3">{{ t('detail.severity') }}</th>
                                        <th class="px-3 py-3">{{ t('detail.code') }}</th>
                                        <th class="px-3 py-3">{{ t('detail.message') }}</th>
                                        <th class="px-3 py-3">{{ t('compare.modelName') }}</th>
                                        <th class="px-3 py-3">{{ t('compare.field') }}</th>
                                        <th class="px-3 py-3">{{ t('detail.value') }}</th>
                                        <th class="px-3 py-3">{{ t('compare.source') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(issue, index) in validationRows(compiledRecord?.validation)" :key="index">
                                        <td class="px-3 py-3 font-semibold">{{ issue.severity }}</td>
                                        <td class="px-3 py-3">{{ issue.code }}</td>
                                        <td class="px-3 py-3">{{ issue.message }}</td>
                                        <td class="px-3 py-3">{{ issue.model }}</td>
                                        <td class="px-3 py-3">{{ issue.field }}</td>
                                        <td class="px-3 py-3">{{ issue.value }}</td>
                                        <td class="px-3 py-3">{{ issue.source }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="mt-3 text-sm text-slate-600">{{ t('detail.noValidationIssues') }}</p>
                    </section>

                    <div v-else-if="activeTab === 'Source'" class="space-y-5">
                        <section class="rounded-lg border border-slate-200 p-5">
                            <h3 class="font-bold text-slate-950">{{ t('detail.sourceProvenance') }}</h3>
                            <EngineeringFieldTable :rows="sourceRows(compiledRecord?.source)" />
                        </section>
                        <details v-if="rawJson" class="rounded-lg border border-dashed border-slate-300 p-5">
                            <summary class="cursor-pointer select-none font-bold text-slate-950">{{ t('detail.rawEngineeringData') }}</summary>
                            <pre class="mt-4 max-h-[520px] overflow-auto rounded-md bg-slate-950 p-4 text-xs leading-5 text-slate-100">{{ pretty(rawJson) }}</pre>
                        </details>
                    </div>

                    <div v-else-if="activeTab === 'Downloads'" class="rounded-lg border border-slate-200 p-5">
                        <div class="flex items-start gap-3">
                            <Download class="mt-1 size-5 text-emerald-700" />
                            <div>
                                <h3 class="font-bold text-slate-950">{{ t('detail.downloadsExports') }}</h3>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ t('detail.downloadsHelp') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 overflow-hidden rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3">{{ t('detail.format') }}</th>
                                        <th class="px-4 py-3">{{ t('detail.access') }}</th>
                                        <th class="px-4 py-3 text-right">{{ t('manufacturerProducts.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="option in exportOptions" :key="option.format">
                                        <td class="px-4 py-3 font-bold text-slate-950">{{ option.label }}</td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <span v-if="option.enabled" class="font-semibold text-emerald-700">{{ t('detail.available') }}</span>
                                            <span v-else>{{ option.reason || t('detail.unavailable') }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a
                                                v-if="option.enabled"
                                                :href="option.href"
                                                class="inline-flex rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800"
                                            >
                                                {{ t('detail.download') }}
                                            </a>
                                            <button
                                                v-else
                                                type="button"
                                                class="inline-flex cursor-not-allowed rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black text-slate-400"
                                                :title="option.reason || t('detail.unavailable')"
                                                disabled
                                            >
                                                {{ t('detail.locked') }}
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'Comparison'" class="rounded-lg border border-dashed border-slate-300 p-8 text-sm text-slate-600">
                        <GitCompare class="mb-3 size-5 text-slate-500" />
                        {{ t('detail.comparisonHelp') }}
                        <div class="mt-4">
                            <Link
                                :href="`/compare/select?seed=${record.id || record.uuid}`"
                                class="inline-flex rounded-md bg-slate-950 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800"
                            >
                                {{ t('detail.compareThisRecord') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
