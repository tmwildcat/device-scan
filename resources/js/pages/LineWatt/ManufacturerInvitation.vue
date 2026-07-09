<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    company: {
        name: string;
        plan?: string | null;
        status?: string | null;
        primary_contact_name?: string | null;
        primary_contact_email?: string | null;
    };
    token: string;
    steps: Array<{ label: string; status: string; description: string }>;
}>();

const form = useForm({});

function accept(token: string): void {
    form.post(`/manufacturer/register/${token}`);
}
</script>

<template>
    <Head :title="`${company.name} Invitation`" />

    <main class="min-h-screen bg-slate-100 px-6 py-10 text-slate-950">
        <section class="mx-auto max-w-5xl">
            <div class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">LineWatt Library OEM Subscriber</p>
                <h1 class="mt-3 text-4xl font-black">{{ company.name }}</h1>
                <p class="mt-3 max-w-2xl text-lg leading-8 text-slate-600">
                    Accept this invitation to begin Manufacturer Admin onboarding. Account creation, email verification and Paddle checkout are placeholders in this milestone.
                </p>

                <div class="mt-6 grid gap-3 md:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Contact</div>
                        <div class="mt-2 font-black">{{ company.primary_contact_name || 'Primary contact' }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ company.primary_contact_email || 'Email pending' }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Initial Plan</div>
                        <div class="mt-2 font-black">{{ company.plan || 'Manufacturer Pro' }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Status</div>
                        <div class="mt-2 font-black">{{ company.status || 'Pending Invitation' }}</div>
                    </div>
                </div>

                <button class="mt-8 rounded-md bg-slate-950 px-5 py-3 text-sm font-black text-white hover:bg-slate-800" :disabled="form.processing" @click="accept(token)">
                    Accept Invitation
                </button>
            </div>

            <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black">Onboarding Flow</h2>
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    <article v-for="step in steps" :key="step.label" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-black">{{ step.label }}</h3>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-600">{{ step.status }}</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ step.description }}</p>
                    </article>
                </div>
            </div>
        </section>
    </main>
</template>
