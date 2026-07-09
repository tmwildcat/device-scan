<script setup lang="ts">
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

type SearchMode = 'all' | 'modules' | 'inverters';

type ManufacturerSuggestion = {
    label: string;
    value: string;
    url?: string;
};

const props = defineProps<{
    mode: SearchMode;
    filters: {
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
        power_tags?: string[];
    };
    deviceTypeCounts: {
        all: number;
        module: number;
        inverter: number;
    };
    filterOptions: {
        technologies: string[];
        validation_grades: string[];
        inverter_device_types: string[];
    };
    powerSearch: {
        categories: Record<string, Array<{ label: string; slug: string; category?: string }>>;
        featured: Array<{ label: string; slug: string; category?: string }>;
        selected: Array<{ label: string; slug: string; category?: string }>;
    };
}>();

const { dir, t } = useLineWattI18n();

const form = reactive({
    q: props.filters.q || '',
    tab: props.mode,
    scope: props.filters.scope || 'central',
    device_type: props.filters.device_type || '',
    manufacturer: props.filters.manufacturer || '',
    model_series: props.filters.model_series || '',
    technology: props.filters.technology || '',
    validation_grade: props.filters.validation_grade || '',
    power_min: props.filters.power_min || '',
    power_max: props.filters.power_max || '',
    inverter_device_type: props.filters.inverter_device_type || '',
    power_tags: Array.isArray((props.filters as any).power_tags) ? [...(props.filters as any).power_tags] : [],
});

const manufacturerSuggestions = ref<ManufacturerSuggestion[]>([]);
const manufacturerLoading = ref(false);
let manufacturerDebounce: ReturnType<typeof setTimeout> | null = null;

const isBroadSearch = computed(() => props.mode === 'all');
const isModuleSearch = computed(() => props.mode === 'modules');
const isInverterSearch = computed(() => props.mode === 'inverters');

const title = computed(() => {
    if (isModuleSearch.value) {
        return t('search.moduleTitle');
    }

    if (isInverterSearch.value) {
        return t('search.inverterTitle');
    }

    return t('search.title');
});

const description = computed(() => {
    if (isModuleSearch.value) {
        return t('search.moduleDescription');
    }

    if (isInverterSearch.value) {
        return t('search.inverterDescription');
    }

    return t('search.description');
});

const searchPlaceholder = computed(() => {
    if (isModuleSearch.value) {
        return t('search.modulePlaceholder');
    }

    if (isInverterSearch.value) {
        return t('search.inverterPlaceholder');
    }

    return t('search.placeholder');
});

const exampleSearches = computed(() => {
    if (isModuleSearch.value) {
        return ['600W bifacial Jinko TOPCon', '1500V double glass module', 'Trina Vertex N 595W'];
    }

    if (isInverterSearch.value) {
        return ['110kW Sungrow DC SPD', 'hybrid inverter AFCI RCMU', 'central inverter 330kW'];
    }

    return ['600W bifacial Jinko TOPCon', '110kW Sungrow DC SPD', '1500V double glass module', 'hybrid inverter AFCI RCMU'];
});

function compactQuery(): Record<string, string | string[]> {
    return Object.fromEntries(
        Object.entries(form).filter(([key, value]) => {
            if (Array.isArray(value)) {
                return value.length > 0;
            }

            if (value === '') {
                return false;
            }

            if (key === 'tab' && value === 'all') {
                return false;
            }

            if (key === 'scope' && value === 'central') {
                return false;
            }

            return true;
        }),
    );
}

function submitSearch(): void {
    form.tab = props.mode;
    router.get('/search/results', compactQuery(), {
        preserveState: false,
    });
}

function clearFilters(): void {
    form.q = '';
    form.device_type = '';
    form.manufacturer = '';
    form.model_series = '';
    form.technology = '';
    form.validation_grade = '';
    form.power_min = '';
    form.power_max = '';
    form.inverter_device_type = '';
    form.power_tags = [];
    manufacturerSuggestions.value = [];
}

function manufacturerDeviceType(): string {
    if (isModuleSearch.value) {
        return 'module';
    }

    if (isInverterSearch.value) {
        return 'inverter';
    }

    return form.device_type;
}

