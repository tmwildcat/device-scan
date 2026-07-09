<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type ManufacturerOption = {
    label: string;
    value: number;
    plan?: string | null;
    subscription_status?: string | null;
    current_champion?: number | null;
};

const props = defineProps<{
    roleLabel?: string | null;
    champion: any;
    manufacturers: Array<any>;
}>();

const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>();
const query = ref('');
const options = ref<ManufacturerOption[]>([]);
const selectedManufacturer = ref<ManufacturerOption | null>(null);
const searching = ref(false);
let debounceTimer: number | undefined;

const pauseLabel = computed(() => (props.champion.status === 'paused' ? 'Unpause' : 'Pause'));
const suspendLabel = computed(() => (props.champion.status === 'suspended' ? 'Reinstate' : 'Suspend'));
const statusClass = computed(() => {
    if (props.champion.status === 'active') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    }

    if (props.champion.status === 'paused') {
        return 'border-amber-200 bg-amber-50 text-amber-800';
    }

    if (props.champion.status === 'suspended') {
        return 'border-red-200 bg-red-50 text-red-800';
    }

    return 'border-slate-200 bg-slate-50 text-slate-700';
});

watch(query, (value) => {
    selectedManufacturer.value = null;
    window.clearTimeout(debounceTimer);

    if (value.trim().length < 2) {
        options.value = [];
        return;
    }

    debounceTimer = window.setTimeout(async () => {
        searching.value = true;
        const url = new URL(props.champion.routes.manufacturer_search, window.location.origin);
        url.searchParams.set('q', value.trim());

        try {
            const response = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
            options.value = response.ok ? await response.json() : [];
        } finally {
            searching.value = false;
        }
    }, 300);
});

function togglePause(): void {
    router.post(props.champion.routes.pause, {}, { preserveScroll: true });
}

function toggleSuspend(): void {
    router.post(props.champion.routes.suspend, {}, { preserveScroll: true });
}

function chooseManufacturer(option: ManufacturerOption): void {
    selectedManufacturer.value = option;
    query.value = option.label;
    options.value = [];
}

function assignManufacturer(): void {
    if (!selectedManufacturer.value) {
        return;
    }

    router.post(
        props.champion.routes.assign_manufacturer,
        { manufacturer_company_id: selectedManufacturer.value.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedManufacturer.value = null;
                query.value = '';
                options.value = [];
            },
        },
    );
}

function removeManufacturer(manufacturer: any): void {
    if (!window.confirm(`Remove ${manufacturer.name} from ${props.champion.name}?`)) {
        return;
    }

    router.delete(manufacturer.remove_href, { preserveScroll: true });
}
</script>

