<script setup lang="ts">
import PdfViewer from '@/device-scan/components/pdf/PdfViewer.vue';
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import QualityGradeBadge from '@/components/linewatt/QualityGradeBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, FileText, PencilLine, Save, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type ReviewRow = {
    path: string;
    field: string;
    value: string;
    unit?: string;
    normalized?: string;
    confidence?: string;
    page?: string;
    section?: string;
    sourceText?: string;
    readonly?: boolean;
};

type ReviewSection = {
    key: string;
    title: string;
    rows: ReviewRow[];
    readonly?: boolean;
};

const props = defineProps<{
    workspace: 'central' | 'my-library' | 'partner' | 'manufacturer' | 'publisher';
    workspaceLabel: string;
    record: any;
    compiledSummary?: any;
    presentation?: any;
    pdfPolicy?: {
        access_mode: string;
        source_url?: string | null;
        source_label: string;
        pdf_label: string;
        can_embed: boolean;
        preview_url?: string | null;
    } | null;
    reviewArtifact?: any;
    reviewComments?: Array<{ id: number; action: string; comment?: string | null; actor: string; created_at?: string | null }>;
    libraryDebug: boolean;
    routes: {
        sourcePdf?: string | null;
        save: string;
        submit?: string | null;
        approve?: string | null;
        reject?: string | null;
        requestChanges?: string | null;
        back: string;
    };
}>();

const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>();

function hasData(value: any): boolean {
    if (value === null || value === undefined || value === '') return false;
    if (Array.isArray(value)) return value.length > 0;
    if (typeof value === 'object') return Object.entries(value).some(([key, nested]) => key !== 'metadata' && hasData(nested));
    return true;
}

