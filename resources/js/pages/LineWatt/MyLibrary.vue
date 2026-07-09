<script setup lang="ts">
import AdPromotionPlayer from '@/components/linewatt/AdPromotionPlayer.vue';
import EngineeringRecordCard from '@/components/linewatt/EngineeringRecordCard.vue';
import EntitlementDiagnostics from '@/components/linewatt/EntitlementDiagnostics.vue';
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import MetricCard from '@/components/linewatt/MetricCard.vue';
import PlaceholderAction from '@/components/linewatt/PlaceholderAction.vue';
import ReviewStatusBadge from '@/components/linewatt/ReviewStatusBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import WorkspaceHeader from '@/components/linewatt/WorkspaceHeader.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, ClipboardCheck, Factory, FileUp, GitCompare, HardDrive, Search, Share, X, Zap } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';

type ManufacturerSuggestion = {
    label: string;
    value: string;
    url: string;
};

const props = defineProps<{
    summary: {
        storage_used_percent: number;
        storage_used_label: string;
        storage_quota_label: string;
        private_uploads: number;
        compiled_records: number;
        needs_review: number;
        recent_exports: number;
        module_records: number;
        inverter_records: number;
        upload_status_counts: Record<string, number>;
    };
    reviewRecords: any[];
    recentRecords: any[];
}>();

const libraryQuery = ref('');
const myLibraryQuery = ref('');
const manufacturerQuery = ref('');
const compareMessage = ref('');
const oemSelectionMessage = ref('');
const selectedCompareRecords = ref<any[]>([]);
const selectedManufacturer = ref<ManufacturerSuggestion | null>(null);
const manufacturerSuggestions = ref<ManufacturerSuggestion[]>([]);
const manufacturerLoading = ref(false);
let manufacturerDebounce: ReturnType<typeof setTimeout> | null = null;
const { dir, t } = useLineWattI18n();

function submitLibrarySearch(): void {
    router.get('/search/results', {
        scope: 'both',
        q: libraryQuery.value,
    }, {
        preserveState: false,
    });
}

function submitMyLibrarySearch(): void {
    router.get('/search/results', {
        scope: 'my-library',
        q: myLibraryQuery.value,
    }, {
        preserveState: false,
    });
}

function submitOemSearch(): void {
    const target = selectedManufacturer.value?.url || '';

    if (target === '') {
        oemSelectionMessage.value = t('myLibrary.selectOemPrompt');
        return;
    }

    router.get(target, {}, {
        preserveState: false,
    });
}

