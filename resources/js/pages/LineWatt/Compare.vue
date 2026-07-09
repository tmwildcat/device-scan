<script setup lang="ts">
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import PlaceholderAction from '@/components/linewatt/PlaceholderAction.vue';
import QualityGradeBadge from '@/components/linewatt/QualityGradeBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, GitCompare, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type CompareValue = {
    record_key: string;
    display: string;
    numeric?: number | null;
    missing: boolean;
    source_page?: string | number | null;
    source_section?: string | null;
    source_text?: string | null;
    highlight?: 'high' | 'low' | null;
};

type CompareRow = {
    key: string;
    label: string;
    kind: string;
    values: CompareValue[];
    has_any_value: boolean;
    has_difference: boolean;
    diff_percent?: number | null;
};

type CompareSection = {
    key: string;
    title: string;
    rows: CompareRow[];
};

type CompareRecord = {
    id?: number;
    uuid?: string;
    comparison_key: string;
    href?: string;
    manufacturer?: string | null;
    display_name?: string | null;
    model_name?: string | null;
    model_series?: string | null;
    series?: string | null;
    technology?: string | null;
    device_type?: string | null;
    inverter_device_type?: string | null;
    power_class_w?: number | string | null;
    power_class_kw?: number | string | null;
    status?: string | null;
    validation_status?: string | null;
    validation_grade?: string | null;
    validation_score?: number | null;
    source_label?: string | null;
    json_source?: 'reviewed' | 'compiled';
    raw_json?: any;
};

const props = defineProps<{
    records: CompareRecord[];
    sections: CompareSection[];
    deviceType?: string | null;
    messages?: string[];
    exportHref?: string;
    canExportComparison?: boolean;
    exportDisabledReason?: string;
    libraryDebug: boolean;
}>();

const { dir, t } = useLineWattI18n();
const onlyDifferences = ref(false);

const visibleSections = computed(() => props.sections
    .map((section) => ({
        ...section,
        rows: onlyDifferences.value ? section.rows.filter((row) => row.has_difference) : section.rows,
    }))
    .filter((section) => section.rows.length > 0));

const selectedRecordKeys = computed(() => props.records.map((record) => record.comparison_key || String(record.uuid || record.id)).filter(Boolean));
const engineeringHints = computed(() => {
    if (props.deviceType === 'module') {
        return [
            {
                title: t('compare.stringDesignHints'),
                items: [
                    t('compare.stringHint1'),
                    t('compare.stringHint2'),
                    t('compare.stringHint3'),
                    t('compare.stringHint4'),
                ],
            },
            {
                title: t('compare.mechanicalDesignHints'),
                items: [
                    t('compare.mechanicalHint1'),
                    t('compare.mechanicalHint2'),
                    t('compare.mechanicalHint3'),
                ],
            },
        ];
    }

    if (props.deviceType === 'inverter') {
        return [
            {
                title: t('compare.systemDesignHints'),
                items: [
                    t('compare.systemHint1'),
                    t('compare.systemHint2'),
                    t('compare.systemHint3'),
                ],
            },
            {
                title: t('compare.protectionHints'),
                items: [
                    t('compare.protectionHint1'),
                    t('compare.protectionHint2'),
                    t('compare.protectionHint3'),
                ],
            },
        ];
    }

    return [];
});

function displayRecord(record: CompareRecord): string {
    return record.display_name || record.model_name || record.model_series || t('compare.engineeringRecord');
}

function powerLabel(record: CompareRecord): string {
    if (record.power_class_w) return `${record.power_class_w} W`;
    if (record.power_class_kw) return `${record.power_class_kw} kW`;

    return '—';
}

function typeLabel(record: CompareRecord): string {
    if (record.device_type === 'module') return record.technology || t('myLibrary.module');

    return (record.inverter_device_type || record.device_type || t('myLibrary.inverter')).replaceAll('_', ' ');
}

function valueFor(row: CompareRow, record: CompareRecord): CompareValue {
    return row.values.find((value) => value.record_key === record.comparison_key) || {
        record_key: record.comparison_key,
        display: '—',
        missing: true,
        numeric: null,
        highlight: null,
    };
}

function removeRecord(record: CompareRecord): void {
    const next = selectedRecordKeys.value.filter((key) => key !== record.comparison_key);

    router.get('/compare', {
        records: next.join(','),
    }, {
        preserveState: false,
        replace: true,
    });
}

