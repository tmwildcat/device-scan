<script setup lang="ts">
import { useLineWattI18n } from '@/lib/linewatt-i18n';

defineProps<{
    rows: Array<{
        model?: string;
        field: string;
        value: string;
        unit?: string;
        normalized?: string;
        confidence?: string;
        page?: string;
        section?: string;
        sourceText?: string;
    }>;
}>();

function hasModelColumn(rows: Array<{ model?: string }>): boolean {
    return rows.some((row) => Boolean(row.model));
}

const { t } = useLineWattI18n();
</script>

<template>
    <div>
        <div v-if="rows.length" class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                    <tr>
                        <th v-if="hasModelColumn(rows)" class="px-3 py-3">{{ t('compare.modelName') }}</th>
                        <th class="px-3 py-3">{{ t('compare.field') }}</th>
                        <th class="px-3 py-3">{{ t('detail.value') }}</th>
                        <th class="px-3 py-3">{{ t('detail.unit') }}</th>
                        <th class="px-3 py-3">{{ t('detail.normalized') }}</th>
                        <th class="px-3 py-3">{{ t('detail.confidence') }}</th>
                        <th class="px-3 py-3">{{ t('compare.page') }}</th>
                        <th class="px-3 py-3">{{ t('detail.section') }}</th>
                        <th class="px-3 py-3">{{ t('detail.sourceText') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="(row, index) in rows" :key="index">
                        <td v-if="hasModelColumn(rows)" class="px-3 py-3 font-semibold text-slate-900">{{ row.model || '' }}</td>
                        <td class="px-3 py-3 font-semibold text-slate-900">{{ row.field }}</td>
                        <td class="px-3 py-3">{{ row.value }}</td>
                        <td class="px-3 py-3">{{ row.unit || '' }}</td>
                        <td class="px-3 py-3">{{ row.normalized || '' }}</td>
                        <td class="px-3 py-3">{{ row.confidence || '' }}</td>
                        <td class="px-3 py-3">{{ row.page || '' }}</td>
                        <td class="px-3 py-3">{{ row.section || '' }}</td>
                        <td class="max-w-md whitespace-normal px-3 py-3 text-slate-600">{{ row.sourceText || '' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="mt-3 text-sm text-slate-600">{{ t('detail.noData') }}</p>
    </div>
</template>