function scheduleManufacturerLookup(): void {
    if (manufacturerDebounce !== null) {
        clearTimeout(manufacturerDebounce);
    }

    const query = manufacturerQuery.value.trim();
    if (selectedManufacturer.value?.label !== query) {
        selectedManufacturer.value = null;
    }
    oemSelectionMessage.value = '';
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
        q: manufacturerQuery.value.trim(),
        source: 'manufacturer-directory',
    });
    const response = await fetch(`/search/manufacturers?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });

    manufacturerSuggestions.value = response.ok ? await response.json() : [];
    manufacturerLoading.value = false;
}

function openManufacturer(suggestion: ManufacturerSuggestion): void {
    selectedManufacturer.value = suggestion;
    manufacturerQuery.value = suggestion.label;
    manufacturerSuggestions.value = [];
    oemSelectionMessage.value = '';
}

const compareDeviceType = computed(() => selectedCompareRecords.value[0]?.device_type ?? null);
const compareCanStart = computed(() => selectedCompareRecords.value.length >= 2);
const compareSlots = computed(() => Math.max(0, 3 - selectedCompareRecords.value.length));
const canOpenSelectedManufacturer = computed(() => selectedManufacturer.value !== null);

function recordKey(record: any): string {
    return String(record.id ?? record.uuid ?? record.href ?? record.display_name);
}

function displayRecord(record: any): string {
    return record.display_name || record.model_name || record.model_series || 'Engineering Record';
}

function isCompareSelected(record: any): boolean {
    return selectedCompareRecords.value.some((selected) => recordKey(selected) === recordKey(record));
}

function compareDisabled(record: any): boolean {
    return false;
}

function toggleCompare(record: any): void {
    compareMessage.value = '';
    const key = recordKey(record);

    if (isCompareSelected(record)) {
        selectedCompareRecords.value = selectedCompareRecords.value.filter((selected) => recordKey(selected) !== key);
        return;
    }

    if (compareDeviceType.value !== null && compareDeviceType.value !== record.device_type) {
        compareMessage.value = t('myLibrary.compareTypeError');
        return;
    }

    if (selectedCompareRecords.value.length >= 3) {
        compareMessage.value = t('myLibrary.compareMaxError');
        return;
    }

    selectedCompareRecords.value = [...selectedCompareRecords.value, record];
}

function removeCompareRecord(record: any): void {
    selectedCompareRecords.value = selectedCompareRecords.value.filter((selected) => recordKey(selected) !== recordKey(record));
}

function clearCompare(): void {
    selectedCompareRecords.value = [];
    compareMessage.value = '';
}

function startCompare(): void {
    if (selectedCompareRecords.value.length < 2) {
        compareMessage.value = t('myLibrary.compareMinError');
        return;
    }

    router.get('/compare', {
        records: selectedCompareRecords.value.map(recordKey).join(','),
    }, {
        preserveState: false,
    });
}
</script>

<template>
    <Head :title="t('myLibrary.headTitle')" />

    <div class="min-h-screen bg-slate-50 text-slate-950" :dir="dir">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <section class="grid gap-6 lg:grid-cols-[1fr_320px]">
                <div class="space-y-6">
                    <WorkspaceHeader
                        :eyebrow="t('myLibrary.eyebrow')"
                        :title="t('myLibrary.title')"
                        :description="t('myLibrary.description')"
                        :workspace-name="t('nav.myPrivateDatasets')"
                        :tenant-or-partner="t('myLibrary.privateTenant')"
                    />

                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div>
                            <h2 class="text-lg font-black text-slate-950">{{ t('myLibrary.engineeringSearch') }}</h2>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ t('myLibrary.engineeringSearchHelp') }}
                            </p>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <Link
                                href="/search/modules"
                                class="rounded-md border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                            >
                                {{ t('myLibrary.powerSearchModules') }}
                            </Link>
                            <Link
                                href="/search/inverters"
                                class="rounded-md border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                            >
                                {{ t('myLibrary.powerSearchInverters') }}
                            </Link>
                        </div>

                        <form class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="submitLibrarySearch">
                            <label class="sr-only" for="library-wide-search">Search all products</label>
                            <div class="flex min-w-0 flex-1 items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3">
                                <Search class="size-4 shrink-0 text-slate-400" />
                                <input
                                    id="library-wide-search"
                                    v-model="libraryQuery"
                                    class="h-12 min-w-0 flex-1 bg-transparent text-sm font-semibold text-slate-950 outline-none placeholder:text-slate-400"
                                    :placeholder="t('myLibrary.searchAllProducts')"
                                    type="search"
                                />
                            </div>
                            <button class="h-12 rounded-md bg-slate-950 px-5 text-sm font-bold text-white hover:bg-slate-800" type="submit">
                                {{ t('myLibrary.search') }}
                            </button>
                        </form>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-lg font-black text-slate-950">{{ t('myLibrary.searchOems') }}</h2>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ t('myLibrary.searchOemsHelp') }}
                                </p>
                            </div>
                            <Link
                                href="/manufacturers"
                                class="inline-flex h-10 shrink-0 items-center justify-center rounded-md border border-slate-200 px-4 text-sm font-black text-slate-700 hover:bg-slate-50"
                            >
                                {{ t('manufacturer.showAll') }}
                            </Link>
                        </div>

                        <form class="relative mt-5 flex flex-col gap-3 sm:flex-row" @submit.prevent="submitOemSearch">
                            <label class="sr-only" for="oem-search">Search OEMs</label>
                            <div class="flex min-w-0 flex-1 items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3">
                                <Factory class="size-4 shrink-0 text-slate-400" />
                                <input
                                    id="oem-search"
                                    v-model="manufacturerQuery"
                                    autocomplete="off"
                                    class="h-12 min-w-0 flex-1 bg-transparent text-sm font-semibold text-slate-950 outline-none placeholder:text-slate-400"
                                    :placeholder="t('myLibrary.searchOemsPlaceholder')"
                                    type="search"
                                    @focus="scheduleManufacturerLookup"
                                    @input="scheduleManufacturerLookup"
                                />
                            </div>
                            <button
                                class="h-12 rounded-md bg-slate-950 px-5 text-sm font-bold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-45"
                                type="submit"
                                :disabled="!canOpenSelectedManufacturer"
                            >
                                {{ t('myLibrary.openOem') }}
                            </button>
                            <div
                                v-if="manufacturerSuggestions.length || manufacturerLoading"
                                class="absolute left-0 right-0 top-14 z-20 max-h-64 overflow-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg sm:right-36"
                            >
                                <div v-if="manufacturerLoading" class="px-3 py-2 text-sm text-slate-500">{{ t('myLibrary.searching') }}</div>
                                <button
                                    v-for="suggestion in manufacturerSuggestions"
                                    :key="suggestion.value"
                                    class="block w-full px-3 py-2 text-left text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    type="button"
                                    @click="openManufacturer(suggestion)"
                                >
                                    {{ suggestion.label }}
                                </button>
                            </div>
                            <p v-if="oemSelectionMessage" class="absolute top-14 text-xs font-bold text-amber-700 sm:left-0">
                                {{ oemSelectionMessage }}
                            </p>
                        </form>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black text-slate-950">{{ t('myLibrary.actionsTitle') }}</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ t('myLibrary.actionsHelp') }}
                        </p>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <Link
                                href="/my-library/uploads/new"
                                class="inline-flex min-h-20 items-center gap-3 rounded-lg border border-slate-200 bg-slate-950 p-4 text-white hover:bg-slate-800"
                            >
                                <FileUp class="size-5" />
                                <span>
                                    <span class="block text-sm font-black">{{ t('myLibrary.compilePdf') }}</span>
                                    <span class="block text-xs text-slate-300">{{ t('myLibrary.compilePdfHelp') }}</span>
                                </span>
                            </Link>
                            <a
                                href="#review-queue"
                                class="inline-flex min-h-20 items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-slate-800 hover:bg-slate-50"
                            >
                                <ClipboardCheck class="size-5 text-amber-700" />
                                <span>
                                    <span class="block text-sm font-black">2. {{ t('myLibrary.reviewQueue') }}</span>
                                    <span class="block text-xs text-slate-500">{{ t('myLibrary.reviewQueueHelp') }}</span>
                                </span>
                            </a>
                            <Link
                                href="/search"
                                class="inline-flex min-h-20 items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-slate-800 hover:bg-slate-50"
                            >
                                <Search class="size-5 text-emerald-700" />
                                <span>
                                    <span class="block text-sm font-black">3. {{ t('myLibrary.engineeringSearch') }}</span>
                                    <span class="block text-xs text-slate-500">{{ t('myLibrary.searchActionHelp') }}</span>
                                </span>
                            </Link>
                            <Link
                                href="/compare/select"
                                class="inline-flex min-h-20 items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-slate-800 hover:bg-slate-50"
                            >
                                <GitCompare class="size-5 text-sky-700" />
                                <span>
                                    <span class="block text-sm font-black">4. {{ t('myLibrary.compare') }}</span>
                                    <span class="block text-xs text-slate-500">{{ t('myLibrary.compareHelp') }}</span>
                                </span>
                            </Link>
                            <button
                                class="inline-flex min-h-20 cursor-not-allowed items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-left text-slate-400"
                                disabled
                                :title="t('myLibrary.comingNext')"
                                type="button"
                            >
                                <Share class="size-5" />
                                <span>
                                    <span class="block text-sm font-black">5. {{ t('myLibrary.exports') }}</span>
                                    <span class="block text-xs">{{ t('myLibrary.comingNext') }}</span>
                                </span>
                            </button>
                            <a
                                href="#activity"
                                class="inline-flex min-h-20 items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-slate-800 hover:bg-slate-50"
                            >
                                <Archive class="size-5 text-violet-700" />
                                <span>
                                    <span class="block text-sm font-black">6. {{ t('myLibrary.activity') }}</span>
                                    <span class="block text-xs text-slate-500">{{ t('myLibrary.activityHelp') }}</span>
                                </span>
                            </a>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-lg font-black text-slate-950">{{ t('myLibrary.privateSearch') }}</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ t('myLibrary.privateSearchHelp') }}
                        </p>

                        <form class="mt-5 flex flex-col gap-3 sm:flex-row" @submit.prevent="submitMyLibrarySearch">
                            <label class="sr-only" for="my-library-record-search">Search My Private Datasets</label>
                            <div class="flex min-w-0 flex-1 items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3">
                                <Search class="size-4 shrink-0 text-slate-400" />
                                <input
                                    id="my-library-record-search"
                                    v-model="myLibraryQuery"
                                    class="h-12 min-w-0 flex-1 bg-transparent text-sm font-semibold text-slate-950 outline-none placeholder:text-slate-400"
                                    :placeholder="t('myLibrary.privateSearchPlaceholder')"
                                    type="search"
                                />
                            </div>
                            <button class="h-12 rounded-md border border-slate-200 px-5 text-sm font-bold text-slate-700 hover:bg-slate-50" type="submit">
                                {{ t('myLibrary.searchPrivate') }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="space-y-4">
                    <AdPromotionPlayer />
                    <Link href="/my-library/storage" class="block rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="font-bold text-slate-950">{{ t('myLibrary.storageUsage') }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ summary.storage_used_label }} / {{ summary.storage_quota_label }}</p>
                            </div>
                            <HardDrive class="size-5 text-emerald-700" />
                        </div>
                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${summary.storage_used_percent}%` }" />
                        </div>
                        <p class="mt-2 text-sm font-semibold text-slate-700">
                            {{ summary.storage_used_percent }}% {{ t('myLibrary.used') }}
                        </p>
                        <p class="mt-3 text-sm font-black text-emerald-700">{{ t('storage.manageStorage') }} →</p>
                    </Link>
                </div>
            </section>

            <section class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <MetricCard :icon="Archive" :label="t('myLibrary.privateUploads')" :value="summary.private_uploads" tone="sky" />
                <MetricCard :icon="Zap" :label="t('myLibrary.engineeringRecords')" :value="summary.compiled_records" tone="emerald" />
                <MetricCard :icon="ClipboardCheck" :label="t('myLibrary.needsReview')" :value="summary.needs_review" tone="amber" />
                <MetricCard :icon="Share" :label="t('myLibrary.recentExports')" :value="summary.recent_exports" tone="violet" />
            </section>

            <section class="mt-8 grid gap-6 lg:grid-cols-[1fr_320px]">
                <div class="space-y-8">
                    <div id="review-queue">
                        <div>
                            <div>
                                <h2 class="text-xl font-black text-slate-950">{{ t('myLibrary.reviewQueueTitle') }}</h2>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ t('myLibrary.reviewQueueDescription') }}
                                </p>
                            </div>
                        </div>

                        <div v-if="reviewRecords.length" class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <div class="divide-y divide-slate-100">
                                <div
                                    v-for="record in reviewRecords"
                                    :key="record.uuid || record.id"
                                    class="grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-center"
                                >
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase tracking-[0.12em] text-slate-600">
                                                {{ record.device_type === 'inverter' ? t('myLibrary.inverter') : t('myLibrary.module') }}
                                            </span>
                                            <LifecycleStatusBadge :status="record.status" />
                                            <ReviewStatusBadge :status="record.review_status" />
                                            <ValidationStatusBadge :status="record.validation_status" />
                                        </div>
                                        <h3 class="mt-2 truncate text-base font-black text-slate-950">
                                            {{ displayRecord(record) }}
                                        </h3>
                                        <p class="mt-1 truncate text-sm text-slate-600">
                                            {{ record.manufacturer || t('myLibrary.unknownManufacturer') }}
                                            <span v-if="record.model_series"> · {{ record.model_series }}</span>
                                            <span v-if="record.power_class_w"> · {{ record.power_class_w }} W</span>
                                            <span v-else-if="record.power_class_kw"> · {{ record.power_class_kw }} kW</span>
                                        </p>
                                    </div>
                                    <Link
                                        :href="record.review_href || '#'"
                                        class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800"
                                    >
                                        {{ t('manufacturer.review') }}
                                    </Link>
                                </div>
                            </div>
                            <div class="border-t border-slate-100 bg-slate-50 px-4 py-3 text-right">
                                <Link
                                    href="/my-library/review-queue"
                                    class="text-sm font-black text-emerald-700 hover:text-emerald-800"
                                >
                                    {{ t('myLibrary.showAllReviews') }}
                                </Link>
                            </div>
                        </div>
                        <div v-else class="mt-5 rounded-lg border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-600">
                            {{ t('myLibrary.noReviews') }}
                        </div>
                    </div>

                    <div id="engineering-records">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-black text-slate-950">{{ t('myLibrary.recentRecords') }}</h2>
                                <p class="mt-1 text-sm text-slate-600">
                                    {{ t('myLibrary.recentRecordsHelp') }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Link
                                    href="/search/results?scope=my-library"
                                    class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                                >
                                    {{ t('manufacturer.showAll') }}
                                </Link>
                                <PlaceholderAction label="Export" />
                            </div>
                        </div>

                        <div v-if="recentRecords.length" class="mt-5 grid gap-4 md:grid-cols-2">
                            <EngineeringRecordCard
                                v-for="record in recentRecords"
                                :key="record.uuid || record.id"
                                :record="record"
                            />
                        </div>
                        <div v-else class="mt-5 rounded-lg border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-600">
                            {{ t('myLibrary.noRecords') }}
                        </div>
                    </div>
                </div>

                <aside class="space-y-4">
                    <div id="activity" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold text-slate-950">{{ t('myLibrary.moduleInverterCounters') }}</h3>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-md bg-slate-50 p-3">
                                <dt class="text-slate-500">{{ t('myLibrary.modules') }}</dt>
                                <dd class="mt-1 text-xl font-black">{{ summary.module_records }}</dd>
                            </div>
                            <div class="rounded-md bg-slate-50 p-3">
                                <dt class="text-slate-500">{{ t('myLibrary.inverters') }}</dt>
                                <dd class="mt-1 text-xl font-black">{{ summary.inverter_records }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold text-slate-950">{{ t('manufacturer.recentActivity') }}</h3>
                        <div class="mt-4 space-y-3 text-sm text-slate-600">
                            <div class="rounded-md bg-slate-50 p-3">
                                <div class="font-bold text-slate-900">{{ t('myLibrary.uploadCompileStatus') }}</div>
                                <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    <div v-for="(count, status) in summary.upload_status_counts" :key="status" class="flex items-center justify-between rounded border border-slate-200 bg-white px-2 py-1.5">
                                        <dt class="capitalize text-slate-500">{{ String(status).replace('_', ' ') }}</dt>
                                        <dd class="font-black text-slate-950">{{ count }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <p class="rounded-md bg-slate-50 p-3">{{ t('myLibrary.reviewActivityPlaceholder') }}</p>
                            <p class="rounded-md bg-slate-50 p-3">{{ t('myLibrary.exportActivityPlaceholder') }}</p>
                        </div>
                    </div>
                    <EntitlementDiagnostics />
                </aside>
            </section>
        </main>

        <div
            v-if="selectedCompareRecords.length > 0"
            class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-4 shadow-[0_-12px_32px_rgba(15,23,42,0.12)] backdrop-blur"
            role="region"
            aria-label="Compare tray"
        >
            <div class="mx-auto flex max-w-7xl flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-3 py-1.5 text-xs font-black uppercase tracking-[0.12em] text-white">
                            <GitCompare class="size-3.5" />
                            {{ compareDeviceType === 'inverter' ? t('myLibrary.compareInverters') : t('myLibrary.compareModules') }}
                        </span>
                        <span v-if="compareMessage" class="text-sm font-semibold text-amber-700">{{ compareMessage }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="record in selectedCompareRecords"
                            :key="recordKey(record)"
                            class="inline-flex max-w-xs items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-900"
                        >
                            <span class="truncate">{{ displayRecord(record) }}</span>
                            <button
                                class="rounded-full p-0.5 text-emerald-700 hover:bg-emerald-100"
                                type="button"
                                :aria-label="`Remove ${displayRecord(record)} from comparison`"
                                @click="removeCompareRecord(record)"
                            >
                                <X class="size-4" />
                            </button>
                        </span>
                        <span
                            v-for="slot in compareSlots"
                            :key="slot"
                            class="inline-flex items-center rounded-md border border-dashed border-slate-300 px-3 py-2 text-sm font-semibold text-slate-500"
                        >
                            {{ t('myLibrary.addOneMore') }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <button
                        class="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                        type="button"
                        @click="clearCompare"
                    >
                        {{ t('myLibrary.clear') }}
                    </button>
                    <button
                        class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-45"
                        type="button"
                        :disabled="!compareCanStart"
                        @click="startCompare"
                    >
                        {{ t('myLibrary.compare') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