function valueClass(value: CompareValue, row: CompareRow): string {
    if (value.missing) {
        return 'text-slate-400';
    }

    if (!row.has_difference) {
        return 'text-slate-900';
    }

    if (value.highlight === 'high') {
        return 'bg-emerald-50 text-emerald-900 ring-1 ring-inset ring-emerald-100';
    }

    if (value.highlight === 'low') {
        return 'bg-amber-50 text-amber-900 ring-1 ring-inset ring-amber-100';
    }

    return 'bg-sky-50 text-sky-900 ring-1 ring-inset ring-sky-100';
}
</script>

<template>
    <Head :title="t('compare.title')" />

    <div class="min-h-screen bg-slate-50 text-slate-950" :dir="dir">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">{{ t('compare.eyebrow') }}</p>
                    <h1 class="mt-2 text-4xl font-black tracking-tight text-slate-950">{{ t('compare.title') }}</h1>
                    <p class="mt-3 max-w-3xl text-lg text-slate-600">
                        {{ t('compare.readOnlyPrefix') }} {{ deviceType === 'inverter' ? t('compare.inverters') : deviceType === 'module' ? t('compare.modules') : t('myLibrary.engineeringRecords') }}.
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span
                            v-for="record in records"
                            :key="record.comparison_key"
                            class="inline-flex max-w-xs items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-bold text-slate-700"
                        >
                            <span class="truncate">{{ displayRecord(record) }}</span>
                            <button
                                class="rounded-full p-0.5 text-slate-500 hover:bg-slate-100"
                                type="button"
                                :aria-label="`${t('compare.remove')} ${displayRecord(record)}`"
                                @click="removeRecord(record)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </span>
                    </div>
                </div>
                <Link
                    href="/my-library"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                >
                    <ArrowLeft class="size-4" />
                    {{ t('compare.back') }}
                </Link>
            </div>

            <div v-if="messages?.length" class="mt-8 space-y-3">
                <div
                    v-for="message in messages"
                    :key="message"
                    class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm font-bold text-amber-800"
                >
                    {{ message }}
                </div>
            </div>

            <section v-if="engineeringHints.length" class="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-700">{{ t('compare.preliminaryHints') }}</p>
                <p class="mt-2 text-sm font-semibold text-amber-900">{{ t('compare.notFinal') }}</p>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div v-for="hint in engineeringHints" :key="hint.title" class="rounded-lg border border-amber-200 bg-white/70 p-4">
                        <h2 class="font-black text-slate-950">{{ hint.title }}</h2>
                        <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-700">
                            <li v-for="item in hint.items" :key="item">• {{ item }}</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section v-if="records.length" class="mt-8 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
                    <div class="grid" :style="{ gridTemplateColumns: `220px repeat(${records.length}, minmax(0, 1fr))` }">
                        <div class="border-r border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.14em] text-slate-500">
                                <GitCompare class="size-4 text-emerald-700" />
                                {{ t('compare.records') }}
                            </div>
                        </div>
                        <article
                            v-for="record in records"
                            :key="record.comparison_key"
                            class="border-r border-slate-200 p-4 last:border-r-0"
                        >
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">
                                {{ record.device_type === 'inverter' ? t('myLibrary.inverter') : t('myLibrary.module') }}
                            </p>
                            <h2 class="mt-2 line-clamp-2 text-base font-black text-slate-950">
                                {{ displayRecord(record) }}
                            </h2>
                            <p class="mt-1 truncate text-sm text-slate-600">{{ record.manufacturer || t('myLibrary.unknownManufacturer') }}</p>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <ValidationStatusBadge :status="record.validation_status" />
                                <QualityGradeBadge :grade="record.validation_grade" :score="record.validation_score" />
                            </div>
                        </article>
                    </div>
                </div>

                <div class="grid" :style="{ gridTemplateColumns: `220px repeat(${records.length}, minmax(0, 1fr))` }">
                    <div class="border-r border-slate-200 bg-slate-50 p-4 text-sm font-bold text-slate-500">{{ t('compare.summary') }}</div>
                    <article
                        v-for="record in records"
                        :key="`summary-${record.comparison_key}`"
                        class="border-r border-slate-200 p-4 last:border-r-0"
                    >
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-slate-500">{{ t('search.modelSeries') }}</dt>
                                <dd class="font-bold text-slate-950">{{ record.model_series || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ t('compare.modelName') }}</dt>
                                <dd class="font-bold text-slate-950">{{ record.model_name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ t('manufacturerProducts.power') }}</dt>
                                <dd class="font-bold text-slate-950">{{ powerLabel(record) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ record.device_type === 'module' ? t('search.technology') : t('search.deviceType') }}</dt>
                                <dd class="font-bold capitalize text-slate-950">{{ typeLabel(record) }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ t('compare.source') }}</dt>
                                <dd class="font-bold text-slate-950">{{ record.source_label || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ t('compare.artifactUsed') }}</dt>
                                <dd class="font-bold capitalize text-slate-950">{{ record.json_source || 'compiled' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ t('compare.lifecycle') }}</dt>
                                <dd class="mt-1"><LifecycleStatusBadge :status="record.status" /></dd>
                            </div>
                        </dl>
                    </article>
                </div>
            </section>

            <section v-if="records.length" class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <label class="inline-flex items-center gap-2 text-sm font-bold text-slate-700">
                    <input v-model="onlyDifferences" class="size-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500" type="checkbox" />
                    {{ t('compare.onlyDifferences') }}
                </label>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="canExportComparison && exportHref && records.length >= 2"
                        :href="exportHref"
                        class="inline-flex rounded-md bg-slate-950 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800"
                    >
                        {{ t('compare.exportPdf') }}
                    </a>
                    <button
                        v-else
                        type="button"
                        class="inline-flex cursor-not-allowed rounded-md border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-bold text-slate-400"
                        :title="exportDisabledReason || t('compare.availableWithSubscription')"
                        disabled
                    >
                        {{ t('compare.exportPdf') }}
                    </button>
                    <PlaceholderAction :label="t('compare.addThirdRecord')" />
                    <PlaceholderAction :label="t('compare.saveComparison')" />
                </div>
            </section>

            <section v-if="records.length" class="mt-6 space-y-6">
                <div
                    v-for="section in visibleSections"
                    :key="section.key"
                    class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <h2 class="text-lg font-black text-slate-950">{{ section.title }}</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                                <tr>
                                    <th class="sticky left-0 z-10 w-56 bg-white px-4 py-3">{{ t('compare.field') }}</th>
                                    <th
                                        v-for="record in records"
                                        :key="`${section.key}-head-${record.comparison_key}`"
                                        class="min-w-64 px-4 py-3"
                                    >
                                        {{ displayRecord(record) }}
                                    </th>
                                    <th class="w-32 px-4 py-3">{{ t('compare.difference') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="row in section.rows" :key="`${section.key}-${row.key}`">
                                    <td class="sticky left-0 z-10 bg-white px-4 py-3 font-bold text-slate-950">
                                        {{ row.label }}
                                    </td>
                                    <td
                                        v-for="record in records"
                                        :key="`${section.key}-${row.key}-${record.comparison_key}`"
                                        class="px-4 py-3 align-top"
                                    >
                                        <div
                                            class="rounded-md px-2 py-1.5 font-semibold"
                                            :class="valueClass(valueFor(row, record), row)"
                                        >
                                            {{ valueFor(row, record).display }}
                                        </div>
                                        <div
                                            v-if="valueFor(row, record).source_page || valueFor(row, record).source_section || valueFor(row, record).source_text"
                                            class="mt-2 space-y-1 text-xs text-slate-500"
                                        >
                                            <div v-if="valueFor(row, record).source_page">{{ t('compare.page') }} {{ valueFor(row, record).source_page }}</div>
                                            <div v-if="valueFor(row, record).source_section">{{ valueFor(row, record).source_section }}</div>
                                            <div v-if="valueFor(row, record).source_text" class="line-clamp-2">{{ valueFor(row, record).source_text }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <span
                                            v-if="row.has_difference"
                                            class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-black text-sky-800"
                                        >
                                            <span v-if="row.diff_percent !== null && row.diff_percent !== undefined">{{ row.diff_percent }}%</span>
                                            <span v-else>{{ t('compare.different') }}</span>
                                        </span>
                                        <span v-else class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">
                                            {{ t('compare.matched') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    v-if="onlyDifferences && visibleSections.length === 0"
                    class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600"
                >
                    {{ t('compare.noDifferences') }}
                </div>
            </section>

            <details v-if="libraryDebug && records.some((record) => record.raw_json)" class="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <summary class="cursor-pointer text-sm font-black text-slate-950">{{ t('compare.debugJson') }}</summary>
                <pre class="mt-4 max-h-[520px] overflow-auto rounded-md bg-slate-950 p-4 text-xs text-slate-100">{{ JSON.stringify(records.map((record) => ({ id: record.id, raw_json: record.raw_json })), null, 2) }}</pre>
            </details>

            <section v-if="records.length === 0 && !messages?.length" class="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                <h2 class="text-lg font-black text-slate-950">{{ t('compare.selectRecords') }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ t('compare.chooseRecords') }}</p>
            </section>
        </main>
    </div>
</template>
