<script setup lang="ts">
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import { GitCompare, Search, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    records: {
        data: any[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        last_page: number;
    };
    seedRecord?: any | null;
    selectedRecords?: any[];
    filters: {
        q?: string;
        device_type?: string;
    };
}>();

const { dir, t } = useLineWattI18n();
const q = ref(props.filters.q || '');
const deviceType = ref(props.filters.device_type === 'inverter' ? 'inverter' : 'module');
const selected = ref<any[]>([]);
const message = ref('');

onMounted(() => {
    for (const record of props.selectedRecords || []) {
        addSelectedRecord(record);
    }

    if (props.seedRecord) {
        addSelectedRecord(props.seedRecord);
    }
});

const selectedType = computed(() => selected.value[0]?.device_type || null);
const canCompare = computed(() => selected.value.length >= 2);
const slots = computed(() => Math.max(0, 3 - selected.value.length));

function recordKey(record: any): string {
    return String(record.id ?? record.uuid);
}

function displayRecord(record: any): string {
    return record.display_name || record.model_name || record.model_series || t('compare.engineeringRecord');
}

function isSelected(record: any): boolean {
    return selected.value.some((item) => recordKey(item) === recordKey(record));
}

function addSelectedRecord(record: any): void {
    if (!record || isSelected(record) || selected.value.length >= 3) {
        return;
    }

    if (selectedType.value && selectedType.value !== record.device_type) {
        return;
    }

    selected.value = [...selected.value, record];
}

function toggle(record: any): void {
    message.value = '';

    if (isSelected(record)) {
        selected.value = selected.value.filter((item) => recordKey(item) !== recordKey(record));
        return;
    }

    if (selectedType.value && selectedType.value !== record.device_type) {
        message.value = t('myLibrary.compareTypeError');
        return;
    }

    if (selected.value.length >= 3) {
        message.value = t('myLibrary.compareMaxError');
        return;
    }

    selected.value = [...selected.value, record];
}

function remove(record: any): void {
    selected.value = selected.value.filter((item) => recordKey(item) !== recordKey(record));
}

function clear(): void {
    selected.value = [];
    message.value = '';
}

function compare(): void {
    if (selected.value.length < 2) {
        message.value = t('myLibrary.compareMinError');
        return;
    }

    router.get('/compare', { records: selected.value.map(recordKey).join(',') });
}

function submitSearch(): void {
    router.get('/compare/select', {
        q: q.value,
        device_type: deviceType.value,
        selected: selected.value.map(recordKey).join(','),
    }, {
        preserveState: false,
        replace: true,
    });
}

function switchDeviceType(type: 'module' | 'inverter'): void {
    deviceType.value = type;
    selected.value = selected.value.filter((record) => record.device_type === type);
    message.value = '';
    submitSearch();
}
</script>

<template>
    <Head :title="t('compare.selectRecords')" />

    <div class="min-h-screen bg-slate-50 pb-44 text-slate-950" :dir="dir">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">{{ t('compare.title') }}</p>
                <h1 class="mt-2 text-4xl font-black tracking-tight text-slate-950">{{ t('compare.selectRecords') }}</h1>
                <p class="mt-2 max-w-3xl text-slate-600">{{ t('compare.chooseTwoOrThree') }}</p>

                <div class="mt-6 inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                    <button
                        class="rounded-md px-4 py-2 text-sm font-black"
                        :class="deviceType === 'module' ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-white'"
                        type="button"
                        @click="switchDeviceType('module')"
                    >
                        {{ t('manufacturerDirectory.modules') }}
                    </button>
                    <button
                        class="rounded-md px-4 py-2 text-sm font-black"
                        :class="deviceType === 'inverter' ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-white'"
                        type="button"
                        @click="switchDeviceType('inverter')"
                    >
                        {{ t('manufacturerDirectory.inverters') }}
                    </button>
                </div>

                <form class="mt-4 grid gap-3 md:grid-cols-[1fr_auto]" @submit.prevent="submitSearch">
                    <div class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3">
                        <Search class="size-4 text-slate-400" />
                        <input
                            v-model="q"
                            class="h-12 flex-1 bg-transparent text-sm font-semibold outline-none"
                            :placeholder="deviceType === 'inverter' ? t('compare.searchInverterPlaceholder') : t('compare.searchModulePlaceholder')"
                            type="search"
                        />
                    </div>
                    <button class="h-12 rounded-md bg-slate-950 px-5 text-sm font-bold text-white" type="submit">{{ t('myLibrary.search') }}</button>
                </form>
            </section>

            <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="divide-y divide-slate-100">
                    <div
                        v-for="record in records.data"
                        :key="recordKey(record)"
                        class="grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-center"
                        :class="isSelected(record) ? 'bg-emerald-50/60' : ''"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase tracking-[0.12em] text-slate-600">{{ record.device_type === 'inverter' ? t('myLibrary.inverter') : t('myLibrary.module') }}</span>
                                <span v-if="record.source_label" class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700">{{ record.source_label }}</span>
                            </div>
                            <h2 class="mt-2 truncate text-base font-black">{{ displayRecord(record) }}</h2>
                            <p class="mt-1 truncate text-sm text-slate-600">
                                {{ record.manufacturer || t('myLibrary.unknownManufacturer') }}
                                <span v-if="record.model_series"> · {{ record.model_series }}</span>
                                <span v-if="record.power_class_w"> · {{ record.power_class_w }} W</span>
                                <span v-else-if="record.power_class_kw"> · {{ record.power_class_kw }} kW</span>
                            </p>
                        </div>
                        <button
                            class="rounded-md border px-4 py-2 text-sm font-bold"
                            :class="isSelected(record) ? 'border-emerald-200 bg-emerald-100 text-emerald-800' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                            type="button"
                            @click="toggle(record)"
                        >
                            {{ isSelected(record) ? t('compare.selected') : t('compare.select') }}
                        </button>
                    </div>
                    <div v-if="records.data.length === 0" class="p-10 text-center text-sm text-slate-600">{{ t('compare.noVisibleRecords') }}</div>
                </div>
            </section>

            <nav v-if="records.last_page > 1" class="mt-6 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in records.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded-md border px-3 py-2 text-sm font-bold"
                    :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'pointer-events-none opacity-40'"
                    v-html="link.label"
                />
            </nav>
        </main>

        <div v-if="selected.length" class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-4 shadow-[0_-12px_32px_rgba(15,23,42,0.12)] backdrop-blur">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-3 py-1.5 text-xs font-black uppercase tracking-[0.12em] text-white">
                            <GitCompare class="size-3.5" />
                            {{ selectedType === 'inverter' ? t('myLibrary.inverter') : t('myLibrary.module') }} {{ t('nav.compare') }}
                        </span>
                        <span v-if="message" class="text-sm font-bold text-amber-700">{{ message }}</span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span v-for="record in selected" :key="recordKey(record)" class="inline-flex max-w-xs items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-900">
                            <span class="truncate">{{ displayRecord(record) }}</span>
                            <button class="rounded-full p-0.5 hover:bg-emerald-100" type="button" @click="remove(record)">
                                <X class="size-4" />
                            </button>
                        </span>
                        <span v-for="slot in slots" :key="slot" class="rounded-md border border-dashed border-slate-300 px-3 py-2 text-sm font-semibold text-slate-500">{{ t('myLibrary.addOneMore') }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700" type="button" @click="clear">{{ t('myLibrary.clear') }}</button>
                    <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white disabled:opacity-45" :disabled="!canCompare" type="button" @click="compare">{{ t('nav.compare') }}</button>
                </div>
            </div>
        </div>
    </div>
</template>
