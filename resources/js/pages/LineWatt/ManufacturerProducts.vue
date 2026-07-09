<script setup lang="ts">
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import LifecycleStatusBadge from '@/components/linewatt/LifecycleStatusBadge.vue';
import ValidationStatusBadge from '@/components/linewatt/ValidationStatusBadge.vue';
import ManufacturerPublicProfilePreview from '@/components/linewatt/manufacturer/ManufacturerPublicProfilePreview.vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    manufacturer: string;
    profile: InstanceType<typeof ManufacturerPublicProfilePreview>['$props']['profile'];
    isLibraryStaffView: boolean;
    activeTab: 'modules' | 'inverters' | 'others';
    tabs: {
        modules: number;
        inverters: number;
        others: number;
    };
    records: {
        data: any[];
        current_page: number;
        from: number | null;
        last_page: number;
        to: number | null;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();

const page = usePage();
const { dir, t } = useLineWattI18n();
const isPublisher = computed(() => (page.props as any).auth?.user?.role === 'library_publisher');
const backHref = computed(() => isPublisher.value ? '/publisher' : '/my-library');
const backLabel = computed(() => isPublisher.value ? t('manufacturerProducts.backToPublisher') : t('manufacturerProducts.backToPrivateDatasets'));

const tabItems = computed(() => [
    { label: t('manufacturerDirectory.modules'), value: 'modules', count: props.tabs.modules },
    { label: t('manufacturerDirectory.inverters'), value: 'inverters', count: props.tabs.inverters },
    { label: t('manufacturerDirectory.others'), value: 'others', count: props.tabs.others },
]);

function tabHref(tab: string): string {
    return `/manufacturers/${encodeURIComponent(props.manufacturer)}?tab=${tab}`;
}

function powerLabel(record: any): string {
    if (record.power_class_w) return `${record.power_class_w} W`;
    if (record.power_class_kw) return `${record.power_class_kw} kW`;
    return '—';
}
</script>

<template>
    <Head :title="manufacturer" />

    <div class="min-h-screen bg-slate-50 text-slate-950" :dir="dir">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">{{ t('manufacturerProducts.eyebrow') }}</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ manufacturer }}</h1>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ isLibraryStaffView ? t('manufacturerProducts.staffSubtitle') : t('manufacturerProducts.publishedSubtitle') }}
                        </p>
                    </div>
                    <Link :href="backHref" class="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        {{ backLabel }}
                    </Link>
                </div>

                <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
                    <Link
                        v-for="tab in tabItems"
                        :key="tab.value"
                        :href="tabHref(tab.value)"
                        class="rounded-md px-4 py-2 text-sm font-bold"
                        :class="activeTab === tab.value ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    >
                        {{ tab.label }}
                        <span class="ml-1 opacity-75">{{ tab.count }}</span>
                    </Link>
                </div>
            </section>

            <section class="mt-6">
                <ManufacturerPublicProfilePreview
                    :profile="profile"
                    viewport="desktop"
                    state="published"
                    :show-chrome="false"
                />
            </section>

            <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-black text-slate-950">{{ t('manufacturerProducts.products') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ isLibraryStaffView ? t('manufacturerProducts.staffRecordsNotice') : t('manufacturerProducts.approvedRecordsNotice') }}
                        {{ t('manufacturerDirectory.showing') }} {{ records.from || 0 }}-{{ records.to || 0 }}.
                    </p>
                </div>

                <div v-if="records.data.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">{{ t('manufacturerProducts.engineeringData') }}</th>
                                <th class="px-4 py-3">{{ t('manufacturerProducts.series') }}</th>
                                <th class="px-4 py-3">{{ t('manufacturerProducts.power') }}</th>
                                <th class="px-4 py-3">{{ t('manufacturerProducts.status') }}</th>
                                <th class="px-4 py-3">{{ t('manufacturerProducts.validation') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('manufacturerProducts.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="record in records.data" :key="record.uuid || record.id" class="hover:bg-slate-50/70">
                                <td class="px-4 py-4">
                                    <div class="font-black text-slate-950">{{ record.display_name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ record.model_name || record.model_series || t('manufacturerProducts.modelPending') }}</div>
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-700">{{ record.model_series || record.series || '—' }}</td>
                                <td class="px-4 py-4 font-semibold text-slate-700">{{ powerLabel(record) }}</td>
                                <td class="px-4 py-4"><LifecycleStatusBadge :status="record.status" /></td>
                                <td class="px-4 py-4"><ValidationStatusBadge :status="record.validation_status" /></td>
                                <td class="px-4 py-4 text-right">
                                    <Link :href="record.href" class="inline-flex rounded-md bg-slate-950 px-3 py-2 text-xs font-black text-white hover:bg-slate-800">
                                        {{ t('manufacturerProducts.open') }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="p-10 text-center">
                    <h2 class="text-lg font-black text-slate-950">{{ t('manufacturerProducts.noProducts') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ t('manufacturerProducts.tryAnotherTab') }}</p>
                </div>
            </section>

            <nav v-if="records.last_page > 1" class="mt-6 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in records.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded-md border px-3 py-2 text-sm font-bold"
                    :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'pointer-events-none opacity-40'"
                    v-html="link.label"
                />
            </nav>
        </main>
    </div>
</template>
