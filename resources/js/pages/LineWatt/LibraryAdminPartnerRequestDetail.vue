<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    roleLabel?: string | null;
    partnerRequest: any;
    routes: { approve: string; reject: string; requestInfo: string; back: string };
}>();

const page = usePage<{ flash?: { success?: string | null } }>();
const form = useForm({ comment: '', plan_code: 'pro', champion_id: props.partnerRequest.champion?.id || '', referral_code: props.partnerRequest.champion?.referral_code || '' });
const championSuggestions = ref<Array<{ label: string; value: number; referral_code: string; status: string }>>([]);
const championLoading = ref(false);
let championDebounce: ReturnType<typeof setTimeout> | null = null;

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

function approve(): void {
    form.post(props.routes.approve, { preserveScroll: true });
}

function reject(): void {
    form.post(props.routes.reject, { preserveScroll: true });
}

function requestInfo(): void {
    form.post(props.routes.requestInfo, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${partnerRequest.company_name} · Manufacturer Request`" />

    <LibraryAdminShell
        :title="partnerRequest.company_name"
        subtitle="Review OEM identity, requested brand, proof notes, and approval state before granting Manufacturer Admin access."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Equipment Manufacturers' },
            { label: 'Manufacturer Requests', href: routes.back },
            { label: partnerRequest.company_name },
        ]"
    >
        <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-black">Application</h2>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        <div><dt class="text-xs font-black uppercase text-slate-500">Company</dt><dd class="mt-1 font-semibold">{{ partnerRequest.company_name }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Requested Brand</dt><dd class="mt-1 font-semibold">{{ partnerRequest.requested_manufacturer_brand }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Website</dt><dd class="mt-1">{{ partnerRequest.website || 'Not provided' }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Country</dt><dd class="mt-1">{{ partnerRequest.country }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Contact</dt><dd class="mt-1">{{ partnerRequest.contact_person }} · {{ partnerRequest.contact_email }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Email Domain</dt><dd class="mt-1">{{ partnerRequest.official_email_domain }}</dd></div>
                    </dl>
                    <div class="mt-5 rounded-lg bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase text-slate-500">Proof / Notes</div>
                        <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ partnerRequest.proof_notes || 'No notes provided.' }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-black">Review State</h2>
                    <dl class="mt-5 grid gap-4 md:grid-cols-2">
                        <div><dt class="text-xs font-black uppercase text-slate-500">Status</dt><dd class="mt-1 capitalize">{{ partnerRequest.status.replaceAll('_', ' ') }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Reviewed By</dt><dd class="mt-1">{{ partnerRequest.reviewed_by || 'Pending' }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Reviewed At</dt><dd class="mt-1">{{ partnerRequest.reviewed_at || 'Pending' }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Linked Company</dt><dd class="mt-1"><Link v-if="partnerRequest.linked_company" :href="partnerRequest.linked_company.href" class="font-black text-emerald-700">{{ partnerRequest.linked_company.name }}</Link><span v-else>Not linked</span></dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Champion</dt><dd class="mt-1">{{ partnerRequest.champion?.name || 'Not assigned' }}</dd></div>
                        <div><dt class="text-xs font-black uppercase text-slate-500">Referral Code</dt><dd class="mt-1">{{ partnerRequest.champion?.referral_code || 'None' }}</dd></div>
                    </dl>
                    <p v-if="partnerRequest.review_comment" class="mt-4 rounded-md bg-slate-50 p-3 text-sm">{{ partnerRequest.review_comment }}</p>
                </div>
            </div>

            <aside class="sticky top-20 self-start rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-black">Admin Decision</h2>
                <label class="mt-4 block text-sm font-bold">Plan placeholder
                    <select v-model="form.plan_code" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2">
                        <option value="pro">Manufacturer Pro</option>
                        <option value="enterprise">Manufacturer Enterprise</option>
                    </select>
                    <p class="mt-1 text-xs font-semibold text-slate-500">Enterprise approval is contact-sales until Paddle pricing is connected.</p>
                </label>
                <label class="relative mt-4 block text-sm font-bold">Champion / Referral
                    <input
                        v-model="form.referral_code"
                        class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2"
                        autocomplete="off"
                        placeholder="Search champion or enter referral code"
                        @input="
                            form.champion_id = '';
                            scheduleChampionLookup();
                        "
                    />
                    <span v-if="form.errors.referral_code" class="text-xs text-red-700">{{ form.errors.referral_code }}</span>
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
                        </button>
                    </div>
                </label>
                <label class="mt-4 block text-sm font-bold">Comment
                    <textarea v-model="form.comment" class="mt-2 min-h-28 w-full rounded-md border border-slate-200 px-3 py-2" placeholder="Required for rejection or more information." />
                    <span v-if="form.errors.comment" class="text-xs text-red-700">{{ form.errors.comment }}</span>
                </label>
                <div class="mt-5 grid gap-2">
                    <button class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-black text-white disabled:opacity-50" :disabled="form.processing" type="button" @click="approve">Approve</button>
                    <button class="rounded-md border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-black text-amber-800 disabled:opacity-50" :disabled="form.processing" type="button" @click="requestInfo">Request More Information</button>
                    <button class="rounded-md border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-black text-red-800 disabled:opacity-50" :disabled="form.processing" type="button" @click="reject">Reject</button>
                </div>
            </aside>
        </section>
    </LibraryAdminShell>
</template>
