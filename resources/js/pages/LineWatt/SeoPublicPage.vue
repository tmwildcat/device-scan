<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicSiteLayout from '@/components/linewatt/PublicSiteLayout.vue';
import { computed } from 'vue';

const props = defineProps<{
    entity: {
        kind: string;
        title: string;
        description?: string | null;
        manufacturer?: string | null;
        device_type?: string | null;
        technology?: string | null;
        status?: string | null;
    };
    seo: {
        title?: string | null;
        description?: string | null;
        keywords?: string | null;
        canonical_url: string;
        robots?: string | null;
        og_title?: string | null;
        og_description?: string | null;
        og_image?: string | null;
        twitter_title?: string | null;
        twitter_description?: string | null;
        twitter_image?: string | null;
        status?: string | null;
    };
    structuredData: Record<string, unknown>;
    internalLinks: Array<{ label: string; href: string }>;
}>();

const labelize = (value?: string | null) => (value || 'Page').replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const structuredDataScripts = computed(() =>
    Object.entries(props.structuredData).map(([key, data]) => ({
        key,
        json: JSON.stringify(data).replace(/</g, '\\u003c'),
    })),
);
</script>

<template>
    <Head :title="seo.title || entity.title">
        <meta v-if="seo.description" name="description" :content="seo.description" />
        <meta v-if="seo.keywords" name="keywords" :content="seo.keywords" />
        <meta v-if="seo.robots" name="robots" :content="seo.robots" />
        <link rel="canonical" :href="seo.canonical_url" />
        <meta property="og:title" :content="seo.og_title || seo.title || entity.title" />
        <meta v-if="seo.og_description || seo.description" property="og:description" :content="seo.og_description || seo.description || ''" />
        <meta v-if="seo.og_image" property="og:image" :content="seo.og_image" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seo.twitter_title || seo.title || entity.title" />
        <meta v-if="seo.twitter_description || seo.description" name="twitter:description" :content="seo.twitter_description || seo.description || ''" />
        <meta v-if="seo.twitter_image" name="twitter:image" :content="seo.twitter_image" />
        <component
            :is="'script'"
            v-for="entry in structuredDataScripts"
            :key="entry.key"
            type="application/ld+json"
            v-text="entry.json"
        />
    </Head>

    <PublicSiteLayout>
        <section class="mx-auto max-w-6xl px-6 py-12">
            <div class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">{{ labelize(entity.kind) }}</p>
                <h1 class="mt-3 text-5xl font-black tracking-tight text-slate-950">{{ entity.title }}</h1>
                <p class="mt-4 max-w-3xl text-lg leading-8 text-slate-600">
                    {{ seo.description || entity.description || 'LineWatt Library renewable energy engineering data.' }}
                </p>

                <div class="mt-7 flex flex-wrap gap-2">
                    <span v-if="entity.manufacturer" class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-black text-emerald-800">{{ entity.manufacturer }}</span>
                    <span v-if="entity.device_type" class="rounded-full bg-sky-50 px-3 py-1.5 text-sm font-black capitalize text-sky-800">{{ labelize(entity.device_type) }}</span>
                    <span v-if="entity.technology" class="rounded-full bg-violet-50 px-3 py-1.5 text-sm font-black text-violet-800">{{ entity.technology }}</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-black capitalize text-slate-700">{{ labelize(entity.status) }}</span>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
                <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-2xl font-black text-slate-950">Engineering Discovery</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        This page is part of the LineWatt Library canonical URL system. Structured engineering data, source datasheet
                        references and internal links will expand as the public library grows.
                    </p>
                    <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Canonical URL</div>
                        <div class="mt-2 break-all font-semibold text-slate-800">{{ seo.canonical_url }}</div>
                    </div>
                </section>

                <aside class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Explore Related Library Pages</h2>
                    <div class="mt-4 grid gap-2">
                        <Link
                            v-for="link in internalLinks"
                            :key="link.href"
                            :href="link.href"
                            class="rounded-md border border-slate-200 px-4 py-3 text-sm font-black text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                </aside>
            </div>
        </section>
    </PublicSiteLayout>
</template>
