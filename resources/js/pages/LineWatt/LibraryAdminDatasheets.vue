<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, DatabaseZap, FileText, RotateCcw, Search } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

type DatasheetRow = {
    id: number;
    uuid: string;
    title: string;
    manufacturer: string;
    device_type: string;
    family_series?: string | null;
    models_count: number;
    status: string;
    review_status?: string | null;
    compile_status?: string | null;
    pdf_access_mode: string;
    source_type: string;
    uploaded_by: string;
    uploaded_at?: string | null;
    filename: string;
    duplicate_candidate: boolean;
    actions: Record<string, string | null>;
};

const props = defineProps<{
    roleLabel?: string | null;
    title?: string;
    subtitle?: string;
    basePath?: string;
    uploadHref?: string;
    searchHref?: string;
    showSummaryCards?: boolean;
    breadcrumbs?: Array<{ label: string; href?: string | null }>;
    view: string;
    filters: Record<string, string>;
    summary: {
        all: number;
        new_uploads: number;
        pending_review: number;
        failed_compiles: number;
        duplicate_candidates: number;
    };
    filterOptions: {
        statuses: string[];
        review_statuses: string[];
        source_types: string[];
        pdf_access_modes: string[];
    };
    views: Array<{ key: string; label: string; href: string }>;
    datasheets: {
        data: DatasheetRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from?: number | null;
        to?: number | null;
        total: number;
    };
}>();

const form = reactive({
    keyword: props.filters.keyword || '',
    manufacturer: props.filters.manufacturer || '',
    device_type: props.filters.device_type || '',
    status: props.filters.status || '',
    review_status: props.filters.review_status || '',
    source_type: props.filters.source_type || '',
    pdf_access_mode: props.filters.pdf_access_mode || '',
    uploaded_from: props.filters.uploaded_from || '',
    uploaded_to: props.filters.uploaded_to || '',
});
const manufacturerSuggestions = ref<Array<{ label: string; value: string; count?: number }>>([]);
const manufacturerLoading = ref(false);
let manufacturerDebounce: ReturnType<typeof setTimeout> | null = null;

const summaryCards = computed(() => [
    { label: 'All Datasheets', value: props.summary.all, href: `${basePath()}?view=all`, icon: FileText },
    { label: 'New Uploads', value: props.summary.new_uploads, href: `${basePath()}?view=new_uploads`, icon: DatabaseZap },
    { label: 'Pending Review', value: props.summary.pending_review, href: `${basePath()}?view=pending_review`, icon: FileText },
    { label: 'Failed Compiles', value: props.summary.failed_compiles, href: `${basePath()}?view=failed_compiles`, icon: AlertTriangle },
    { label: 'Duplicate Candidates', value: props.summary.duplicate_candidates, href: `${basePath()}?view=duplicate_review`, icon: RotateCcw },
]);
const workflowViews = computed(() => props.views.filter((savedView) => savedView.key !== 'all'));

function basePath(): string {
    return props.basePath || '/admin/library/datasheets';
}

function productTypeHref(deviceType: 'module' | 'inverter'): string {
    const params = new URLSearchParams({ view: props.view });
    params.set('device_type', deviceType);

    return `${basePath()}?${params.toString()}`;
}

