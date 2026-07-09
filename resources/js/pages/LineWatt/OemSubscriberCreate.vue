<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    roleLabel?: string | null;
    plans: Record<string, string>;
    statuses: Record<string, string>;
    initialManufacturer?: string | null;
}>();

const form = useForm({
    manufacturer: props.initialManufacturer || '',
    primary_contact_name: '',
    primary_contact_email: '',
    plan_code: 'pro',
    champion_id: '',
    referral_code: '',
    notes: '',
});
const suggestions = ref<Array<{ label: string; value: string }>>([]);
const championSuggestions = ref<Array<{ label: string; value: number; referral_code: string; status: string }>>([]);
const loading = ref(false);
const championLoading = ref(false);
let debounce: ReturnType<typeof setTimeout> | null = null;
let championDebounce: ReturnType<typeof setTimeout> | null = null;

function scheduleLookup(): void {
    if (debounce !== null) clearTimeout(debounce);

    if (form.manufacturer.trim().length < 2) {
        suggestions.value = [];
        loading.value = false;
        return;
    }

    loading.value = true;
    debounce = setTimeout(fetchManufacturers, 300);
}

async function fetchManufacturers(): Promise<void> {
    const params = new URLSearchParams({ q: form.manufacturer.trim() });
    const response = await fetch(`/admin/library/oem-subscribers/manufacturers?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });

    suggestions.value = response.ok ? await response.json() : [];
    loading.value = false;
}

function selectManufacturer(suggestion: { value: string }): void {
    form.manufacturer = suggestion.value;
    suggestions.value = [];
}

function scheduleChampionLookup(): void {
    if (championDebounce !== null) clearTimeout(championDebounce);

    if (form.referral_code.trim().length < 2) {
        championSuggestions.value = [];
        championLoading.value = false;
        return;
    }

    championLoading.value = true;
    championDebounce = setTimeout(fetchChampions, 300);
}

async function fetchChampions(): Promise<void> {
    const params = new URLSearchParams({ q: form.referral_code.trim() });
    const response = await fetch(`/admin/library/champions/search?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });

    championSuggestions.value = response.ok ? await response.json() : [];
    championLoading.value = false;
}

function selectChampion(suggestion: { value: number; referral_code: string }): void {
    form.champion_id = String(suggestion.value);
    form.referral_code = suggestion.referral_code;
    championSuggestions.value = [];
}

function submit(): void {
    form.post('/admin/library/oem-subscribers');
}
</script>

