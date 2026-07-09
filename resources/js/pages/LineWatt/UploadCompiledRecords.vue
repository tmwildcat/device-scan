<script setup lang="ts">
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardCheck } from 'lucide-vue-next';

defineProps<{
    datasheet: {
        id: number;
        uuid: string;
        source_type: string;
        device_type: string;
        manufacturer?: string | null;
        product_name?: string | null;
        status?: string | null;
        original_filename?: string | null;
    };
    records: any[];
    backUrl: string;
}>();
</script>

<template>
    <Head title="Compiled Engineering Records" />

    <div class="min-h-screen bg-slate-50 text-slate-950">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Compilation Complete</p>
                    <h1 class="mt-2 text-4xl font-black tracking-tight text-slate-950">Review Compiled Records</h1>
                    <p class="mt-2 text-slate-600">
                        {{ datasheet.original_filename || datasheet.product_name || 'Uploaded datasheet' }}
                    </p>
                </div>
                <Link
                    :href="backUrl"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                >
                    <ArrowLeft class="size-4" />
                    Back to My Private Datasets
                </Link>
            </div>

            <div class="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Engineering Records</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            This datasheet produced multiple records. Open each record for editable review.
                        </p>
                    </div>
                    <LifecycleStatusBadge :status="datasheet.status" />
                </div>

                <div v-if="records.length" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="record in records"
                        :key="record.uuid || record.id"
                        class="rounded-lg border border-slate-200 bg-slate-50 p-4"
                    >
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">
                            {{ record.device_type === 'inverter' ? 'Inverter' : 'Module' }}
                        </p>
                        <h3 class="mt-2 text-lg font-black text-slate-950">
                            {{ record.display_name || record.model_name || record.model_series || 'Engineering Record' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-600">{{ record.manufacturer || 'Unknown manufacturer' }}</p>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <LifecycleStatusBadge :status="record.status" />
                            <ValidationStatusBadge :status="record.validation_status" />
                        </div>
                        <Link
                            :href="record.review_href"
                            class="mt-4 inline-flex items-center gap-2 rounded-md bg-slate-950 px-3 py-2 text-sm font-bold text-white hover:bg-slate-800"
                        >
                            <ClipboardCheck class="size-4" />
                            Review
                        </Link>
                    </article>
                </div>
                <p v-else class="mt-5 rounded-md border border-dashed border-slate-300 p-4 text-sm text-slate-600">
                    No compiled records were created. The datasheet may need retry or manual review.
                </p>
            </div>
        </main>
    </div>
</template>
