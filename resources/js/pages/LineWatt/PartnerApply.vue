<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage<{ flash?: { success?: string | null } }>();

const form = useForm({
    company_name: '',
    website: '',
    country: '',
    contact_person: '',
    contact_email: '',
    official_email_domain: '',
    requested_manufacturer_brand: '',
    referral_code: '',
    proof_notes: '',
});

function submit(): void {
    form.post('/partner/apply', { preserveScroll: true });
}
</script>

<template>
    <Head title="Apply for OEM Access" />

    <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-950 sm:px-6 lg:px-8">
        <section class="mx-auto max-w-5xl">
            <Link href="/" class="text-sm font-black text-emerald-700">LineWatt Library</Link>
            <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Verified OEM Access</p>
                <h1 class="mt-2 text-3xl font-black">Partner Request</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Manufacturer Admin access is approval-based because OEM accounts control public manufacturer identity, datasheets, claims, logos and promotions.
                </p>
                <div v-if="page.props.flash?.success" class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                    {{ page.props.flash.success }}
                </div>
            </div>

            <form class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="text-sm font-bold">Company name
                        <input v-model="form.company_name" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                        <span v-if="form.errors.company_name" class="text-xs text-red-700">{{ form.errors.company_name }}</span>
                    </label>
                    <label class="text-sm font-bold">Website
                        <input v-model="form.website" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" placeholder="https://example.com" />
                    </label>
                    <label class="text-sm font-bold">Country
                        <input v-model="form.country" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                    </label>
                    <label class="text-sm font-bold">Contact person
                        <input v-model="form.contact_person" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                    </label>
                    <label class="text-sm font-bold">Official email
                        <input v-model="form.contact_email" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" type="email" />
                    </label>
                    <label class="text-sm font-bold">Official email domain
                        <input v-model="form.official_email_domain" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" placeholder="example.com" />
                    </label>
                    <label class="text-sm font-bold md:col-span-2">Requested manufacturer brand
                        <input v-model="form.requested_manufacturer_brand" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                    </label>
                    <label class="text-sm font-bold md:col-span-2">Referral Code
                        <input v-model="form.referral_code" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" placeholder="Optional Library Champion referral code" />
                    </label>
                    <label class="text-sm font-bold md:col-span-2">Proof / notes
                        <textarea v-model="form.proof_notes" class="mt-2 min-h-32 w-full rounded-md border border-slate-200 px-3 py-2" placeholder="Official role, public profile, business registration, brand ownership notes, or other verification details." />
                    </label>
                </div>
                <div class="mt-6 flex justify-end">
                    <button class="rounded-md bg-slate-950 px-5 py-3 text-sm font-black text-white disabled:opacity-50" :disabled="form.processing" type="submit">
                        Submit for Review
                    </button>
                </div>
            </form>
        </section>
    </main>
</template>
