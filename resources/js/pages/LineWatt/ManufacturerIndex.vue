<script setup lang="ts">
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { Head, Link } from '@inertiajs/vue3';
import { Factory } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    letter: string;
    activeTab: 'modules' | 'inverters' | 'others';
    tabs: {
        modules: number;
        inverters: number;
        others: number;
    };
    manufacturers: {
        data: Array<{
            manufacturer: string;
            records_count: number;
            href: string;
        }>;
        current_page: number;
        from: number | null;
        last_page: number;
        to: number | null;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    alphabet: string[];
}>();

const { dir, t } = useLineWattI18n();

const tabItems = computed(() => [
    { label: t('manufacturerDirectory.modules'), value: 'modules', count: props.tabs.modules },
    { label: t('manufacturerDirectory.inverters'), value: 'inverters', count: props.tabs.inverters },
    { label: t('manufacturerDirectory.others'), value: 'others', count: props.tabs.others },
]);

function tabHref(tab: string): string {
    return `/manufacturers?tab=${tab}&letter=${props.letter}`;
}

function letterHref(letter: string): string {
    return `/manufacturers?tab=${props.activeTab}&letter=${letter}`;
}
</script>

<template>
    <Head :title="t('manufacturerDirectory.headTitle')" />

    <div class="min-h-screen bg-slate-50 text-slate-950" :dir="dir">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">{{ t('manufacturerDirectory.eyebrow') }}</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">{{ t('manufacturerDirectory.title') }}</h1>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ t('manufacturerDirectory.description') }}
                        </p>
                    </div>
                    <Link href="/my-library" class="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        {{ t('manufacturerDirectory.backToPrivateDatasets') }}
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

                <div class="mt-5 flex flex-wrap gap-1.5">
                    <Link
                        v-for="item in alphabet"
                        :key="item"
                        :href="letterHref(item)"
                        class="flex size-9 items-center justify-center rounded-md text-sm font-black"
                        :class="letter === item ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    >
                        {{ item }}
                    </Link>
                </div>
            </section>

            <section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <Factory class="size-5 text-emerald-700" />
                        <div>
                            <h2 class="font-black text-slate-950">{{ letter }} {{ t('manufacturerDirectory.manufacturersSuffix') }}</h2>
                            <p class="text-sm text-slate-500">
                                {{ t('manufacturerDirectory.showing') }} {{ manufacturers.from || 0 }}-{{ manufacturers.to || 0 }}.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="manufacturers.data.length" class="divide-y divide-slate-100">
                    <Link
                        v-for="manufacturer in manufacturers.data"
                        :key="manufacturer.manufacturer"
                        :href="manufacturer.href"
                        class="grid gap-2 px-5 py-4 hover:bg-slate-50 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                    >
                        <div class="min-w-0">
                            <h3 class="truncate font-black text-slate-950">{{ manufacturer.manufacturer }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ manufacturer.records_count }} {{ t('manufacturerDirectory.recordsCount') }}</p>
                        </div>
                        <span class="text-sm font-bold text-emerald-700">{{ t('manufacturerDirectory.openProducts') }}</span>
                    </Link>
                </div>

                <div v-else class="p-10 text-center">
                    <h2 class="text-lg font-black text-slate-950">{{ t('manufacturerDirectory.noManufacturers') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ t('manufacturerDirectory.tryAnother') }}</p>
                </div>
            </section>

            <nav v-if="manufacturers.last_page > 1" class="mt-6 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in manufacturers.links"
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
