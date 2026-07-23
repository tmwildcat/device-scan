<script setup lang="ts">
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

type Obligation = { id: string; document: string; version: string; effective_at?: string | null; published_at?: string | null; workflow: string; audience: string; blocking: boolean; material_change: boolean; statement?: string | null; document_url: string };
const props = defineProps<{ capability?: string | null; configuration_valid: boolean; reason_code?: string | null; obligations: Obligation[]; completed_count: number }>();
const form = useForm({ affirmed: false });
const current = () => props.obligations.find((item) => item.blocking) || props.obligations[0];
</script>

<template>
    <Head title="Legal acceptance required" />
    <div class="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-white">
        <WorkspaceNavigation />
        <main class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <div v-if="!configuration_valid" class="rounded-xl border border-rose-300 bg-rose-50 p-6 text-rose-950">
                <h1 class="text-2xl font-black">This feature is temporarily unavailable</h1>
                <p class="mt-2">Its legal requirements have not been configured correctly. No acceptance can be recorded.</p>
            </div>
            <article v-else-if="current()" class="rounded-xl border bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm font-bold text-emerald-700">Required document {{ completed_count + 1 }} · {{ obligations.length }} outstanding</p>
                <h1 class="mt-2 text-3xl font-black">{{ current()?.document }}</h1>
                <p class="mt-2 text-sm text-slate-500">Version {{ current()?.version }} · Audience: {{ current()?.audience }}</p>
                <div v-if="current()?.material_change" class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-950">This is a Material Change and requires a new acceptance.</div>
                <p class="mt-5 text-lg font-semibold">{{ current()?.statement }}</p>
                <a :href="current()?.document_url" class="mt-4 inline-block font-bold text-emerald-700 underline">Read the complete published document</a>
                <form class="mt-6" @submit.prevent="form.post(`/legal/acceptance/${current()?.id}`)">
                    <label class="flex items-start gap-3 rounded-md border p-4"><input v-model="form.affirmed" type="checkbox" class="mt-1" /><span>I have reviewed the exact document version above and explicitly affirm the stated legal action. The timestamp and evidence will be recorded.</span></label>
                    <button :disabled="!form.affirmed || form.processing" class="mt-5 rounded-md bg-slate-950 px-5 py-3 font-black text-white disabled:opacity-40">Accept and continue</button>
                </form>
            </article>
            <div v-else class="rounded-xl border bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-900">
                <h1 class="text-2xl font-black">No outstanding legal requirements</h1>
                <Link href="/legal-status" class="mt-4 inline-block font-bold text-emerald-700">View Legal Status</Link>
            </div>
        </main>
    </div>
</template>