function applyFilters(): void {
    router.get(basePath(), {
        view: props.view,
        ...form,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters(): void {
    router.get(basePath(), { view: props.view, device_type: form.device_type || 'module' }, {
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
        source: 'datasheets',
        device_type: form.device_type || 'module',
    });
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

function labelize(value?: string | null): string {
    return value ? value.replaceAll('_', ' ') : '—';
}
</script>

<template>
    <Head :title="title || 'Datasheet Operations'" />

    <LibraryAdminShell
        :title="title || 'Datasheet Operations'"
        :subtitle="subtitle || 'One operational table for uploads, review, compile failures, duplicate candidates and published source datasheets.'"
        :role-label="roleLabel"
        :breadcrumbs="breadcrumbs || [
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Library Data Management' },
            { label: 'Datasheets' },
        ]"
        :primary-action="{ label: 'Upload Datasheet', href: uploadHref || '/admin/library/uploads/new' }"
        :secondary-actions="[{ label: 'Search', href: searchHref || '/search' }]"
    >
        <section class="mb-5 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Product Type</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <Link
                        :href="productTypeHref('module')"
                        class="rounded-md px-4 py-2 text-sm font-black"
                        :class="filters.device_type !== 'inverter' ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    >
                        Modules
                    </Link>
                    <Link
                        :href="productTypeHref('inverter')"
                        class="rounded-md px-4 py-2 text-sm font-black"
                        :class="filters.device_type === 'inverter' ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    >
                        Inverters
                    </Link>
                </div>
            </div>

            <Link :href="`${basePath()}?view=all&device_type=${filters.device_type || 'module'}`" class="self-start rounded-md border border-slate-950 bg-white px-4 py-2.5 text-sm font-black text-slate-950 hover:bg-slate-950 hover:text-white lg:self-center">
                Show All
            </Link>
        </section>

        <section v-if="showSummaryCards !== false" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
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

        <section class="rounded-lg border border-slate-200 bg-white shadow-sm" :class="showSummaryCards !== false ? 'mt-6' : ''">
            <div class="border-b border-slate-100 p-4">
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="savedView in workflowViews"
                        :key="savedView.key"
                        :href="savedView.href"
                        class="rounded-md px-3 py-2 text-sm font-black transition"
                        :class="view === savedView.key ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    >
                        {{ savedView.label }}
                    </Link>
                </div>
            </div>

            <form class="border-b border-slate-100 p-4" @submit.prevent="applyFilters">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Family / Datasheet</span>
                        <div class="mt-2 flex items-center rounded-md border border-slate-200 bg-white px-3">
                            <Search class="size-4 text-slate-400" />
                            <input
                                v-model="form.keyword"
                                type="search"
                                class="w-full border-0 bg-transparent px-2 py-2 text-sm font-semibold outline-none focus:ring-0"
                                placeholder="Family, series, title, filename..."
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

                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800">
                        Apply Filters
                        </button>
                        <button type="button" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50" @click="clearFilters">
                            Clear
                        </button>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Manufacturer</th>
                            <th class="px-4 py-3">Family / Series</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="datasheet in datasheets.data" :key="datasheet.uuid" class="align-top hover:bg-slate-50/70">
                            <td class="px-4 py-4 font-bold text-slate-900">
                                <div>{{ datasheet.manufacturer }}</div>
                                <div class="mt-1 text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ labelize(datasheet.device_type) }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex gap-3">
                                    <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                                        <FileText class="size-4" />
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-950">{{ datasheet.family_series || datasheet.title }}</div>
                                        <div class="mt-1 max-w-xs text-xs leading-5 text-slate-500">{{ datasheet.filename }}</div>
                                        <div v-if="datasheet.duplicate_candidate" class="mt-2 inline-flex rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-black text-amber-800">
                                            Duplicate candidate
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <Link :href="datasheet.actions.review_compilation || '#'" class="inline-flex rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">
                                    Open
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="datasheets.data.length === 0">
                            <td colspan="3" class="px-5 py-12 text-center">
                                <p class="font-black text-slate-950">No datasheets found for this operations view.</p>
                                <p class="mt-2 text-sm text-slate-600">Try another saved view or clear filters.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="datasheets.links?.length" class="flex flex-col gap-3 border-t border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
                <p class="text-sm text-slate-600">
                    Showing {{ datasheets.from || 0 }}–{{ datasheets.to || 0 }} of {{ datasheets.total }}
                </p>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in datasheets.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="rounded-md border px-3 py-2 text-sm font-bold"
                        :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 text-slate-700 hover:bg-slate-50' : 'pointer-events-none border-slate-100 text-slate-300'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </section>
    </LibraryAdminShell>
</template>
