<script setup lang="ts">
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import ReviewStatusBadge from '@/components/linewatt/ReviewStatusBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardCheck } from 'lucide-vue-next';

defineProps<{
    records: {
        data: any[];
        current_page: number;
        from: number | null;
        last_page: number;
        per_page: number;
        to: number | null;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
}>();

function displayRecord(record: any): string {
    return record.display_name || record.model_name || record.model_series || 'Engineering Record';
}
</script>

<template>
    <Head title="My Private Datasets Review Queue" />

    <div class="min-h-screen bg-slate-50 text-slate-950">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">My Private Datasets</p>
                    <h1 class="mt-2 text-4xl font-black tracking-tight text-slate-950">Review Queue</h1>
                    <p class="mt-2 max-w-3xl text-slate-600">
                        Private Engineering Records waiting for manual review, correction or approval.
                    </p>
                </div>
                <Link
                    href="/my-library"
                    class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                >
                    <ArrowLeft class="size-4" />
                    Back to My Private Datasets
                </Link>
            </div>

            <section class="mt-8 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                            <ClipboardCheck class="size-5" />
                        </span>
                        <div>
                            <h2 class="font-black text-slate-950">Records Needing Review</h2>
                            <p class="text-sm text-slate-500">
                                Showing {{ records.from || 0 }}-{{ records.to || 0 }}. Continue until the queue is clear.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="records.data.length" class="divide-y divide-slate-100">
                    <div
                        v-for="record in records.data"
                        :key="record.uuid || record.id"
                        class="grid gap-3 px-5 py-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-center"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase tracking-[0.12em] text-slate-600">
                                    {{ record.device_type === 'inverter' ? 'Inverter' : 'Module' }}
                                </span>
                                <LifecycleStatusBadge :status="record.status" />
                                <ReviewStatusBadge :status="record.review_status" />
                                <ValidationStatusBadge :status="record.validation_status" />
                            </div>
                            <h3 class="mt-2 truncate text-base font-black text-slate-950">
                                {{ displayRecord(record) }}
                            </h3>
                            <p class="mt-1 truncate text-sm text-slate-600">
                                {{ record.manufacturer || 'Unknown manufacturer' }}
                                <span v-if="record.model_series"> · {{ record.model_series }}</span>
                                <span v-if="record.power_class_w"> · {{ record.power_class_w }} W</span>
                                <span v-else-if="record.power_class_kw"> · {{ record.power_class_kw }} kW</span>
                            </p>
                        </div>
                        <Link
                            :href="record.review_href || '#'"
                            class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800"
                        >
                            Review
                        </Link>
                    </div>
                </div>

                <div v-else class="p-10 text-center">
                    <h2 class="text-lg font-black text-slate-950">No private datasets need review</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Uploaded and compiled private datasets will appear here when they require manual review.
                    </p>
                </div>
            </section>

            <nav v-if="records.last_page > 1" class="mt-6 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in records.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded-md border px-3 py-2 text-sm font-bold"
                    :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'"
                    v-html="link.label"
                />
            </nav>
        </main>
    </div>
</template>
