<script setup lang="ts">
import EngineeringRecordCard from '@/components/linewatt/EngineeringRecordCard.vue';
import EngineeringRecordTable from '@/components/linewatt/EngineeringRecordTable.vue';
import PlaceholderAction from '@/components/linewatt/PlaceholderAction.vue';
import PublicSiteLayout from '@/components/linewatt/PublicSiteLayout.vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

type Filters = {
    q?: string;
    tab?: string;
    scope?: string;
    device_type?: string;
    manufacturer?: string;
    model_series?: string;
    technology?: string;
    validation_grade?: string;
    power_min?: string;
    power_max?: string;
    inverter_device_type?: string;
    bifacial?: string;
    double_glass?: string;
    maximum_system_voltage?: string;
    has_dc_switch?: string;
    has_dc_spd?: string;
    has_ac_spd?: string;
    has_afci?: string;
    has_rcmu?: string;
    mppt_count?: string;
    needs_review?: string;
    power_tags?: string[];
    parsed_terms?: string;
    engineering_query_parsed?: string;
    parsed_technology?: string;
};

const props = defineProps<{
    filters: Filters;
    sort: string;
    deviceTypeCounts: {
        all: number;
        module: number;
        inverter: number;
    };
    emptyState: {
        type: string;
        title: string;
        message: string;
    };
    records: {
        data: any[];
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    powerSearch: {
        selected: Array<{ label: string; slug: string; category?: string }>;
    };
}>();

const { dir, t } = useLineWattI18n();

const activeTab = computed(() => props.filters.tab || 'all');
const scopeLabel = computed(() => {
    if (props.filters.scope === 'my-library') {
        return t('nav.myPrivateDatasets');
    }

    if (props.filters.scope === 'both') {
        return `LineWatt Library + ${t('nav.myPrivateDatasets')}`;
    }

    return 'LineWatt Library';
});

const productTypeLabel = computed(() => {
    if (activeTab.value === 'modules') {
        return t('manufacturerDirectory.modules');
    }

    if (activeTab.value === 'inverters') {
        return t('manufacturerDirectory.inverters');
    }

    if (props.filters.device_type === 'module') {
        return t('manufacturerDirectory.modules');
    }

    if (props.filters.device_type === 'inverter') {
        return t('manufacturerDirectory.inverters');
    }

    return t('results.all');
});

const editSearchHref = computed(() => {
    const queryParams = queryWithout([]);
    const path = activeTab.value === 'modules' ? '/search/modules' : activeTab.value === 'inverters' ? '/search/inverters' : '/search';
    delete queryParams.tab;
    const params = new URLSearchParams(queryParams);
    const queryString = params.toString();

    return queryString === '' ? path : `${path}?${queryString}`;
});

const activeFilterChips = computed(() => {
    const chips: Array<{ key: string; label: string; value: string; removeKeys: string[] }> = [];

    if (props.filters.q) {
        chips.push({ key: 'q', label: t('nav.search'), value: props.filters.q, removeKeys: ['q'] });
    }

    if (props.filters.scope && props.filters.scope !== 'central') {
        chips.push({ key: 'scope', label: t('results.scope'), value: scopeLabel.value, removeKeys: ['scope'] });
    }

    if (activeTab.value !== 'all') {
        chips.push({
            key: 'tab',
            label: t('results.type'),
            value: activeTab.value === 'modules' ? t('manufacturerDirectory.modules') : t('manufacturerDirectory.inverters'),
            removeKeys: ['tab', 'device_type', 'inverter_device_type', 'power_min', 'power_max'],
        });
    } else if (props.filters.device_type) {
        chips.push({ key: 'device_type', label: t('results.type'), value: props.filters.device_type.replace('_', ' '), removeKeys: ['device_type'] });
    }

    if (props.filters.manufacturer) {
        chips.push({ key: 'manufacturer', label: t('search.manufacturer'), value: props.filters.manufacturer, removeKeys: ['manufacturer'] });
    }

    if (props.filters.model_series) {
        chips.push({ key: 'model_series', label: t('search.modelSeries'), value: props.filters.model_series, removeKeys: ['model_series'] });
    }

    if (props.filters.power_min || props.filters.power_max) {
        const unit = activeTab.value === 'inverters' ? 'kW' : 'W';
        const min = props.filters.power_min || t('results.any');
        const max = props.filters.power_max || t('results.any');
        chips.push({ key: 'power', label: t('manufacturerProducts.power'), value: `${min}-${max}${unit}`, removeKeys: ['power_min', 'power_max'] });
    }

    if (props.filters.technology) {
        chips.push({ key: 'technology', label: t('search.technology'), value: props.filters.technology, removeKeys: ['technology'] });
    }

    if (props.filters.validation_grade) {
        chips.push({ key: 'validation_grade', label: t('manufacturerProducts.validation'), value: props.filters.validation_grade, removeKeys: ['validation_grade'] });
    }

    if (props.filters.needs_review === '1') {
        chips.push({ key: 'needs_review', label: t('results.workflow'), value: t('myLibrary.needsReview'), removeKeys: ['needs_review'] });
    }

    for (const tag of props.powerSearch.selected || []) {
        chips.push({ key: `power_tag_${tag.slug}`, label: t('search.powerSearch'), value: tag.label, removeKeys: [`power_tags:${tag.slug}`] });
    }

    if (props.filters.inverter_device_type) {
        chips.push({
            key: 'inverter_device_type',
            label: t('search.inverterType'),
            value: props.filters.inverter_device_type.replaceAll('_', ' '),
            removeKeys: ['inverter_device_type'],
        });
    }

    for (const [key, label] of [
        ['bifacial', t('search.bifacial')],
        ['double_glass', t('search.doubleGlass')],
        ['maximum_system_voltage', t('results.maximumSystemVoltage')],
        ['has_dc_switch', 'DC switch'],
        ['has_dc_spd', 'DC SPD'],
        ['has_ac_spd', 'AC SPD'],
        ['has_afci', 'AFCI'],
        ['has_rcmu', 'RCMU'],
        ['mppt_count', 'MPPT count'],
    ]) {
        const value = props.filters[key as keyof Filters];
        if (value) {
            chips.push({ key, label, value: value === '1' ? t('results.yes') : value, removeKeys: [key] });
        }
    }

    return chips;
});

function queryWithout(removeKeys: string[]): Record<string, any> {
    const query: Record<string, any> = {};

    for (const [key, value] of Object.entries(props.filters)) {
        if (!removeKeys.includes(key) && value !== undefined && value !== '' && !(key === 'tab' && value === 'all')) {
            if (['parsed_terms', 'engineering_query_parsed', 'parsed_technology'].includes(key)) {
                continue;
            }

            if (key === 'scope' && value === 'central') {
                continue;
            }

            if (Array.isArray(value)) {
                const removedTags = removeKeys
                    .filter((removeKey) => removeKey.startsWith(`${key}:`))
                    .map((removeKey) => removeKey.split(':')[1]);
                const nextValues = value.filter((item) => !removedTags.includes(item));
                if (nextValues.length > 0 && !removeKeys.includes(key)) {
                    query[key] = nextValues;
                }
                continue;
            }

            query[key] = value;
        }
    }

    if (props.sort !== 'newest') {
        query.sort = props.sort;
    }

    return query;
}

function removeChip(removeKeys: string[]): void {
    router.get('/search/results', queryWithout(removeKeys), {
        preserveState: false,
        replace: true,
    });
}

function updateSort(event: Event): void {
    const target = event.target as HTMLSelectElement;
    router.get(
        '/search/results',
        {
            ...queryWithout([]),
            sort: target.value,
        },
        {
            preserveState: false,
            replace: true,
        },
    );
}

function paramsToQueryString(params: Record<string, string | number | undefined>): string {
    const searchParams = new URLSearchParams();

    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== '') {
            searchParams.set(key, String(value));
        }
    }

    const queryString = searchParams.toString();

    return queryString === '' ? '' : `?${queryString}`;
}

