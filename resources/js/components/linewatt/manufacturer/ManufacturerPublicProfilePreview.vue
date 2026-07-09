<script setup lang="ts">
import { useLineWattI18n } from '@/lib/linewatt-i18n';

defineProps<{
    profile: {
        company_name: string;
        logo_href?: string | null;
        description: string;
        technologies: string[];
        product_categories: string[];
        factory_summary: {
            known_factories: number;
            primary_country: string;
            certification_status: string;
        };
        distribution_summary: {
            countries: number;
            priority_regions: string[];
            channel_model: string;
        };
        latest_datasheets: Array<{ title: string; series?: string | null; status?: string | null; updated?: string | null }>;
        company_documents: Array<Record<string, string>>;
        contacts: Array<{ label: string; value: string }>;
    };
    viewport: 'desktop' | 'mobile';
    state: 'draft' | 'published';
    showChrome?: boolean;
}>();

const { t } = useLineWattI18n();
</script>

<template>
    <div class="rounded-lg border border-slate-200 bg-slate-100 p-4" :class="showChrome === false ? 'bg-transparent p-0 border-0' : ''">
        <div v-if="showChrome !== false" class="mb-3 flex items-center justify-between text-xs font-black uppercase tracking-[0.14em] text-slate-500">
            <span>{{ viewport }} {{ t('publicManufacturer.visitorPreview') }}</span>
            <span class="rounded-full px-2.5 py-1" :class="state === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">{{ state }}</span>
        </div>

        <article class="mx-auto overflow-hidden rounded-lg bg-white shadow-sm" :class="viewport === 'mobile' ? 'max-w-sm' : 'max-w-5xl'">
            <header class="border-b border-slate-200 bg-gradient-to-br from-emerald-50 via-white to-sky-50 p-6" :class="viewport === 'mobile' ? 'space-y-4' : 'flex items-start justify-between gap-6'">
                <div class="flex items-center gap-4">
                    <div class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-lg bg-gradient-to-br from-emerald-500 to-sky-500 text-xl font-black text-white">
                        <img v-if="profile.logo_href" :src="profile.logo_href" alt="" class="h-full w-full object-contain bg-white" />
                        <span v-else>{{ profile.company_name.slice(0, 2).toUpperCase() }}</span>
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">{{ t('publicManufacturer.linewattManufacturer') }}</p>
                        <h1 class="mt-1 text-3xl font-black text-slate-950" :class="viewport === 'mobile' ? 'text-2xl' : ''">{{ profile.company_name }}</h1>
                    </div>
                </div>
                <div class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-200">{{ t('publicManufacturer.poweredBy') }}</div>
            </header>

            <div class="grid gap-5 p-6" :class="viewport === 'mobile' ? '' : 'lg:grid-cols-[minmax(0,1fr)_300px]'">
                <main class="space-y-5">
                    <section>
                        <h2 class="text-lg font-black">{{ t('publicManufacturer.overview') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ profile.description }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span v-for="technology in profile.technologies" :key="technology" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-800 ring-1 ring-emerald-100">{{ technology }}</span>
                            <span v-for="category in profile.product_categories" :key="category" class="rounded-full bg-sky-50 px-3 py-1 text-xs font-black text-sky-800 ring-1 ring-sky-100">{{ category }}</span>
                        </div>
                    </section>

                    <section>
                        <h2 class="text-lg font-black">{{ t('publicManufacturer.latestDatasheets') }}</h2>
                        <div class="mt-3 divide-y divide-slate-100 rounded-lg border border-slate-200">
                            <div v-for="datasheet in profile.latest_datasheets" :key="`${datasheet.title}-${datasheet.updated}`" class="p-3">
                                <div class="font-black">{{ datasheet.title }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ datasheet.series || t('publicManufacturer.seriesPending') }} · {{ datasheet.status || t('publicManufacturer.pending') }} · {{ datasheet.updated || t('publicManufacturer.updatedPending') }}</div>
                            </div>
                            <div v-if="profile.latest_datasheets.length === 0" class="p-4 text-sm text-slate-600">{{ t('publicManufacturer.datasheetsEmpty') }}</div>
                        </div>
                    </section>

                    <section>
                        <h2 class="text-lg font-black">{{ t('publicManufacturer.companyDocuments') }}</h2>
                        <div class="mt-3 grid gap-2">
                            <div v-for="document in profile.company_documents" :key="document['Document title']" class="rounded-md border border-slate-200 p-3 text-sm">
                                <div class="font-black">{{ document['Document title'] }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ document.Category }} · {{ document.Status }}</div>
                            </div>
                            <div v-if="profile.company_documents.length === 0" class="rounded-md border border-dashed border-slate-200 p-4 text-sm text-slate-600">{{ t('publicManufacturer.companyDocumentsEmpty') }}</div>
                        </div>
                    </section>
                </main>

                <aside class="space-y-4">
                    <section class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h2 class="font-black">{{ t('publicManufacturer.factorySummary') }}</h2>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ t('publicManufacturer.factories') }}</dt><dd class="font-black">{{ profile.factory_summary.known_factories }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ t('publicManufacturer.primaryCountry') }}</dt><dd class="font-black">{{ profile.factory_summary.primary_country }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ t('publicManufacturer.certifications') }}</dt><dd class="font-black">{{ profile.factory_summary.certification_status }}</dd></div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h2 class="font-black">{{ t('publicManufacturer.distribution') }}</h2>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ t('publicManufacturer.countries') }}</dt><dd class="font-black">{{ profile.distribution_summary.countries }}</dd></div>
                            <div><dt class="text-slate-500">{{ t('publicManufacturer.regions') }}</dt><dd class="mt-1 font-black">{{ profile.distribution_summary.priority_regions.join(', ') }}</dd></div>
                            <div><dt class="text-slate-500">{{ t('publicManufacturer.channel') }}</dt><dd class="mt-1 font-black">{{ profile.distribution_summary.channel_model }}</dd></div>
                        </dl>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <h2 class="font-black">{{ t('publicManufacturer.contacts') }}</h2>
                        <div class="mt-3 space-y-2 text-sm">
                            <div v-for="contact in profile.contacts" :key="contact.label">
                                <div class="font-black">{{ contact.label }}</div>
                                <div class="text-slate-600">{{ contact.value }}</div>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </article>
    </div>
</template>
