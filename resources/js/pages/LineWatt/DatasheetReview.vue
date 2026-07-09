<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import PdfViewer from '@/device-scan/components/pdf/PdfViewer.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, ClipboardCheck, DatabaseZap, FileText, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    workspace: 'central' | 'manufacturer';
    workspaceLabel: string;
    roleLabel?: string | null;
    company?: any;
    datasheet: {
        id: number;
        manufacturer?: string | null;
        title: string;
        family_series?: string | null;
        revision?: string | null;
        language?: string | null;
        publication_date?: string | null;
        source_type: string;
        uploaded_by?: string | number | null;
        uploaded_at?: string | null;
        status: string;
        review_status?: string | null;
        notes?: string | null;
        manufacturer_mismatch?: boolean;
        detected_manufacturer?: string | null;
    };
    summary: Record<string, any>;
    pdfPolicy?: {
        access_mode: string;
        source_url?: string | null;
        source_domain?: string | null;
        permission_status?: string | null;
        can_embed: boolean;
        preview_url?: string | null;
        can_public_download: boolean;
        can_private_download: boolean;
        source_label: string;
        pdf_label: string;
        external_only: boolean;
    } | null;
    coverage: Record<string, boolean>;
    warnings: Array<{
        severity: string;
        code: string;
        message: string;
        record?: string | null;
    }>;
    records: Array<any>;
    routes: {
        sourcePdf: string;
        save: string;
        submit?: string | null;
        approve?: string | null;
        reject?: string | null;
        requestChanges?: string | null;
        back: string;
    };
}>();

const notesForm = useForm({
    notes: props.datasheet.notes || '',
});

const commentForm = useForm({
    comment: '',
});

const reviewScope = ref<'datasheet' | 'engineering-data'>('datasheet');

const statusClass = (status?: string | null) => {
    const normalized = (status || '').toLowerCase();

    if (['published', 'approved', 'librarian_reviewed', 'publisher_reviewed'].includes(normalized)) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    }

    if (['submitted', 'submitted_for_approval', 'review_required', 'pending_review'].includes(normalized)) {
        return 'border-amber-200 bg-amber-50 text-amber-800';
    }

    if (['rejected', 'changes_requested', 'errors'].includes(normalized)) {
        return 'border-rose-200 bg-rose-50 text-rose-800';
    }

    return 'border-slate-200 bg-slate-50 text-slate-700';
};

const coverageLabel = (key: string) => key.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

function saveReview(): void {
    notesForm.patch(props.routes.save, { preserveScroll: true });
}

function submitForApproval(): void {
    if (props.routes.submit) {
        notesForm.post(props.routes.submit, { preserveScroll: true });
    }
}

function approve(): void {
    if (props.routes.approve) {
        commentForm.post(props.routes.approve, { preserveScroll: true });
    }
}

function reject(): void {
    if (props.routes.reject) {
        commentForm.post(props.routes.reject, { preserveScroll: true });
    }
}

