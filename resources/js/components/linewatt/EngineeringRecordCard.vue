<script setup lang="ts">
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Check, GitCompare } from 'lucide-vue-next';
import LifecycleStatusBadge from './LifecycleStatusBadge.vue';

defineEmits<{
    compare: [];
}>();

defineProps<{
    record: {
        id?: number | string;
        uuid?: string | null;
        href?: string;
        manufacturer?: string | null;
        display_name?: string | null;
        model_name?: string | null;
        model_series?: string | null;
        series?: string | null;
        technology?: string | null;
        device_type?: string | null;
        inverter_device_type?: string | null;
        power_class_w?: number | string | null;
        power_class_kw?: number | string | null;
        status?: string | null;
        validation_status?: string | null;
        validation_grade?: string | null;
        validation_score?: number | null;
        source_label?: string | null;
        review_href?: string | null;
    };
    compareEnabled?: boolean;
    compareSelected?: boolean;
    compareDisabled?: boolean;
    compareMessage?: string | null;
    primaryHref?: string | null;
    primaryLabel?: string;
}>();

const { t } = useLineWattI18n();
</script>

<template>
    <article
        class="rounded-lg border bg-white p-5 shadow-sm transition"
        :class="compareSelected ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-slate-200'"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">
                    {{ record.device_type === 'inverter' ? t('myLibrary.inverter') : t('myLibrary.module') }}
                </p>
                <h3 class="mt-2 truncate text-lg font-bold text-slate-950">
                    {{ record.display_name || record.model_name || record.model_series || t('compare.engineeringRecord') }}
                </h3>
                <p class="mt-1 text-sm text-slate-600">
                    {{ record.manufacturer || t('myLibrary.unknownManufacturer') }}
                    <span v-if="record.series"> · {{ record.series }}</span>
                </p>
                <p v-if="record.source_label" class="mt-2 inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">
                    {{ record.source_label }}
                </p>
            </div>
            <LifecycleStatusBadge :status="record.status" />
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-slate-500">{{ t('search.modelSeries') }}</dt>
                <dd class="font-semibold text-slate-900">{{ record.model_series || t('publicManufacturer.pending') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ t('manufacturerProducts.power') }}</dt>
                <dd class="font-semibold text-slate-900">
                    <span v-if="record.power_class_w">{{ record.power_class_w }} W</span>
                    <span v-else-if="record.power_class_kw">{{ record.power_class_kw }} kW</span>
                    <span v-else>{{ t('publicManufacturer.pending') }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ record.device_type === 'inverter' ? t('search.deviceType') : t('search.technology') }}</dt>
                <dd class="font-semibold text-slate-900 capitalize">
                    <span v-if="record.device_type === 'inverter'">{{ (record.inverter_device_type || record.device_type || t('publicManufacturer.pending')).replace('_', ' ') }}</span>
                    <span v-else>{{ record.technology || t('publicManufacturer.pending') }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-slate-500">{{ t('compare.source') }}</dt>
                <dd class="font-semibold text-slate-900">{{ record.source_label || 'LineWatt Library' }}</dd>
            </div>
        </dl>

        <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <button
                v-if="compareEnabled"
                class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-semibold transition"
                :class="compareSelected
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : compareDisabled
                        ? 'cursor-not-allowed border-slate-200 text-slate-400'
                        : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                :disabled="compareDisabled && !compareSelected"
                :aria-pressed="compareSelected"
                :title="compareMessage || t('compare.selectForComparison')"
                type="button"
                @click="$emit('compare')"
            >
                <Check v-if="compareSelected" class="size-4" />
                <GitCompare v-else class="size-4" />
                {{ compareSelected ? t('compare.selected') : t('nav.compare') }}
            </button>
            <button
                v-else
                class="inline-flex cursor-not-allowed items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-400"
                disabled
                :title="t('myLibrary.comingNext')"
                type="button"
            >
                <GitCompare class="size-4" />
                {{ t('nav.compare') }}
            </button>
            <Link
                :href="primaryHref || record.href || '#'"
                class="inline-flex items-center gap-2 rounded-md bg-slate-950 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
                {{ primaryLabel || t('results.openRecord') }}
                <ArrowRight class="size-4" />
            </Link>
        </div>
    </article>
</template>