function titleize(value: string): string {
    return value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function isSourceValue(value: any): boolean {
    return Boolean(value && typeof value === 'object' && !Array.isArray(value) && ('value' in value || 'source_text' in value || 'source_page' in value || 'confidence' in value));
}

function displayValue(value: any): string {
    if (value === null || value === undefined || value === '') return '';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (Array.isArray(value)) return value.map((item) => displayValue(item)).join(', ');
    if (typeof value === 'object') {
        if (isSourceValue(value)) return displayValue(value.value ?? value.normalized_value);
        return JSON.stringify(value);
    }
    return String(value);
}

function confidence(value: any): string {
    return value !== null && value !== undefined && value !== '' && !Number.isNaN(Number(value))
        ? `${Math.round(Number(value) * 100)}%`
        : '';
}

function rowFromValue(path: string, field: string, value: any, readonly = false): ReviewRow | null {
    if (!hasData(value) || field === 'metadata') return null;

    if (isSourceValue(value)) {
        return {
            path,
            field: titleize(field),
            value: displayValue(value.value ?? value.normalized_value),
            unit: value.unit ?? '',
            normalized: value.normalized_value !== null && value.normalized_value !== undefined ? displayValue(value.normalized_value) : '',
            confidence: confidence(value.confidence),
            page: value.source_page !== null && value.source_page !== undefined ? String(value.source_page) : '',
            section: value.source_section ? titleize(String(value.source_section)) : '',
            sourceText: value.source_text ? String(value.source_text) : '',
            readonly,
        };
    }

    return {
        path,
        field: titleize(field),
        value: displayValue(value),
        readonly,
    };
}

function rowsFromObject(basePath: string, object: any, readonly = false): ReviewRow[] {
    if (!hasData(object)) return [];

    if (Array.isArray(object)) {
        return object.flatMap((item, index) => rowsFromObject(`${basePath}.${index}`, item, readonly));
    }

    if (typeof object === 'object') {
        if (object?.models && Array.isArray(object.models)) {
            return object.models.flatMap((model: any, modelIndex: number) => {
                const modelName = model.display_name || model.model_name || model.model_series || `Model ${modelIndex + 1}`;
                return Object.entries(model)
                    .filter(([key]) => !['metadata', 'model_variants'].includes(key))
                    .map(([key, value]) => rowFromValue(`${basePath}.models.${modelIndex}.${key}`, `${modelName} / ${key}`, value, readonly))
                    .filter((row): row is ReviewRow => row !== null);
            });
        }

        return Object.entries(object)
            .filter(([key]) => key !== 'metadata')
            .flatMap(([key, value]) => {
                if (isSourceValue(value) || typeof value !== 'object' || value === null || Array.isArray(value)) {
                    const row = rowFromValue(`${basePath}.${key}`, key, value, readonly);
                    return row ? [row] : [];
                }

                return rowsFromObject(`${basePath}.${key}`, value, readonly);
            });
    }

    const row = rowFromValue(basePath, 'value', object, readonly);
    return row ? [row] : [];
}

function validationRows(validation: any): ReviewRow[] {
    const issues = validation?.issues;
    if (!Array.isArray(issues)) return [];

    return issues.map((issue: any, index: number) => ({
        path: `validation.issues.${index}`,
        field: `${displayValue(issue.severity)} / ${displayValue(issue.code)}`,
        value: displayValue(issue.message),
        section: displayValue(issue.field),
        sourceText: displayValue(issue.context ?? issue.source),
        readonly: true,
    }));
}

function buildSections(): ReviewSection[] {
    const overview = {
        manufacturer: props.record.manufacturer,
        display_name: props.record.display_name,
        model_series: props.record.model_series,
        model_name: props.record.model_name,
        device_type: props.record.device_type,
        technology: props.record.technology,
        power_class_w: props.record.power_class_w,
        power_class_kw: props.record.power_class_kw,
    };
    const electrical = props.presentation?.electrical || {};
    const general = props.presentation?.general || {};

    const sections: ReviewSection[] = [
        { key: 'identity', title: 'Identity', rows: rowsFromObject('identity', overview) },
    ];

    if (props.record.device_type === 'module') {
        sections.push(
            { key: 'electrical_stc', title: 'STC Electrical', rows: rowsFromObject('electrical.electrical_stc', electrical.electrical_stc) },
            { key: 'mechanical', title: 'Mechanical', rows: rowsFromObject('general.mechanical', general.mechanical) },
            { key: 'operating', title: 'Operating', rows: rowsFromObject('general.operating_conditions', general.operating_conditions) },
            { key: 'temperature', title: 'Temperature', rows: rowsFromObject('general.temperature_characteristics', general.temperature_characteristics) },
            { key: 'warranty', title: 'Warranty', rows: rowsFromObject('warranty', props.presentation?.warranty) },
            { key: 'certifications', title: 'Certifications', rows: rowsFromObject('general.certifications', general.certifications) },
        );
    } else {
        sections.push(
            { key: 'dc_input', title: 'DC Input', rows: rowsFromObject('electrical.dc_input', electrical.dc_input) },
            { key: 'ac_output', title: 'AC Output', rows: rowsFromObject('electrical.ac_output', electrical.ac_output) },
            { key: 'rated_power_conditions', title: 'Rated Power Conditions', rows: rowsFromObject('electrical.rated_power_conditions', electrical.rated_power_conditions) },
            { key: 'protection', title: 'Protection', rows: rowsFromObject('protection', props.presentation?.protection) },
            { key: 'central_specific', title: 'Central-Specific', rows: rowsFromObject('general.central_specific', general.central_specific) },
        );
    }

    sections.push({ key: 'validation', title: 'Validation Issues', rows: validationRows(props.presentation?.validation), readonly: true });

    return sections.filter((section) => section.rows.length > 0 || section.key === 'validation');
}

const form = useForm({
    sections: buildSections(),
    comment: '',
});

const correctionMode = ref(Boolean(props.reviewArtifact));
const rawJson = computed(() => (props.libraryDebug ? props.presentation?.raw_json ?? null : null));

function enableCorrectionMode(): void {
    correctionMode.value = true;
}

function cancelChanges(): void {
    form.defaults({
        sections: buildSections(),
    });
    form.reset();
    form.clearErrors();
    correctionMode.value = false;
}

function saveReview(): void {
    if (!correctionMode.value) return;

    form.patch(props.routes.save, {
        preserveScroll: true,
    });
}

function approve(): void {
    if (!props.routes.approve) return;
    form.post(props.routes.approve, { preserveScroll: true });
}

function reject(): void {
    if (!props.routes.reject) return;
    form.post(props.routes.reject, { preserveScroll: true });
}

function requestChanges(): void {
    if (!props.routes.requestChanges) return;
    form.post(props.routes.requestChanges, { preserveScroll: true });
}

function submitForApproval(): void {
    if (!props.routes.submit) return;
    form.post(props.routes.submit, { preserveScroll: true });
}
</script>

<template>
    <Head title="Review Engineering Record" />

    <component
        :is="workspace === 'manufacturer' ? ManufacturerAdminShell : workspace === 'central' ? LibraryAdminShell : 'div'"
        v-bind="workspace === 'manufacturer'
            ? {
                company: { name: record.manufacturer || 'Manufacturer', manufacturer_role_label: workspaceLabel },
                title: record.display_name || record.model_name || record.model_series || 'Engineering Review',
                subtitle: `${record.manufacturer || 'Unknown manufacturer'} · structured field review`,
                breadcrumbs: [{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Datasheets', href: routes.back }, { label: 'Review' }]
            }
            : workspace === 'central'
                ? {
                    title: record.display_name || record.model_name || record.model_series || 'Engineering Review',
                    subtitle: `${record.manufacturer || 'Unknown manufacturer'} · PDF preview, editable reviewed fields, comments, validation and approval actions.`,
                    breadcrumbs: [{ label: 'Dashboard', href: '/admin/library' }, { label: 'Review & Approval', href: routes.back }, { label: 'Review' }],
                    roleLabel: workspaceLabel
                }
            : { class: 'min-h-screen bg-slate-50 text-slate-950' }"
    >
        <WorkspaceNavigation v-if="workspace !== 'manufacturer' && workspace !== 'central'" />

        <main :class="workspace === 'manufacturer' || workspace === 'central' ? '' : 'mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8'">
            <div v-if="workspace !== 'manufacturer' && workspace !== 'central'" class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">{{ workspaceLabel }}</p>
                    <h1 class="mt-2 text-4xl font-black tracking-tight text-slate-950">
                        {{ record.display_name || record.model_name || record.model_series || 'Engineering Record' }}
                    </h1>
                    <p class="mt-2 text-slate-600">{{ record.manufacturer || 'Unknown manufacturer' }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <LifecycleStatusBadge :status="record.status" />
                        <ValidationStatusBadge :status="record.validation_status" />
                        <QualityGradeBadge :grade="record.validation_grade" :score="record.validation_score" />
                    </div>
                </div>

                <Link
                    :href="routes.back"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                >
                    <ArrowLeft class="size-4" />
                    {{ workspace === 'my-library' ? 'Back to My Private Datasets' : 'Back to workspace' }}
                </Link>
            </div>

            <div v-if="page.props.flash?.success" class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.flash?.error" class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">
                {{ page.props.flash.error }}
            </div>

            <section class="sticky top-0 z-30 mt-6 rounded-lg border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur">
                <div class="grid gap-3 2xl:grid-cols-[minmax(260px,0.75fr)_minmax(360px,1fr)_auto] 2xl:items-end">
                    <div class="flex flex-wrap items-center gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Review Actions</p>
                            <h2 class="mt-1 text-lg font-black text-slate-950">Decision Workflow</h2>
                        </div>
                        <div v-if="reviewArtifact || correctionMode" class="flex flex-wrap gap-2">
                            <span v-if="reviewArtifact" class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-black text-slate-700">Review saved</span>
                            <span v-if="correctionMode" class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-800">Needs Changes Active</span>
                        </div>
                    </div>

                    <label v-if="routes.reject || routes.requestChanges" class="block text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        Librarian comment
                        <textarea
                            v-model="form.comment"
                            rows="1"
                            class="mt-1 min-h-10 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold normal-case tracking-normal text-slate-800 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100"
                            placeholder="Required when rejecting or requesting changes."
                        />
                        <span v-if="form.errors.comment" class="mt-1 block text-xs font-bold normal-case tracking-normal text-red-700">{{ form.errors.comment }}</span>
                    </label>

                    <div class="flex flex-wrap gap-2 2xl:justify-end">
                        <button
                            class="inline-flex items-center justify-center gap-1.5 rounded-md border px-3 py-2 text-xs font-black disabled:opacity-50"
                            :class="correctionMode ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            :disabled="form.processing || correctionMode"
                            type="button"
                            @click="enableCorrectionMode"
                        >
                            <PencilLine class="size-3.5" />
                            {{ correctionMode ? 'Needs Changes Active' : 'Needs Changes' }}
                        </button>
                        <button
                            class="inline-flex items-center justify-center gap-1.5 rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800 disabled:opacity-50"
                            :disabled="form.processing || !correctionMode"
                            type="button"
                            @click="saveReview"
                            :title="correctionMode ? 'Save manual corrections.' : 'Use Needs Changes before editing or saving corrections.'"
                        >
                            <Save class="size-3.5" />
                            Save
                        </button>
                        <button
                            v-if="correctionMode"
                            class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                            :disabled="form.processing"
                            type="button"
                            @click="cancelChanges"
                        >
                            <X class="size-3.5" />
                            Cancel
                        </button>
                        <button
                            v-if="routes.submit"
                            class="rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-black text-sky-800 hover:bg-sky-100 disabled:opacity-50"
                            :disabled="form.processing"
                            type="button"
                            @click="submitForApproval"
                        >
                            Submit
                        </button>
                        <button
                            v-if="routes.approve"
                            class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-800 hover:bg-emerald-100 disabled:opacity-50"
                            :disabled="form.processing"
                            type="button"
                            @click="approve"
                        >
                            {{ workspace === 'central' ? 'Approve / Publish' : 'Approve' }}
                        </button>
                        <button
                            v-if="routes.reject"
                            class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-black text-red-800 hover:bg-red-100 disabled:opacity-50"
                            :disabled="form.processing"
                            type="button"
                            @click="reject"
                        >
                            Reject
                        </button>
                        <button
                            v-if="routes.requestChanges"
                            class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-black text-amber-800 hover:bg-amber-100 disabled:opacity-50"
                            :disabled="form.processing"
                            type="button"
                            @click="requestChanges"
                        >
                            Request Changes
                        </button>
                    </div>
                </div>
            </section>

            <section class="mt-8 grid gap-6 xl:grid-cols-[minmax(420px,0.95fr)_minmax(0,1.25fr)]">
                <aside class="space-y-4">
                    <div class="sticky top-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-black text-slate-950">Datasheet Preview</h2>
                                <p class="mt-1 text-sm text-slate-600">
                                    Source PDF for visual verification while editing extracted fields.
                                </p>
                            </div>
                            <a
                                v-if="routes.sourcePdf"
                                :href="routes.sourcePdf"
                                target="_blank"
                                class="rounded-md border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                            >
                                Open PDF
                            </a>
                        </div>
                        <div class="mt-4">
                            <PdfViewer v-if="pdfPolicy?.can_embed && (pdfPolicy.preview_url || routes.sourcePdf)" :src="pdfPolicy.preview_url || routes.sourcePdf || ''" />
                            <div
                                v-else
                                class="flex h-[520px] items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-center"
                            >
                                <div>
                                    <div class="text-lg font-semibold text-slate-700">{{ pdfPolicy?.source_url ? 'External source only' : 'PDF preview unavailable' }}</div>
                                    <p class="mt-2 max-w-sm text-sm text-slate-500">
                                        {{ pdfPolicy?.source_url ? 'Use the manufacturer source URL for the datasheet. Internal preview is disabled by PDF policy.' : 'The source datasheet artifact could not be resolved for preview.' }}
                                    </p>
                                    <a v-if="pdfPolicy?.source_url" :href="pdfPolicy.source_url" target="_blank" class="mt-4 inline-flex rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white">
                                        View datasheet at manufacturer website
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex items-start gap-3 rounded-md bg-slate-50 p-3 text-xs text-slate-600">
                            <FileText class="mt-0.5 size-4 shrink-0 text-slate-500" />
                            <div class="min-w-0">
                                <div class="font-bold text-slate-800">{{ presentation?.source?.datasheet?.original_filename || record.datasheet?.original_filename || 'Source datasheet' }}</div>
                                <div v-if="pdfPolicy" class="mt-1 font-bold">{{ pdfPolicy.source_label }} · {{ pdfPolicy.pdf_label }}</div>
                                <div class="mt-1 break-all">{{ presentation?.source?.datasheet?.path || 'Source path pending' }}</div>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="space-y-6">
                    <div v-if="reviewComments?.length || reviewArtifact" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black text-slate-950">Review History</h2>
                        <div v-if="reviewArtifact" class="mt-3 rounded-md bg-slate-50 p-3 text-xs text-slate-600">
                            Existing review artifact: <span class="font-semibold break-all">{{ reviewArtifact.path }}</span>
                        </div>
                        <div v-if="reviewComments?.length" class="mt-4 space-y-2">
                            <article v-for="comment in reviewComments" :key="comment.id" class="rounded-md border border-slate-100 bg-slate-50 p-3 text-xs">
                                <div class="flex flex-wrap items-center justify-between gap-2 font-bold text-slate-800">
                                    <span>{{ comment.action.replaceAll('_', ' ') }}</span>
                                    <span class="text-slate-500">{{ comment.actor }} · {{ comment.created_at }}</span>
                                </div>
                                <p v-if="comment.comment" class="mt-1 text-slate-600">{{ comment.comment }}</p>
                            </article>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <form class="space-y-6" @submit.prevent="saveReview">
                            <div
                                v-for="(section, sectionIndex) in form.sections"
                                :key="section.key"
                                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <h2 class="text-lg font-black text-slate-950">{{ section.title }}</h2>
                                    <span v-if="section.readonly" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">Read-only</span>
                                </div>

                                <div v-if="section.rows.length" class="mt-4 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                                            <tr>
                                                <th class="px-3 py-3">Field</th>
                                                <th class="px-3 py-3">Value</th>
                                                <th class="px-3 py-3">Unit</th>
                                                <th class="px-3 py-3">Normalized</th>
                                                <th class="px-3 py-3">Confidence</th>
                                                <th class="px-3 py-3">Page</th>
                                                <th class="px-3 py-3">Section</th>
                                                <th class="px-3 py-3">Source Text</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr v-for="(row, rowIndex) in section.rows" :key="`${section.key}-${row.path}-${rowIndex}`">
                                                <td class="min-w-52 px-3 py-3 font-semibold text-slate-900">{{ row.field }}</td>
                                                <td class="min-w-64 px-3 py-3">
                                                    <textarea
                                                        v-model="form.sections[sectionIndex].rows[rowIndex].value"
                                                        class="min-h-10 w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 disabled:bg-slate-50 disabled:text-slate-500"
                                                        :disabled="!correctionMode || section.readonly || row.readonly"
                                                    />
                                                </td>
                                                <td class="px-3 py-3">{{ row.unit || '' }}</td>
                                                <td class="px-3 py-3">{{ row.normalized || '' }}</td>
                                                <td class="px-3 py-3">{{ row.confidence || '' }}</td>
                                                <td class="px-3 py-3">{{ row.page || '' }}</td>
                                                <td class="px-3 py-3">{{ row.section || '' }}</td>
                                                <td class="max-w-md whitespace-normal px-3 py-3 text-slate-600">{{ row.sourceText || '' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-else class="mt-3 text-sm text-slate-600">No data found in this section.</p>
                            </div>
                        </form>

                        <details v-if="rawJson" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <summary class="cursor-pointer text-sm font-black text-slate-950">Debug Raw JSON</summary>
                            <pre class="mt-4 max-h-[520px] overflow-auto rounded-md bg-slate-950 p-4 text-xs text-slate-100">{{ JSON.stringify(rawJson, null, 2) }}</pre>
                        </details>
                    </div>
                </div>
            </section>
        </main>
    </component>
</template>
