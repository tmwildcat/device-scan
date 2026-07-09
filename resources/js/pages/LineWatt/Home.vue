<script setup lang="ts">
import AdPromotionPlayer from '@/components/linewatt/AdPromotionPlayer.vue';
import EngineeringSearchBar from '@/components/linewatt/EngineeringSearchBar.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BadgeCheck, Clock3, DatabaseZap, Factory, GitCompare, Layers3, ShieldCheck } from 'lucide-vue-next';

defineProps<{
    libraryStats: {
        curated_datasheets: number;
        engineering_records: number;
        manufacturers: number;
        technologies: number;
        recently_updated: number;
    };
    featuredManufacturers: Array<{
        manufacturer: string;
        count?: number | null;
    }>;
    featuredTechnologies: string[];
}>();
</script>

<template>
    <Head title="LineWatt Library" />

    <div class="min-h-screen bg-slate-50 text-slate-950">
        <WorkspaceNavigation />

        <main>
            <section class="border-b border-slate-200 bg-white">
                <div class="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8 lg:py-20">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">
                            The home of trusted renewable energy engineering data
                        </p>
                        <h1 class="mt-5 max-w-4xl text-4xl font-black tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                            Create, manage, search, compare and distribute trusted Engineering Records.
                        </h1>
                        <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-600">
                            LineWatt Library gives renewable energy teams a premium knowledge portal for solar modules,
                            string inverters, hybrid inverters and central inverter engineering data.
                        </p>

                        <div class="mt-8">
                            <EngineeringSearchBar />
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link
                                href="/search"
                                class="inline-flex items-center gap-2 rounded-md bg-slate-950 px-4 py-3 text-sm font-bold text-white hover:bg-slate-800"
                            >
                                Open Engineering Search
                                <ArrowRight class="size-4" />
                            </Link>
                            <Link
                                href="/my-library"
                                class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-100"
                            >
                                My Private Datasets
                            </Link>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <AdPromotionPlayer />
                        <div class="rounded-lg border border-slate-200 bg-slate-950 p-5 text-white shadow-sm">
                            <div class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-300">
                                Library Signal
                            </div>
                            <dl class="mt-5 space-y-4">
                                <div>
                                    <dt class="text-sm text-slate-400">Engineering Records</dt>
                                    <dd class="text-2xl font-black">{{ libraryStats.engineering_records }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-slate-400">Curated datasheets</dt>
                                    <dd class="text-2xl font-black">{{ libraryStats.curated_datasheets }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-slate-400">Manufacturers</dt>
                                    <dd class="text-2xl font-black">{{ libraryStats.manufacturers }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-950">Library Statistics</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Public discovery stays high-level; private dataset operations live inside My Private Datasets.
                        </p>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <DatabaseZap class="size-5 text-emerald-700" />
                            <dt class="mt-4 text-sm font-bold text-slate-500">Curated Datasheets</dt>
                            <dd class="mt-1 text-3xl font-black text-slate-950">{{ libraryStats.curated_datasheets }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <BadgeCheck class="size-5 text-sky-700" />
                            <dt class="mt-4 text-sm font-bold text-slate-500">Engineering Records</dt>
                            <dd class="mt-1 text-3xl font-black text-slate-950">{{ libraryStats.engineering_records }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <Factory class="size-5 text-violet-700" />
                            <dt class="mt-4 text-sm font-bold text-slate-500">Manufacturers</dt>
                            <dd class="mt-1 text-3xl font-black text-slate-950">{{ libraryStats.manufacturers }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <Layers3 class="size-5 text-amber-700" />
                            <dt class="mt-4 text-sm font-bold text-slate-500">Technologies</dt>
                            <dd class="mt-1 text-3xl font-black text-slate-950">{{ libraryStats.technologies }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <Clock3 class="size-5 text-rose-700" />
                            <dt class="mt-4 text-sm font-bold text-slate-500">Recently Updated</dt>
                            <dd class="mt-1 text-3xl font-black text-slate-950">{{ libraryStats.recently_updated }}</dd>
                        </div>
                    </div>

                    <div class="mt-8 grid gap-6 lg:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-slate-500">
                                Featured Manufacturers
                            </h3>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    v-for="manufacturer in featuredManufacturers"
                                    :key="manufacturer.manufacturer"
                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700"
                                >
                                    {{ manufacturer.manufacturer }}
                                    <span v-if="manufacturer.count !== null && manufacturer.count !== undefined" class="text-slate-400">
                                        {{ manufacturer.count }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-slate-500">
                                Featured Technologies
                            </h3>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    v-for="technology in featuredTechnologies"
                                    :key="technology"
                                    class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-800"
                                >
                                    {{ technology }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y border-slate-200 bg-white">
                <div class="mx-auto grid max-w-7xl gap-4 px-4 py-12 sm:px-6 md:grid-cols-4 lg:px-8">
                    <div class="rounded-lg border border-slate-200 p-5">
                        <DatabaseZap class="size-5 text-emerald-700" />
                        <h3 class="mt-4 font-bold">Engineering Search</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Search by manufacturer, model series, power, technology and validation grade.
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-5">
                        <GitCompare class="size-5 text-sky-700" />
                        <h3 class="mt-4 font-bold">Comparison Ready</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Compare two Engineering Records without modifying source records.
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-5">
                        <ShieldCheck class="size-5 text-amber-700" />
                        <h3 class="mt-4 font-bold">Validation Aware</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Surface quality grades, warnings and review status where available.
                        </p>
                    </div>
                    <div class="rounded-lg border border-slate-200 p-5">
                        <BadgeCheck class="size-5 text-violet-700" />
                        <h3 class="mt-4 font-bold">Curated Library</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Keep My Private Datasets separate from published LineWatt Library records.
                        </p>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-slate-950 p-8 text-white">
                    <div class="grid gap-6 md:grid-cols-[1fr_auto] md:items-center">
                        <div>
                            <h2 class="text-2xl font-black">Build your trusted private engineering datasets.</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                                Subscribers can compile manufacturer PDFs into private Engineering Records while
                                searching the central LineWatt Library from the same professional interface.
                            </p>
                        </div>
                        <Link
                            href="/my-library"
                            class="inline-flex items-center justify-center rounded-md bg-emerald-300 px-5 py-3 text-sm font-black text-slate-950 hover:bg-emerald-200"
                        >
                            Open My Private Datasets
                        </Link>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
