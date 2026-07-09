<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    roleLabel?: string | null;
    stats: {
        total: number;
        added_this_month: number;
        dropped_this_month: number;
        subscribed: number;
    };
    manufacturers: {
        data: Array<any>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from?: number | null;
        to?: number | null;
        total?: number;
    };
    filters: {
        tab: string;
        letter?: string | null;
        q?: string | null;
    };
    hasListingFilter: boolean;
    alphabet: string[];
}>();

const search = ref(props.filters.q || '');
const suggestions = ref<Array<{ label: string; value: string; url: string }>>([]);
const loading = ref(false);
let debounce: ReturnType<typeof setTimeout> | null = null;

const tabs = [
    { label: 'All', value: 'all' },
    { label: 'Modules', value: 'modules' },
    { label: 'Inverters', value: 'inverters' },
];

const statsCards = computed(() => [
    { label: 'Total Manufacturers', value: props.stats.total },
    { label: 'Added This Month', value: props.stats.added_this_month },
    { label: 'Dropped This Month', value: props.stats.dropped_this_month },
    { label: 'Subscribed Manufacturers', value: props.stats.subscribed },
]);

function href(params: Record<string, string | null | undefined>): string {
    const next = new URLSearchParams();
    next.set('tab', params.tab || props.filters.tab || 'all');

    const letter = params.letter ?? props.filters.letter;
    const query = params.q ?? props.filters.q;

    if (letter) next.set('letter', letter);
    if (query) next.set('q', query);

    return `/admin/library/manufacturers?${next.toString()}`;
}

function scheduleSearch(): void {
    if (debounce !== null) clearTimeout(debounce);

    if (search.value.trim().length < 2) {
        suggestions.value = [];
        loading.value = false;
        return;
    }

    loading.value = true;
    debounce = setTimeout(fetchSuggestions, 300);
}

async function fetchSuggestions(): Promise<void> {
    const params = new URLSearchParams({ q: search.value.trim(), tab: props.filters.tab || 'all' });
    const response = await fetch(`/admin/library/manufacturers/search?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });

    suggestions.value = response.ok ? await response.json() : [];
    loading.value = false;
}

function submitSearch(): void {
    const query = search.value.trim();
    router.get('/admin/library/manufacturers', {
        tab: props.filters.tab || 'all',
        q: query || undefined,
    });
}

function selectSuggestion(suggestion: { value: string }): void {
    search.value = suggestion.value;
    suggestions.value = [];
    submitSearch();
}

function clearFilters(): void {
    router.get('/admin/library/manufacturers', { tab: props.filters.tab || 'all' });
}

function labelize(value?: string | null): string {
    return value ? value.replaceAll('_', ' ') : '—';
}
</script>

<template>
    <Head title="All Manufacturers" />

    <LibraryAdminShell
        title="All Manufacturers"
        subtitle="Canonical manufacturer directory across subscribed OEMs, datasheets, and Structured Engineering Data."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Equipment Manufacturers' },
            { label: 'All Manufacturers' },
        ]"
    >
        <section class="grid gap-4 md:grid-cols-4">
            <article v-for="card in statsCards" :key="card.label" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">{{ card.label }}</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ card.value }}</p>
            </article>
        </section>

        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Product Type</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <Link
                            v-for="tab in tabs"
                            :key="tab.value"
                            :href="href({ tab: tab.value, letter: filters.letter, q: filters.q })"
                            class="rounded-md px-4 py-2 text-sm font-black"
                            :class="filters.tab === tab.value ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700 hover:bg-slate-50'"
                        >
                            {{ tab.label }}
                        </Link>
                    </div>
                </div>

                <form class="relative w-full xl:max-w-md" @submit.prevent="submitSearch">
                    <label class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Search manufacturers</label>
                    <div class="mt-2 flex gap-2">
                        <input
                            v-model="search"
                            class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold"
                            autocomplete="off"
                            placeholder="Search manufacturers..."
                            @input="scheduleSearch"
                        />
                        <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white" type="submit">Search</button>
                    </div>
                    <div v-if="loading || suggestions.length" class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 shadow-xl">
                        <div v-if="loading" class="px-3 py-2 text-sm font-bold text-slate-500">Searching...</div>
                        <button
                            v-for="suggestion in suggestions"
                            :key="suggestion.value"
                            type="button"
                            class="w-full rounded-md px-3 py-2 text-left text-sm font-bold text-slate-800 hover:bg-slate-50"
                            @click="selectSuggestion(suggestion)"
                        >
                            {{ suggestion.label }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="mt-5 flex flex-wrap gap-1.5">
                <Link
                    v-for="letter in alphabet"
                    :key="letter"
                    :href="href({ tab: filters.tab, letter, q: null })"
                    class="rounded-md border px-3 py-2 text-sm font-black"
                    :class="filters.letter === letter && !filters.q ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                >
                    {{ letter }}
                </Link>
            </div>
        </section>

        <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div v-if="manufacturers.data.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Manufacturer</th>
                            <th class="px-4 py-3">Headquarters Country</th>
                            <th class="px-4 py-3">Device Types</th>
                            <th class="px-4 py-3">Datasheets</th>
                            <th class="px-4 py-3">Engineering Data</th>
                            <th class="px-4 py-3">Subscriber Status</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Last Updated</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="manufacturer in manufacturers.data" :key="manufacturer.slug" class="hover:bg-slate-50/70">
                            <td class="px-4 py-4">
                                <div class="font-black text-slate-950">{{ manufacturer.name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ manufacturer.slug }}</div>
                            </td>
                            <td class="px-4 py-4">{{ manufacturer.headquarters_country }}</td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <span v-for="device in manufacturer.device_types" :key="device" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black capitalize text-slate-700">{{ labelize(device) }}</span>
                                    <span v-if="!manufacturer.device_types?.length" class="text-slate-400">—</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 font-semibold">{{ manufacturer.datasheets }}</td>
                            <td class="px-4 py-4 font-semibold">{{ manufacturer.records }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-black capitalize text-slate-700">{{ labelize(manufacturer.subscriber_status) }}</span>
                            </td>
                            <td class="px-4 py-4 capitalize">{{ labelize(manufacturer.status) }}</td>
                            <td class="px-4 py-4">{{ manufacturer.last_updated }}</td>
                            <td class="px-4 py-4 text-right">
                                <Link :href="manufacturer.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Open</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else-if="!hasListingFilter" class="p-12 text-center">
                <h2 class="text-xl font-black text-slate-950">Select a letter or search manufacturers.</h2>
                <p class="mt-2 text-sm text-slate-600">The product tab controls whether the alphabetic listing is across all products, modules, or inverters.</p>
            </div>
            <div v-else class="p-12 text-center">
                <h2 class="text-xl font-black text-slate-950">No manufacturers found.</h2>
                <button class="mt-4 rounded-md border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50" type="button" @click="clearFilters">
                    Clear filters
                </button>
            </div>

            <div v-if="manufacturers.links?.length > 3" class="flex flex-col gap-3 border-t border-slate-100 p-5 md:flex-row md:items-center md:justify-between">
                <p class="text-sm text-slate-600">Showing {{ manufacturers.from || 0 }}–{{ manufacturers.to || 0 }} of {{ manufacturers.total }}</p>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="link in manufacturers.links"
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