function scheduleManufacturerLookup(): void {
    if (manufacturerDebounce !== null) {
        clearTimeout(manufacturerDebounce);
    }

    const query = form.manufacturer.trim();
    if (query.length < 2) {
        manufacturerSuggestions.value = [];
        manufacturerLoading.value = false;
        return;
    }

    manufacturerLoading.value = true;
    manufacturerDebounce = setTimeout(fetchManufacturers, 300);
}

async function fetchManufacturers(): Promise<void> {
    const params = new URLSearchParams({ q: form.manufacturer.trim() });
    const deviceType = manufacturerDeviceType();
    if (deviceType !== '') {
        params.set('device_type', deviceType);
    }

    const response = await fetch(`/search/manufacturers?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });
    manufacturerSuggestions.value = response.ok ? await response.json() : [];
    manufacturerLoading.value = false;
}

function selectManufacturer(suggestion: ManufacturerSuggestion): void {
    form.manufacturer = suggestion.value;
    manufacturerSuggestions.value = [];
}

function runExample(example: string): void {
    form.q = example;

    if (props.mode === 'all') {
        if (example.toLowerCase().includes('inverter') || example.toLowerCase().includes('sungrow')) {
            form.tab = 'inverters';
        } else if (example.toLowerCase().includes('module') || example.toLowerCase().includes('jinko')) {
            form.tab = 'modules';
        }
    }

    submitSearch();
}

function togglePowerTag(slug: string): void {
    form.power_tags = form.power_tags.includes(slug)
        ? form.power_tags.filter((tag) => tag !== slug)
        : [...form.power_tags, slug];
}

function runPowerTag(slug: string): void {
    if (!form.power_tags.includes(slug)) {
        form.power_tags = [...form.power_tags, slug];
    }

    submitSearch();
}
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-slate-50 text-slate-950" :dir="dir">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-7 shadow-sm sm:p-9">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">
                    LineWatt Library
                </p>
                <h1 class="mt-4 text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">
                    {{ title }}
                </h1>
                <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                    {{ description }}
                </p>

                <div v-if="isBroadSearch" class="mt-5 flex flex-wrap gap-2">
                    <Link
                        href="/search/modules"
                        class="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                    >
                        {{ t('manufacturerDirectory.modules') }}
                    </Link>
                    <Link
                        href="/search/inverters"
                        class="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                    >
                        {{ t('manufacturerDirectory.inverters') }}
                    </Link>
                </div>

                <form class="mt-7 space-y-6" @submit.prevent="submitSearch">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-2 shadow-inner">
                        <input
                            v-model="form.q"
                            class="min-h-14 w-full rounded-md border border-transparent bg-white px-4 text-base font-semibold text-slate-950 shadow-sm outline-none focus:border-emerald-500"
                            :placeholder="searchPlaceholder"
                            type="search"
                        />
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">
                            {{ t('search.try') }} <span class="font-semibold text-slate-700">{{ exampleSearches[0] }}</span>
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-for="example in exampleSearches"
                                :key="example"
                                class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800"
                                type="button"
                                @click="runExample(example)"
                            >
                                {{ example }}
                            </button>
                        </div>
                    </div>

                    <div v-if="powerSearch.featured.length" class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-black uppercase tracking-[0.14em] text-slate-500">{{ t('search.powerSearch') }}</h2>
                                <p class="mt-1 text-sm text-slate-600">{{ t('search.powerSearchHelp') }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                v-for="option in powerSearch.featured"
                                :key="option.slug"
                                class="rounded-full border px-3 py-1.5 text-sm font-bold"
                                :class="form.power_tags.includes(option.slug)
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                                    : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-white'"
                                type="button"
                                @click="isBroadSearch ? runPowerTag(option.slug) : togglePowerTag(option.slug)"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>

                    <div v-if="!isBroadSearch" class="grid gap-5 lg:grid-cols-12">
                        <div class="relative block text-sm font-semibold text-slate-700 lg:col-span-4">
                            {{ t('search.manufacturer') }}
                            <input
                                v-model="form.manufacturer"
                                autocomplete="off"
                                class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none"
                                :placeholder="t('search.typeAtLeast')"
                                type="search"
                                @focus="scheduleManufacturerLookup"
                                @input="scheduleManufacturerLookup"
                            />
                            <div
                                v-if="manufacturerSuggestions.length || manufacturerLoading"
                                class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg"
                            >
                                <div v-if="manufacturerLoading" class="px-3 py-2 text-sm text-slate-500">{{ t('myLibrary.searching') }}</div>
                                <button
                                    v-for="suggestion in manufacturerSuggestions"
                                    :key="suggestion.value"
                                    class="block w-full px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    type="button"
                                    @click="selectManufacturer(suggestion)"
                                >
                                    {{ suggestion.label }}
                                </button>
                            </div>
                        </div>

                        <label class="block text-sm font-semibold text-slate-700 lg:col-span-4">
                            {{ t('search.modelSeries') }}
                            <input
                                v-model="form.model_series"
                                class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none"
                                placeholder="e.g. TSM-NE19R"
                                type="text"
                            />
                        </label>

                        <label class="block text-sm font-semibold text-slate-700 lg:col-span-4">
                            {{ t('search.validationGrade') }}
                            <select v-model="form.validation_grade" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                                <option value="">{{ t('search.anyGrade') }}</option>
                                <option v-for="grade in filterOptions.validation_grades" :key="grade" :value="grade">
                                    {{ grade }}
                                </option>
                            </select>
                        </label>

                        <template v-if="isModuleSearch">
                            <label class="block text-sm font-semibold text-slate-700 lg:col-span-2">
                                {{ t('search.minW') }}
                                <input v-model="form.power_min" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none" min="0" type="number" />
                            </label>
                            <label class="block text-sm font-semibold text-slate-700 lg:col-span-2">
                                {{ t('search.maxW') }}
                                <input v-model="form.power_max" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none" min="0" type="number" />
                            </label>
                            <label class="block text-sm font-semibold text-slate-700 lg:col-span-4">
                                {{ t('search.technology') }}
                                <select v-model="form.technology" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                                    <option value="">{{ t('search.anyTechnology') }}</option>
                                    <option v-for="technology in filterOptions.technologies" :key="technology" :value="technology">
                                        {{ technology }}
                                    </option>
                                </select>
                            </label>
                            <div class="flex flex-wrap items-end gap-2 text-sm lg:col-span-4">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">{{ t('search.bifacial') }}: <strong>{{ t('search.comingSoon') }}</strong></span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">{{ t('search.doubleGlass') }}: <strong>{{ t('search.comingSoon') }}</strong></span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">{{ t('search.maxVoltage') }}: <strong>{{ t('search.comingSoon') }}</strong></span>
                            </div>
                        </template>

                        <template v-if="isInverterSearch">
                            <label class="block text-sm font-semibold text-slate-700 lg:col-span-2">
                                {{ t('search.minKw') }}
                                <input v-model="form.power_min" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none" min="0" type="number" />
                            </label>
                            <label class="block text-sm font-semibold text-slate-700 lg:col-span-2">
                                {{ t('search.maxKw') }}
                                <input v-model="form.power_max" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none" min="0" type="number" />
                            </label>
                            <label class="block text-sm font-semibold text-slate-700 lg:col-span-4">
                                {{ t('search.inverterType') }}
                                <select v-model="form.inverter_device_type" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2.5 text-sm capitalize focus:border-emerald-500 focus:outline-none">
                                    <option value="">{{ t('search.anyInverterType') }}</option>
                                    <option v-for="deviceType in filterOptions.inverter_device_types" :key="deviceType" :value="deviceType">
                                        {{ deviceType.replaceAll('_', ' ') }}
                                    </option>
                                </select>
                            </label>
                            <div class="flex flex-wrap items-end gap-2 text-sm lg:col-span-4">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">DC switch: <strong>{{ t('search.comingSoon') }}</strong></span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">DC SPD: <strong>{{ t('search.comingSoon') }}</strong></span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">AC SPD: <strong>{{ t('search.comingSoon') }}</strong></span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">AFCI: <strong>{{ t('search.comingSoon') }}</strong></span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-slate-500">RCMU: <strong>{{ t('search.comingSoon') }}</strong></span>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-2">
                        <button class="rounded-md bg-slate-950 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800" type="submit">
                            {{ t('myLibrary.search') }}
                        </button>
                        <button class="rounded-md border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50" type="button" @click="clearFilters">
                            {{ t('myLibrary.clear') }}
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</template>
