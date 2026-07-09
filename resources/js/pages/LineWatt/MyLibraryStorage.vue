<script setup lang="ts">
import EntitlementDiagnostics from '@/components/linewatt/EntitlementDiagnostics.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, DatabaseZap, FileText, HardDrive, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type StorageItem = {
    key: string;
    id: string;
    type: 'datasheet' | 'record';
    name: string;
    category: string;
    size: string;
    size_bytes: number;
    uploaded_at?: string | null;
    last_accessed_at?: string | null;
    can_be_regenerated: boolean;
    dependent_records_count: number;
    delete_warning?: string | null;
};

const props = defineProps<{
    summary: {
        used_bytes: number;
        used_label: string;
        quota_bytes: number;
        quota_label: string;
        used_percent: number;
        plan_code: string;
    };
    breakdown: Array<{
        key: string;
        label: string;
        bytes: number;
        size: string;
    }>;
    items: StorageItem[];
    futureActions: Record<string, string>;
}>();

const { dir, t } = useLineWattI18n();
const selected = ref<string[]>([]);
const deleteDependents = ref(false);

const selectedItems = computed(() => props.items.filter((item) => selected.value.includes(item.key)));
const hasSelectedOriginalPdfWithDependents = computed(() => selectedItems.value.some((item) => item.type === 'datasheet' && item.dependent_records_count > 0));

function iconFor(key: string) {
    if (key === 'private_datasheets') return FileText;
    if (key === 'engineering_records') return DatabaseZap;

    return Archive;
}

function toggleItem(key: string): void {
    selected.value = selected.value.includes(key)
        ? selected.value.filter((item) => item !== key)
        : [...selected.value, key];
}

function deleteItem(item: StorageItem): void {
    const dependentWarning = item.type === 'datasheet' && item.dependent_records_count > 0
        ? `\n\n${t('storage.deletePdfWarning')}`
        : '';
    const confirmed = window.confirm(`${t('storage.deleteConfirm')} ${item.name}?${dependentWarning}`);

    if (!confirmed) {
        return;
    }

    router.delete(`/my-library/storage/items/${encodeURIComponent(item.key)}`, {
        data: {
            delete_dependents: item.type === 'datasheet' && item.dependent_records_count > 0,
        },
        preserveScroll: true,
    });
}

function deleteSelected(): void {
    if (selected.value.length === 0) {
        return;
    }

    const warning = hasSelectedOriginalPdfWithDependents.value
        ? `\n\n${t('storage.deletePdfWarning')}`
        : '';
    const confirmed = window.confirm(`${t('storage.deleteSelectedConfirm')} (${selected.value.length})?${warning}`);

    if (!confirmed) {
        return;
    }

    router.delete('/my-library/storage/items', {
        data: {
            items: selected.value,
            delete_dependents: hasSelectedOriginalPdfWithDependents.value || deleteDependents.value,
        },
        preserveScroll: true,
        onSuccess: () => {
            selected.value = [];
        },
    });
}
</script>

<template>
    <Head :title="t('storage.title')" />

    <div class="min-h-screen bg-slate-50 text-slate-950" :dir="dir">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="mb-6">
                <Link href="/my-library" class="text-sm font-bold text-emerald-700 hover:text-emerald-800">
                    {{ t('storage.backToPrivateDatasets') }}
                </Link>
            </div>

            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-emerald-700">{{ t('storage.eyebrow') }}</p>
                        <h1 class="mt-2 text-4xl font-black tracking-tight text-slate-950">{{ t('storage.title') }}</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                            {{ t('storage.description') }}
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-right">
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ t('storage.planAllowance') }}</p>
                        <p class="mt-1 text-2xl font-black text-slate-950">{{ summary.quota_label }}</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">{{ summary.plan_code }}</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-5 lg:grid-cols-[320px_1fr]">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-500">{{ t('storage.totalUsed') }}</p>
                                <p class="mt-1 text-3xl font-black text-slate-950">{{ summary.used_label }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-600">{{ summary.used_percent }}% {{ t('myLibrary.used') }}</p>
                            </div>
                            <HardDrive class="size-8 text-emerald-700" />
                        </div>
                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-white">
                            <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${summary.used_percent}%` }" />
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article
                            v-for="row in breakdown"
                            :key="row.key"
                            class="rounded-lg border border-slate-200 bg-white p-4"
                        >
                            <component :is="iconFor(row.key)" class="size-5 text-emerald-700" />
                            <p class="mt-4 text-xs font-black uppercase tracking-[0.14em] text-slate-400">{{ t(`storage.${row.key}` as any) }}</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ row.size }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
                    <div>
                        <h2 class="text-xl font-black text-slate-950">{{ t('storage.assets') }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ t('storage.assetsHelp') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            class="inline-flex items-center gap-2 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-black text-red-700 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="selected.length === 0"
                            type="button"
                            @click="deleteSelected"
                        >
                            <Trash2 class="size-4" />
                            {{ t('storage.deleteSelected') }}
                        </button>
                        <button class="cursor-not-allowed rounded-md border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-400" disabled type="button">
                            {{ t('storage.archive') }}
                        </button>
                        <button class="cursor-not-allowed rounded-md border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-400" disabled type="button">
                            {{ t('storage.emptyTrash') }}
                        </button>
                    </div>
                </div>

                <div v-if="hasSelectedOriginalPdfWithDependents" class="border-b border-amber-100 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-900">
                    {{ t('storage.deletePdfWarning') }}
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="w-10 px-4 py-3"></th>
                                <th class="px-4 py-3">{{ t('storage.name') }}</th>
                                <th class="px-4 py-3">{{ t('storage.category') }}</th>
                                <th class="px-4 py-3">{{ t('storage.size') }}</th>
                                <th class="px-4 py-3">{{ t('storage.uploaded') }}</th>
                                <th class="px-4 py-3">{{ t('storage.lastAccessed') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('manufacturerProducts.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in items" :key="item.key" class="hover:bg-slate-50/70">
                                <td class="px-4 py-4">
                                    <input
                                        class="size-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500"
                                        type="checkbox"
                                        :checked="selected.includes(item.key)"
                                        @change="toggleItem(item.key)"
                                    />
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-black text-slate-950">{{ item.name }}</div>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs font-semibold">
                                        <span v-if="item.can_be_regenerated" class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700">{{ t('storage.canBeRegenerated') }}</span>
                                        <span v-if="item.dependent_records_count > 0" class="rounded-full bg-amber-50 px-2 py-1 text-amber-800">
                                            {{ item.dependent_records_count }} {{ t('storage.dependentRecords') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-700">{{ t(`storage.category.${item.type}` as any) }}</td>
                                <td class="px-4 py-4 font-semibold text-slate-700">{{ item.size }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ item.uploaded_at || '—' }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ item.last_accessed_at || '—' }}</td>
                                <td class="px-4 py-4 text-right">
                                    <button class="rounded-md border border-red-200 px-3 py-2 text-xs font-black text-red-700 hover:bg-red-50" type="button" @click="deleteItem(item)">
                                        {{ t('storage.delete') }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="items.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-600">
                                    {{ t('storage.noAssets') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">
                <EntitlementDiagnostics />
            </div>
        </main>
    </div>
</template>
