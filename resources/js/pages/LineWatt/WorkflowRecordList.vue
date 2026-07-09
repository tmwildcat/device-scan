<script setup lang="ts">
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import QualityGradeBadge from '@/components/linewatt/QualityGradeBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, BadgeCheck, ClipboardCheck, DatabaseZap, Search, ShieldAlert } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    workspaceTitle: string;
    pageType: 'approval_queue' | 'engineering_data';
    view: string;
    source?: string | null;
    title: string;
    description: string;
    records: {
        data: any[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from?: number | null;
        to?: number | null;
        total?: number;
    };
    views: Array<{ key: string; label: string; href: string }>;
    summary?: {
        all: number;
        pending_approval: number;
        pending_review: number;
        validation_warnings: number;
        changes_requested: number;
        published: number;
    };
    filters?: Record<string, string>;
    filterOptions?: {
        device_types: string[];
        statuses: string[];
        review_statuses: string[];
        validation_statuses: string[];
        source_types: string[];
    };
    basePath?: string;
    showSummaryCards?: boolean;
    showFilters?: boolean;
    showManufacturerFilter?: boolean;
    listTitle?: string;
    listDescription?: string;
}>();

const form = reactive({
    keyword: props.filters?.keyword || '',
    manufacturer: props.filters?.manufacturer || '',
    device_type: props.filters?.device_type || '',
    status: props.filters?.status || '',
    review_status: props.filters?.review_status || '',
    validation_status: props.filters?.validation_status || '',
    source_type: props.filters?.source_type || '',
    updated_from: props.filters?.updated_from || '',
    updated_to: props.filters?.updated_to || '',
});
const manufacturerSuggestions = ref<Array<{ label: string; value: string; count?: number }>>([]);
const manufacturerLoading = ref(false);
let manufacturerDebounce: ReturnType<typeof setTimeout> | null = null;

const summaryCards = computed(() => [
    { label: 'Pending Approval', value: props.summary?.pending_approval || 0, href: engineeringDataHref('pending_approval'), icon: ClipboardCheck },
    { label: 'Pending Review', value: props.summary?.pending_review || 0, href: engineeringDataHref('pending_review'), icon: DatabaseZap },
    { label: 'Validation Warnings', value: props.summary?.validation_warnings || 0, href: engineeringDataHref('validation_warnings'), icon: ShieldAlert },
    { label: 'Changes Requested', value: props.summary?.changes_requested || 0, href: engineeringDataHref('changes_requested'), icon: AlertTriangle },
    { label: 'Published', value: props.summary?.published || 0, href: engineeringDataHref('published'), icon: BadgeCheck },
]);

const visibleViews = computed(() => {
    if (props.pageType === 'engineering_data') {
        return props.views.filter((item) => item.key !== 'all');
    }

    return props.views;
});

function recordTitle(record: any): string {
    return record.display_name || record.model_name || record.model_series || 'Structured Engineering Data';
}

function powerLabel(record: any): string {
    if (record.power_class_w) return `${record.power_class_w} W`;
    if (record.power_class_kw) return `${record.power_class_kw} kW`;
    return '—';
}

function labelize(value?: string | null): string {
    return value ? value.replaceAll('_', ' ') : '—';
}

function currentDeviceType(): 'module' | 'inverter' {
    return form.device_type === 'inverter' ? 'inverter' : 'module';
}

function engineeringDataHref(view: string, deviceType = currentDeviceType()): string {
    return `${props.basePath || '/admin/library/engineering-data'}?view=${view}&device_type=${deviceType}`;
}

function sourceHref(source: string | null): string {
    const url = new URL(window.location.href);
    if (source) {
        url.searchParams.set('source', source);
    } else {
        url.searchParams.delete('source');
    }
    return `${url.pathname}?${url.searchParams.toString()}`;
}