function requestChanges(): void {
    if (props.routes.requestChanges) {
        commentForm.post(props.routes.requestChanges, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="`Review Compilation · ${datasheet.title}`" />

    <component
        :is="workspace === 'manufacturer' ? ManufacturerAdminShell : LibraryAdminShell"
        :company="company"
        :title="'Review Compilation'"
        :subtitle="'Datasheet Compilation Summary before model-level Structured Engineering Data review.'"
        :role-label="roleLabel"
        :breadcrumbs="workspace === 'manufacturer'
            ? [{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Datasheets', href: '/admin/manufacturer/datasheets' }, { label: 'Review Compilation' }]
            : [{ label: 'Dashboard', href: '/admin/library' }, { label: 'Datasheets', href: '/admin/library/datasheets?view=all' }, { label: 'Review Compilation' }]"
    >
        <section class="sticky top-0 z-30 mb-6 rounded-lg border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur">
            <div class="grid gap-3 2xl:grid-cols-[minmax(220px,0.65fr)_minmax(300px,0.75fr)_minmax(360px,1fr)_auto] 2xl:items-end">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Review Actions</p>
                        <h2 class="mt-1 text-lg font-black text-slate-950">Decision Workflow</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border px-2.5 py-1 text-xs font-black" :class="statusClass(datasheet.status)">
                            {{ datasheet.status.replaceAll('_', ' ') }}
                        </span>
                        <span v-if="datasheet.review_status" class="rounded-full border px-2.5 py-1 text-xs font-black" :class="statusClass(datasheet.review_status)">
                            {{ datasheet.review_status.replaceAll('_', ' ') }}
                        </span>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Review Scope</p>
                    <div class="mt-1 grid gap-2 sm:grid-cols-2">
                        <button
                            type="button"
                            class="rounded-md border px-3 py-2 text-xs font-black transition"
                            :class="reviewScope === 'datasheet' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            @click="reviewScope = 'datasheet'"
                        >
                            Datasheet
                        </button>
                        <button
                            type="button"
                            class="rounded-md border px-3 py-2 text-xs font-black transition"
                            :class="reviewScope === 'engineering-data' ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            @click="reviewScope = 'engineering-data'"
                        >
                            Engineering Data
                        </button>
                    </div>
                </div>

                <div class="grid gap-2 lg:grid-cols-2">
                    <label class="block text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        Review notes
                        <textarea
                            v-model="notesForm.notes"
                            rows="1"
                            class="mt-1 min-h-10 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold normal-case tracking-normal text-slate-800 focus:border-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Review notes..."
                        />
                    </label>
                    <label v-if="routes.approve || routes.reject || routes.requestChanges" class="block text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        Librarian comment
                        <textarea
                            v-model="commentForm.comment"
                            rows="1"
                            class="mt-1 min-h-10 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold normal-case tracking-normal text-slate-800 focus:border-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-200"
                            placeholder="Required for reject or request changes..."
                        />
                    </label>
                </div>

                <div class="flex flex-wrap gap-2 2xl:justify-end">
                    <button class="rounded-md border border-slate-950 bg-white px-3 py-2 text-xs font-black text-slate-950 hover:bg-slate-950 hover:text-white" @click="saveReview">
                        Save Review
                    </button>
                    <button v-if="routes.submit" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800" @click="submitForApproval">
                        Submit
                    </button>
                    <button v-if="routes.approve" class="rounded-md bg-emerald-700 px-3 py-2 text-xs font-black text-white hover:bg-emerald-800" @click="approve">
                        Approve & Publish
                    </button>
                    <button v-if="routes.requestChanges" class="rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-black text-amber-900 hover:bg-amber-100" @click="requestChanges">
                        Request Changes
                    </button>
                    <button v-if="routes.reject" class="rounded-md border border-rose-300 bg-rose-50 px-3 py-2 text-xs font-black text-rose-900 hover:bg-rose-100" @click="reject">
                        Reject
                    </button>
                    <Link :href="routes.back" class="rounded-md border border-slate-200 bg-white px-3 py-2 text-center text-xs font-black text-slate-700 hover:bg-slate-50">
                        Back
                    </Link>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(520px,1.35fr)_minmax(420px,1fr)]">
            <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black text-slate-950">Datasheet Preview</h2>
                            <p class="mt-1 text-sm text-slate-600">Visual evidence for validating the compilation.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-black text-sky-800">Source: {{ pdfPolicy?.source_label || 'Pending' }}</span>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-black text-slate-700">PDF: {{ pdfPolicy?.pdf_label || 'Pending' }}</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <PdfViewer v-if="pdfPolicy?.can_embed && pdfPolicy.preview_url" :src="pdfPolicy.preview_url" />
                        <div v-else class="flex h-[520px] items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                            <div>
                                <FileText class="mx-auto size-10 text-slate-400" />
                                <h3 class="mt-3 text-lg font-black text-slate-950">External source only</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    This PDF is retained for metadata and compilation evidence only when allowed. Use the manufacturer source link for the public datasheet.
                                </p>
                                <a
                                    v-if="pdfPolicy?.source_url"
                                    :href="pdfPolicy.source_url"
                                    target="_blank"
                                    class="mt-4 inline-flex rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800"
                                >
                                    View datasheet at manufacturer website
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2">
                        <a
                            v-if="pdfPolicy?.preview_url"
                            :href="pdfPolicy.preview_url"
                            target="_blank"
                            class="rounded-md border border-slate-200 px-3 py-2 text-center text-sm font-black text-slate-700 hover:bg-slate-50"
                        >
                            Open original
                        </a>
                        <a
                            v-if="pdfPolicy?.source_url"
                            :href="pdfPolicy.source_url"
                            target="_blank"
                            class="rounded-md border border-slate-200 px-3 py-2 text-center text-sm font-black text-slate-700 hover:bg-slate-50"
                        >
                            Manufacturer source URL
                        </a>
                    </div>
                </section>
            </aside>

            <main class="space-y-6">
                <section v-if="reviewScope === 'datasheet'" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Datasheet Compilation Summary</p>
                            <h1 class="mt-2 text-3xl font-black text-slate-950">{{ datasheet.title }}</h1>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Review the source datasheet, extraction coverage, warnings, and generated Structured Engineering Data before editing individual models.
                            </p>
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div v-for="item in [
                            ['Manufacturer', datasheet.manufacturer],
                            ['Family / Series', datasheet.family_series],
                            ['Revision', datasheet.revision],
                            ['Language', datasheet.language],
                            ['Publication date', datasheet.publication_date],
                            ['Source type', datasheet.source_type?.replaceAll('_', ' ')],
                            ['Uploaded by', datasheet.uploaded_by],
                            ['Uploaded at', datasheet.uploaded_at],
                        ]" :key="item[0]" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ item[0] }}</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ item[1] || '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section v-if="reviewScope === 'datasheet'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article v-for="item in [
                        ['Models found', summary.models_found, DatabaseZap],
                        ['Engineering Data Sets', summary.compiled_records_created, ClipboardCheck],
                        ['Warnings', summary.warning_count, AlertTriangle],
                        ['Errors', summary.error_count, XCircle],
                    ]" :key="item[0]" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <component :is="item[2]" class="size-5 text-emerald-700" />
                        <p class="mt-4 text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ item[0] }}</p>
                        <p class="mt-2 text-3xl font-black text-slate-950">{{ item[1] ?? 0 }}</p>
                    </article>
                </section>

                <section v-if="reviewScope === 'datasheet'" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Compilation Summary</h2>
                    <dl class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <div v-for="item in [
                            ['Device type', summary.device_type],
                            ['Compiler version', summary.compiler_version],
                            ['Compile quality', summary.quality_grade || summary.quality_score],
                            ['Validation status', summary.validation_status],
                        ]" :key="item[0]" class="rounded-md border border-slate-200 p-3">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ item[0] }}</dt>
                            <dd class="mt-1 font-bold text-slate-800">{{ item[1] || '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section v-if="reviewScope === 'datasheet'" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Extraction Coverage</h2>
                    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <div v-for="(present, key) in coverage" :key="key" class="flex items-center gap-3 rounded-md border border-slate-200 p-3">
                            <CheckCircle2 v-if="present" class="size-5 text-emerald-600" />
                            <XCircle v-else class="size-5 text-slate-300" />
                            <span class="text-sm font-bold text-slate-800">{{ coverageLabel(String(key)) }}</span>
                        </div>
                    </div>
                </section>

                <section v-if="reviewScope === 'datasheet'" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Warnings</h2>
                    <div v-if="warnings.length" class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Severity</th>
                                    <th class="px-4 py-3">Code</th>
                                    <th class="px-4 py-3">Message</th>
                                    <th class="px-4 py-3">Record</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="warning in warnings" :key="`${warning.code}-${warning.message}-${warning.record}`">
                                    <td class="px-4 py-3 capitalize">{{ warning.severity }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ warning.code }}</td>
                                    <td class="px-4 py-3">{{ warning.message }}</td>
                                    <td class="px-4 py-3">{{ warning.record || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">No compiler or validation warnings detected for this datasheet.</p>
                </section>

                <section v-if="reviewScope === 'engineering-data'" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-xl font-black text-slate-950">Models / Structured Engineering Data</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Select an individual model or structured engineering data set to review identity, electrical, mechanical, operating, temperature, warranty, protection, and validation fields.
                    </p>
                    <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Model / Display name</th>
                                    <th class="px-4 py-3">Power</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Review</th>
                                    <th class="px-4 py-3">Validation</th>
                                    <th class="px-4 py-3">Updated</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="record in records" :key="record.uuid">
                                    <td class="px-4 py-4">
                                        <div class="font-black text-slate-950">{{ record.display_name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ record.model_series || record.model_name || '—' }}</div>
                                    </td>
                                    <td class="px-4 py-4">{{ record.power_class_w ? `${record.power_class_w} W` : record.power_class_kw ? `${record.power_class_kw} kW` : '—' }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-black" :class="statusClass(record.status)">{{ record.status?.replaceAll('_', ' ') || '—' }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full border px-2.5 py-1 text-xs font-black" :class="statusClass(record.review_status)">{{ record.review_status?.replaceAll('_', ' ') || '—' }}</span>
                                    </td>
                                    <td class="px-4 py-4">{{ record.validation_status || record.validation_grade || '—' }}</td>
                                    <td class="px-4 py-4">{{ record.created_at ? new Date(record.created_at).toLocaleDateString() : '—' }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="record.open_href" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold hover:bg-slate-50">Open</Link>
                                            <Link :href="record.review_model_href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Review Model</Link>
                                            <Link :href="record.history_href" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold hover:bg-slate-50">History</Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="records.length === 0">
                                    <td colspan="7" class="px-4 py-10 text-center text-slate-600">No Structured Engineering Data has been compiled for this datasheet yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </component>
</template>
