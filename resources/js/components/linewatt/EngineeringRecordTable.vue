<script setup lang="ts">
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, GitCompare } from 'lucide-vue-next';
import LifecycleStatusBadge from './LifecycleStatusBadge.vue';

defineEmits<{
    compare: [record: any];
}>();

defineProps<{
    tab?: string;
    compareEnabled?: boolean;
    emptyState?: {
        title: string;
        message: string;
    };
    records: Array<{
        href?: string;
        manufacturer?: string | null;
        display_name?: string | null;
        model_name?: string | null;
        model_series?: string | null;
        device_type?: string | null;
        inverter_device_type?: string | null;
        technology?: string | null;
        power_class_w?: number | string | null;
        power_class_kw?: number | string | null;
        status?: string | null;
        validation_status?: string | null;
        validation_grade?: string | null;
        validation_score?: number | null;
        source_label?: string | null;
    }>;
}>();

const { t } = useLineWattI18n();
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ t('compare.engineeringRecord') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ t('search.modelSeries') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ t('manufacturerProducts.power') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">
                        {{ tab === 'modules' ? t('search.technology') : tab === 'inverters' ? t('search.deviceType') : t('results.typeTechnology') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ t('manufacturerProducts.status') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{{ t('manufacturer.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <tr v-for="record in records" :key="record.href || record.display_name || record.model_name || record.model_series || ''">
                    <td class="px-4 py-4">
                        <div class="font-semibold text-slate-950">
                            {{ record.display_name || record.model_name || record.model_series || t('compare.engineeringRecord') }}
                        </div>
                        <div class="text-sm text-slate-500">
                            {{ record.manufacturer || t('myLibrary.unknownManufacturer') }}
                            <span v-if="record.source_label"> · {{ record.source_label }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">
                        {{ record.model_series || t('publicManufacturer.pending') }}
                    </td>
                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">
                        <span v-if="record.power_class_w">{{ record.power_class_w }} W</span>
                        <span v-else-if="record.power_class_kw">{{ record.power_class_kw }} kW</span>
                        <span v-else>{{ t('publicManufacturer.pending') }}</span>
                    </td>
                    <td class="px-4 py-4 text-sm font-medium text-slate-700">
                        <span v-if="record.device_type === 'module'">{{ record.technology || t('publicManufacturer.pending') }}</span>
                        <span v-else class="capitalize">{{ (record.inverter_device_type || record.device_type || 'record').replace('_', ' ') }}</span>
                    </td>
                    <td class="px-4 py-4">
                        <LifecycleStatusBadge :status="record.status" />
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                v-if="compareEnabled"
                                class="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 text-slate-700 hover:bg-slate-50"
                                type="button"
                                :title="`${t('nav.compare')} ${t('compare.engineeringRecord')}`"
                                @click="$emit('compare', record)"
                            >
                                <GitCompare class="size-4" />
                            </button>
                            <button
                                v-else
                                class="inline-flex size-9 cursor-not-allowed items-center justify-center rounded-md border border-slate-200 text-slate-400"
                                disabled
                                type="button"
                                :title="t('myLibrary.comingNext')"
                            >
                                <GitCompare class="size-4" />
                            </button>
                            <Link
                                :href="record.href || '#'"
                                class="inline-flex size-9 items-center justify-center rounded-md bg-slate-950 text-white hover:bg-slate-800"
                                :title="`${t('manufacturerProducts.open')} ${t('compare.engineeringRecord')}`"
                            >
                                <ArrowRight class="size-4" />
                            </Link>
                        </div>
                    </td>
                </tr>
                <tr v-if="records.length === 0">
                    <td class="px-4 py-10 text-center" colspan="6">
                        <div class="font-bold text-slate-950">{{ emptyState?.title || t('results.noRecordsFound') }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ emptyState?.message || t('results.tryChangingFilters') }}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