<template>
    <Head title="New OEM Subscriber" />

    <LibraryAdminShell
        title="New OEM Subscriber"
        subtitle="Search an existing manufacturer, enter the primary contact, and generate an onboarding invitation."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Equipment Manufacturers' },
            { label: 'Subscribers', href: '/admin/library/oem-subscribers' },
            { label: 'New' },
        ]"
    >
        <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]" @submit.prevent="submit">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-black text-slate-950">Invitation Details</h2>
                <p class="mt-1 text-sm text-slate-600">Manufacturers already subscribed are excluded from autocomplete results.</p>

                <label class="relative mt-5 block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Manufacturer</span>
                    <div class="mt-2 flex items-center rounded-md border border-slate-200 bg-white px-3">
                        <Search class="size-4 text-slate-400" />
                        <input
                            v-model="form.manufacturer"
                            class="w-full border-0 bg-transparent px-2 py-2 text-sm font-semibold outline-none focus:ring-0"
                            autocomplete="off"
                            placeholder="Type at least 2 characters"
                            @input="scheduleLookup"
                        />
                    </div>
                    <div v-if="form.errors.manufacturer" class="mt-1 text-sm font-semibold text-rose-700">{{ form.errors.manufacturer }}</div>
                    <div v-if="loading || suggestions.length" class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 shadow-xl">
                        <div v-if="loading" class="px-3 py-2 text-sm font-bold text-slate-500">Searching...</div>
                        <button
                            v-for="suggestion in suggestions"
                            :key="suggestion.value"
                            type="button"
                            class="w-full rounded-md px-3 py-2 text-left text-sm font-bold text-slate-800 hover:bg-slate-50"
                            @click="selectManufacturer(suggestion)"
                        >
                            {{ suggestion.label }}
                        </button>
                    </div>
                </label>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Primary Contact</span>
                        <input v-model="form.primary_contact_name" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold" placeholder="Name" />
                        <div v-if="form.errors.primary_contact_name" class="mt-1 text-sm font-semibold text-rose-700">{{ form.errors.primary_contact_name }}</div>
                    </label>
                    <label class="block">
                        <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Contact Email</span>
                        <input v-model="form.primary_contact_email" type="email" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold" placeholder="admin@manufacturer.com" />
                        <div v-if="form.errors.primary_contact_email" class="mt-1 text-sm font-semibold text-rose-700">{{ form.errors.primary_contact_email }}</div>
                    </label>
                </div>

                <label class="mt-5 block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Initial Plan Placeholder</span>
                    <select v-model="form.plan_code" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold">
                        <option v-for="(label, value) in plans" :key="value" :value="value">{{ label }}</option>
                    </select>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Manufacturer Pro is checkout-ready. Enterprise remains contact-sales until a Paddle price is configured.</p>
                </label>

                <label class="relative mt-5 block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Champion / Referral</span>
                    <input
                        v-model="form.referral_code"
                        class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold"
                        autocomplete="off"
                        placeholder="Search champion or enter referral code"
                        @input="
                            form.champion_id = '';
                            scheduleChampionLookup();
                        "
                    />
                    <div v-if="form.errors.referral_code" class="mt-1 text-sm font-semibold text-rose-700">{{ form.errors.referral_code }}</div>
                    <div v-if="championLoading || championSuggestions.length" class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 shadow-xl">
                        <div v-if="championLoading" class="px-3 py-2 text-sm font-bold text-slate-500">Searching champions...</div>
                        <button
                            v-for="suggestion in championSuggestions"
                            :key="suggestion.value"
                            type="button"
                            class="w-full rounded-md px-3 py-2 text-left text-sm font-bold text-slate-800 hover:bg-slate-50"
                            @click="selectChampion(suggestion)"
                        >
                            {{ suggestion.label }}
                            <span class="ml-2 text-xs uppercase text-slate-400">{{ suggestion.status }}</span>
                        </button>
                    </div>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Optional. Suspended champions cannot be assigned without Admin override.</p>
                </label>

                <label class="mt-5 block">
                    <span class="text-xs font-black uppercase tracking-[0.12em] text-slate-500">Notes</span>
                    <textarea v-model="form.notes" class="mt-2 min-h-28 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold" placeholder="Internal onboarding notes..." />
                </label>

                <div class="mt-6 flex flex-wrap gap-2">
                    <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800" :disabled="form.processing">
                        Generate Invitation
                    </button>
                    <Link href="/admin/library/oem-subscribers" class="rounded-md border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">
                        Cancel
                    </Link>
                </div>
            </section>

            <aside class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-black text-slate-950">Placeholder Flow</h2>
                <ol class="mt-4 space-y-3 text-sm">
                    <li class="rounded-md bg-emerald-50 p-3 font-bold text-emerald-800">1. Search existing manufacturers</li>
                    <li class="rounded-md bg-slate-50 p-3 font-bold text-slate-700">2. Select manufacturer</li>
                    <li class="rounded-md bg-slate-50 p-3 font-bold text-slate-700">3. Enter primary contact</li>
                    <li class="rounded-md bg-slate-50 p-3 font-bold text-slate-700">4. Generate invitation</li>
                    <li class="rounded-md bg-slate-50 p-3 font-bold text-slate-700">5. Send invitation</li>
                </ol>
                <p class="mt-4 text-sm leading-6 text-slate-600">Paddle checkout, payment and account activation are intentionally placeholders in this milestone.</p>
            </aside>
        </form>
    </LibraryAdminShell>
</template>