function applyFilters(): void {
    router.get(props.basePath || '/admin/library/engineering-data', {
        view: props.view,
        ...form,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters(): void {
    router.get(props.basePath || '/admin/library/engineering-data', { view: props.view, device_type: currentDeviceType() }, {
        preserveState: true,
        replace: true,
    });
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
    const params = new URLSearchParams({
        q: form.manufacturer.trim(),
        source: 'engineering-data',
    });
    if (form.device_type) {
        params.set('device_type', form.device_type);
    }
    const response = await fetch(`/search/manufacturers?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });

    manufacturerSuggestions.value = response.ok ? await response.json() : [];
    manufacturerLoading.value = false;
}

function selectManufacturer(suggestion: { value: string }): void {
    form.manufacturer = suggestion.value;
    manufacturerSuggestions.value = [];
    applyFilters();
}
</script>

<template>
    <Head :title="title" />

    <LibraryAdminShell
        :title="title"
        :subtitle="description"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Library Data Management' },
            { label: title },
        ]"
    >
        <section v-if="pageType === 'engineering_data'" class="mb-5 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Operations View</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <Link
                        :href="engineeringDataHref(view, 'module')"
                        class="rounded-md px-4 py-2 text-sm font-black"
                        :class="currentDeviceType() === 'module' ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    >
                        Modules
                    </Link>
                    <Link
                        :href="engineeringDataHref(view, 'inverter')"
                        class="rounded-md px-4 py-2 text-sm font-black"
                        :class="currentDeviceType() === 'inverter' ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    >
                        Inverters
                    </Link>
                </div>
            </div>

            <Link :href="engineeringDataHref('all')" class="self-start rounded-md border border-slate-950 bg-white px-4 py-2.5 text-sm font-black text-slate-950 hover:bg-slate-950 hover:text-white lg:self-center">
                Show All
            </Link>
        </section>

        <section v-if="pageType === 'engineering_data' && showSummaryCards !== false" class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <Link
                v-for="card in summaryCards"
                :key="card.label"
                :href="card.href"
                class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
            >
                <component :is="card.icon" class="size-5 text-emerald-700" />
                <p class="mt-4 text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-black text-slate-950">{{ card.value }}</p>
            </Link>
        </section>

        <div class="mb-5 flex flex-wrap gap-2">
            <Link
                v-for="item in visibleViews"
                :key="item.key"
                :href="item.href"
                class="rounded-md border px-3 py-2 text-sm font-bold"
                :class="item.key === view ? 'border-slate-950 bg-slate-950 text-white hover:bg-slate-900' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
            >
                {{ item.label }}
            </Link>
        </div>

        <form v-if="pageType === 'engineering_data' && showFilters !== false" class="mb-6 rounded-lg border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="applyFilters">
            <div class="grid gap-3 lg:grid-cols-3">
                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Engineering Data / Model</span>
                    <div class="mt-2 flex items-center rounded-md border border-slate-200 bg-white px-3">
                        <Search class="size-4 text-slate-400" />
                        <input
                            v-model="form.keyword"
                            type="search"
                            class="w-full border-0 bg-transparent px-2 py-2 text-sm font-semibold outline-none focus:ring-0"
                            placeholder="Model, series, family, technology..."
                        />
                    </div>
                </label>

                <label class="relative block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Manufacturer</span>
                    <input
                        v-model="form.manufacturer"
                        class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold focus:border-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        autocomplete="off"
                        placeholder="Type at least 2 characters"
                        @input="scheduleManufacturerLookup"
                    />
                    <div
                        v-if="manufacturerLoading || manufacturerSuggestions.length"
                        class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 shadow-xl"
                    >
                        <div v-if="manufacturerLoading" class="px-3 py-2 text-sm font-bold text-slate-500">Searching...</div>
                        <button
                            v-for="suggestion in manufacturerSuggestions"
                            :key="suggestion.value"
                            type="button"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-bold text-slate-800 hover:bg-slate-50"
                            @click="selectManufacturer(suggestion)"
                        >
                            <span>{{ suggestion.label }}</span>
                            <span v-if="suggestion.count !== undefined" class="text-xs text-slate-400">{{ suggestion.count }}</span>
                        </button>
                    </div>
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Status</span>
                    <select v-model="form.status" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold">
                        <option value="">Any status</option>
                        <option v-for="option in filterOptions?.statuses || []" :key="option" :value="option">{{ labelize(option) }}</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Source Type</span>
                    <select v-model="form.source_type" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold">
                        <option value="">Any source</option>
                        <option v-for="option in filterOptions?.source_types || []" :key="option" :value="option">{{ labelize(option) }}</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Updated From</span>
                    <input v-model="form.updated_from" type="date" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold" />
                </label>

                <label class="block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Updated To</span>
                    <input v-model="form.updated_to" type="date" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold" />
                </label>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800">
                    Apply Filters
                </button>
                <button type="button" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50" @click="clearFilters">
                    Clear
                </button>
            </div>
        </form>

        <form v-else-if="pageType === 'engineering_data' && showManufacturerFilter" class="mb-6 rounded-lg border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="applyFilters">
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-end">
                <label class="relative block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Manufacturer</span>
                    <input
                        v-model="form.manufacturer"
                        class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold focus:border-slate-950 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        autocomplete="off"
                        placeholder="Type at least 2 characters"
                        @input="scheduleManufacturerLookup"
                    />
                    <div
                        v-if="manufacturerLoading || manufacturerSuggestions.length"
                        class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 shadow-xl"
                    >
                        <div v-if="manufacturerLoading" class="px-3 py-2 text-sm font-bold text-slate-500">Searching...</div>
                        <button
                            v-for="suggestion in manufacturerSuggestions"
                            :key="suggestion.value"
                            type="button"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-bold text-slate-800 hover:bg-slate-50"
                            @click="selectManufacturer(suggestion)"
                        >
                            <span>{{ suggestion.label }}</span>
                            <span v-if="suggestion.count !== undefined" class="text-xs text-slate-400">{{ suggestion.count }}</span>
                        </button>
                    </div>
                </label>
                <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800">
                    Apply
                </button>
                <button type="button" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50" @click="clearFilters">
                    Clear
                </button>
            </div>
        </form>

        <div v-if="pageType === 'approval_queue'" class="mb-5 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Source</span>
            <Link
                :href="sourceHref(null)"
                class="rounded-full border px-3 py-1.5 font-bold"
                :class="!source ? 'border-emerald-700 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            >
                All
            </Link>
            <Link
                v-for="sourceOption in [
                    { key: 'oem', label: 'OEM' },
                    { key: 'publisher', label: 'Publisher' },
                    { key: 'librarian', label: 'Librarian / Central' },
                ]"
                :key="sourceOption.key"
                :href="sourceHref(sourceOption.key)"
                class="rounded-full border px-3 py-1.5 font-bold"
                :class="source === sourceOption.key ? 'border-emerald-700 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            >
                {{ sourceOption.label }}
            </Link>
        </div>

        <section v-if="pageType === 'engineering_data'" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="font-black">{{ listTitle || 'Engineering Data Operations' }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ listDescription || 'Model-level Structured Engineering Data records derived from datasheets.' }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Manufacturer</th>
                            <th class="px-4 py-3">Model</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="record in records.data" :key="record.uuid || record.id" class="align-top hover:bg-slate-50/70">
                            <td class="px-4 py-4 font-semibold">{{ record.manufacturer || 'Pending' }}</td>
                            <td class="px-4 py-4">
                                <div class="font-black text-slate-950">{{ recordTitle(record) }}</div>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ record.model_series || record.model_name || record.series || 'Series pending' }}
                                    <span v-if="powerLabel(record) !== '—'"> · {{ powerLabel(record) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a :href="record.open_href" class="inline-flex rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800" :title="record.open_href">
                                    Open
                                </a>
                            </td>
                        </tr>
                        <tr v-if="records.data.length === 0">
                            <td colspan="3" class="px-5 py-12 text-center">
                                <p class="font-black text-slate-950">No Engineering Data records found.</p>
                                <p class="mt-2 text-sm text-slate-600">Try another saved view or clear filters.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-else class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <h2 class="font-black">{{ pageType === 'approval_queue' ? 'Review & Approval' : 'Engineering Data' }}</h2>
                <p class="mt-1 text-sm text-slate-600">
                    {{ pageType === 'approval_queue'
                        ? 'Workflow actions across pending approvals, requested changes, rejected items and recently published records.'
                        : 'Model-level structured engineering records separated from datasheet-level operations.' }}
                </p>
            </div>

            <div v-if="records.data.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Item / Model</th>
                            <th class="px-4 py-3">Datasheet</th>
                            <th class="px-4 py-3">Manufacturer</th>
                            <th class="px-4 py-3">Source</th>
                            <th class="px-4 py-3">Submitted By</th>
                            <th class="px-4 py-3">Submitted</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Review</th>
                            <th class="px-4 py-3">Warnings</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="record in records.data" :key="record.uuid || record.id">
                            <td class="px-4 py-4">
                                <div class="font-black text-slate-950">{{ recordTitle(record) }}</div>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <ValidationStatusBadge :status="record.validation_status" />
                                    <QualityGradeBadge :grade="record.validation_grade" :score="record.validation_score" />
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">{{ record.datasheet_title || 'Datasheet pending' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ record.model_series || record.series || record.device_type || 'Series pending' }}</div>
                            </td>
                            <td class="px-4 py-4 font-semibold">{{ record.manufacturer || 'Pending' }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase tracking-[0.12em] text-slate-600">
                                    {{ record.source_label || 'Central' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs text-slate-600">{{ record.submitted_by_label || '—' }}</td>
                            <td class="px-4 py-4 text-xs text-slate-600">
                                {{ record.submitted_at || record.created_at || 'Pending' }}
                            </td>
                            <td class="px-4 py-4"><LifecycleStatusBadge :status="record.status" /></td>
                            <td class="px-4 py-4"><LifecycleStatusBadge :status="record.review_status || 'not_reviewed'" /></td>
                            <td class="px-4 py-4 font-semibold text-slate-700">{{ record.warning_count || 0 }}</td>
                            <td class="px-4 py-4 text-right">
                                <a :href="record.open_href" class="inline-flex rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800" :title="record.open_href">
                                    Open
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="p-10 text-center">
                <h2 class="font-black text-slate-950">Nothing in this queue</h2>
                <p class="mt-2 text-sm text-slate-600">Records will appear here as they move through the publishing workflow.</p>
            </div>
        </section>

        <nav v-if="records.links?.length > 3" class="mt-6 flex flex-wrap gap-2">
            <Link
                v-for="link in records.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-md border px-3 py-2 text-sm font-bold"
                :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'"
                v-html="link.label"
            />
        </nav>
    </LibraryAdminShell>
</template>