<template>
    <Head :title="champion.name" />

    <LibraryAdminShell
        :title="champion.name"
        subtitle="Champion profile, status controls, and recruited manufacturer assignments."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Library Management' },
            { label: 'Champions', href: '/admin/library/champions' },
            { label: champion.name },
        ]"
    >
        <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
            {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash?.error" class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">
            {{ page.props.flash.error }}
        </div>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Library Champion</p>
                            <h2 class="mt-2 text-3xl font-black text-slate-950">{{ champion.name }}</h2>
                            <p class="mt-1 text-slate-600">{{ champion.email }}</p>
                        </div>
                        <span class="rounded-full border px-3 py-1.5 text-sm font-black capitalize" :class="statusClass">{{ champion.status }}</span>
                    </div>

                    <dl class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Referral Code</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ champion.referral_code }}</dd>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Organisation</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ champion.organisation || 'Independent' }}</dd>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Commission Type</dt>
                            <dd class="mt-2 font-black capitalize text-slate-950">{{ champion.commission_type }}</dd>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                            <dt class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Commission Value</dt>
                            <dd class="mt-2 font-black text-slate-950">{{ champion.commission_value ?? 'Placeholder' }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                        <div>
                            <h2 class="font-black text-slate-950">Tagged Manufacturers</h2>
                            <p class="mt-1 text-sm text-slate-600">Manufacturers recruited or assigned to this champion.</p>
                        </div>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-600">{{ manufacturers.length }} tagged</span>
                    </div>

                    <div v-if="manufacturers.length" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Manufacturer</th>
                                    <th class="px-4 py-3">Subscription</th>
                                    <th class="px-4 py-3">Commission</th>
                                    <th class="px-4 py-3">Library Footprint</th>
                                    <th class="px-4 py-3">Last Activity</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="manufacturer in manufacturers" :key="manufacturer.uuid" class="hover:bg-slate-50/70">
                                    <td class="px-4 py-4">
                                        <div class="font-black text-slate-950">{{ manufacturer.name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ manufacturer.referral_code }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-bold capitalize">{{ manufacturer.plan_code || 'No plan' }}</div>
                                        <div class="mt-1 text-xs capitalize text-slate-500">{{ manufacturer.subscription_status || 'Pending' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-bold capitalize">{{ manufacturer.commission_type }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ manufacturer.commission_value ?? 'Placeholder' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">
                                        <div>{{ manufacturer.datasheets_count }} datasheets</div>
                                        <div class="mt-1">{{ manufacturer.engineering_data_count }} engineering data sets</div>
                                        <div class="mt-1">{{ manufacturer.users_count }} users</div>
                                    </td>
                                    <td class="px-4 py-4">{{ manufacturer.last_activity || 'Pending' }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="manufacturer.href" class="rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">Open</Link>
                                            <button class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-black text-red-800 hover:bg-red-100" type="button" @click="removeManufacturer(manufacturer)">Remove</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-10 text-center">
                        <h2 class="font-black">No manufacturers tagged yet</h2>
                        <p class="mt-2 text-sm text-slate-600">Use the selector to tag recruited manufacturers to this champion.</p>
                    </div>
                </article>
            </div>

            <aside class="space-y-6">
                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-black text-slate-950">Champion Controls</h2>
                    <p class="mt-1 text-sm text-slate-600">Pause or suspend changes access to champion activity without deleting history.</p>
                    <div class="mt-5 grid gap-3">
                        <button
                            class="rounded-md border px-4 py-3 text-sm font-black"
                            :class="champion.status === 'paused' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800'"
                            type="button"
                            @click="togglePause"
                        >
                            {{ pauseLabel }}
                        </button>
                        <button
                            class="rounded-md border px-4 py-3 text-sm font-black"
                            :class="champion.status === 'suspended' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'"
                            type="button"
                            @click="toggleSuspend"
                        >
                            {{ suspendLabel }}
                        </button>
                        <Link href="/admin/library/champions" class="rounded-md border border-slate-200 px-4 py-3 text-center text-sm font-black text-slate-700 hover:bg-slate-50">Back to Champions</Link>
                    </div>
                </article>

                <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-black text-slate-950">Tag Manufacturer</h2>
                    <p class="mt-1 text-sm text-slate-600">Search existing manufacturers. Already tagged manufacturers are excluded.</p>
                    <div class="relative mt-5">
                        <input
                            v-model="query"
                            class="w-full rounded-md border border-slate-200 px-3 py-3 text-sm font-bold"
                            placeholder="Type at least 2 characters..."
                            type="search"
                        />
                        <div v-if="options.length" class="absolute z-20 mt-2 max-h-72 w-full overflow-auto rounded-md border border-slate-200 bg-white shadow-lg">
                            <button
                                v-for="option in options"
                                :key="option.value"
                                class="block w-full px-3 py-3 text-left text-sm hover:bg-slate-50"
                                type="button"
                                @click="chooseManufacturer(option)"
                            >
                                <span class="font-black text-slate-950">{{ option.label }}</span>
                                <span class="mt-1 block text-xs capitalize text-slate-500">{{ option.plan || 'No plan' }} · {{ option.subscription_status || 'Pending' }}</span>
                            </button>
                        </div>
                    </div>
                    <p v-if="searching" class="mt-2 text-xs font-bold text-slate-500">Searching...</p>
                    <button
                        class="mt-4 w-full rounded-md bg-slate-950 px-4 py-3 text-sm font-black text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                        :disabled="!selectedManufacturer"
                        type="button"
                        @click="assignManufacturer"
                    >
                        Tag Manufacturer
                    </button>
                </article>
            </aside>
        </section>
    </LibraryAdminShell>
</template>