function compareDeviceType(record?: any): string | undefined {
    if (record?.device_type === 'module' || record?.device_type === 'inverter') {
        return record.device_type;
    }

    if (activeTab.value === 'modules' || props.filters.device_type === 'module') {
        return 'module';
    }

    if (activeTab.value === 'inverters' || props.filters.device_type === 'inverter') {
        return 'inverter';
    }

    return undefined;
}

function recordKey(record: any): string {
    return String(record.id ?? record.uuid ?? '');
}

function compareSelectHref(record?: any): string {
    return `/compare/select${paramsToQueryString({
        q: props.filters.q,
        device_type: compareDeviceType(record),
        seed: record ? recordKey(record) : undefined,
    })}`;
}

function openCompareSelector(record: any): void {
    router.get(compareSelectHref(record), {}, {
        preserveState: false,
    });
}
</script>

<template>
    <Head :title="t('results.headTitle')" />

    <PublicSiteLayout :dir="dir">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">
                            {{ t('results.eyebrow') }}
                        </p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">
                            {{ t('myLibrary.engineeringRecords') }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ t('manufacturerDirectory.showing') }} {{ records.from || 0 }}-{{ records.to || 0 }}. {{ t('results.keepMoving') }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold">
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-800">{{ t('results.scope') }}: {{ scopeLabel }}</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">{{ t('results.productType') }}: {{ productTypeLabel }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="editSearchHref"
                            class="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"
                        >
                            {{ t('results.editSearch') }}
                        </Link>
                        <Link
                            :href="compareSelectHref()"
                            class="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"
                        >
                            {{ t('nav.compare') }}
                        </Link>
                        <PlaceholderAction :label="t('nav.exports')" />
                        <PlaceholderAction :label="t('results.saveSearch')" />
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="chip in activeFilterChips"
                            :key="chip.key"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-white"
                            type="button"
                            @click="removeChip(chip.removeKeys)"
                        >
                            <span class="text-slate-400">{{ chip.label }}</span>
                            <span>{{ chip.value }}</span>
                            <span aria-hidden="true" class="text-slate-400">x</span>
                        </button>
                        <span v-if="activeFilterChips.length === 0" class="text-sm text-slate-500">
                            {{ t('results.noFilters') }}
                        </span>
                    </div>

                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        {{ t('results.sort') }}
                        <select
                            class="rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                            :value="sort"
                            @change="updateSort"
                        >
                            <option value="newest">{{ t('results.newest') }}</option>
                            <option value="manufacturer">{{ t('search.manufacturer') }}</option>
                            <option value="power_asc">{{ t('results.powerLowHigh') }}</option>
                            <option value="power_desc">{{ t('results.powerHighLow') }}</option>
                            <option value="validation">{{ t('manufacturerProducts.validation') }}</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="mt-6">
                <div class="hidden lg:block">
                    <EngineeringRecordTable
                        compare-enabled
                        :empty-state="emptyState"
                        :records="records.data"
                        :tab="activeTab"
                        @compare="openCompareSelector"
                    />
                </div>
                <div class="grid gap-4 lg:hidden">
                    <EngineeringRecordCard
                        v-for="record in records.data"
                        :key="record.uuid || record.id"
                        compare-enabled
                        :record="record"
                        @compare="openCompareSelector(record)"
                    />
                    <div v-if="records.data.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                        <h3 class="text-lg font-black text-slate-950">{{ emptyState.title }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ emptyState.message }}</p>
                    </div>
                </div>
            </section>

            <nav v-if="records.last_page > 1" class="mt-6 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in records.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded-md border px-3 py-2 text-sm font-bold"
                    :class="[
                        link.active ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </div>
    </PublicSiteLayout>
</template>
