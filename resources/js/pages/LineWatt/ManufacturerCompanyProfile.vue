<script setup lang="ts">
import ManufacturerAdminShell from '@/components/linewatt/admin/ManufacturerAdminShell.vue';
import ManufacturerPublicProfilePreview from '@/components/linewatt/manufacturer/ManufacturerPublicProfilePreview.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { BadgeCheck, Building2, Copy, FileBadge2, Globe2, MapPinned, QrCode, ShieldCheck, UploadCloud } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    company: { name: string; plan_code: string; plan_label: string; can_upgrade: boolean; upgrade_message: string | null };
    profile: {
        identity: Record<string, string>;
        businessDescription: Record<string, string>;
        factorySummary: Record<string, string>;
        distributionSummary: Record<string, string>;
        contacts: Array<Record<string, string>>;
        libraryPresence: {
            public_url: string;
            qr_png_href: string;
            qr_svg_href: string;
            profile_status: string;
            visibility: string;
            last_published: string;
            seo_status: string;
        };
        verification: Record<string, string>;
    };
    logo: {
        preview_href?: string | null;
        original_filename?: string | null;
        updated_at?: string | null;
        upload_href: string;
        remove_href: string;
    };
    visitorPreview: InstanceType<typeof ManufacturerPublicProfilePreview>['$props']['profile'];
    companyDocuments: Array<Record<string, string>>;
}>();

const documentColumns = ['Scope', 'Document title', 'Category', 'Revision', 'Language', 'Status', 'Uploaded'];
const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>();
const activeTab = ref<'profile' | 'preview'>('profile');
const previewViewport = ref<'desktop' | 'mobile'>('desktop');
const previewState = ref<'draft' | 'published'>('draft');
const fileInput = ref<HTMLInputElement | null>(null);
const logoForm = useForm<{ logo: File | null }>({ logo: null });
const removeLogoForm = useForm({});
const copied = ref(false);
const qrInlineHref = computed(() => `${props.profile.libraryPresence.qr_png_href}?inline=1`);

function visibleEntries(items: Record<string, string | number>): Array<[string, string | number]> {
    return Object.entries(items).filter(([label]) => label !== 'href');
}

function isLong(value: string | number): boolean {
    return String(value).length > 60;
}

function chooseLogo(): void {
    fileInput.value?.click();
}

function uploadLogo(event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    if (!file) {
        return;
    }

    logoForm.logo = file;
    logoForm.post(props.logo.upload_href, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            logoForm.logo = null;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}

function removeLogo(): void {
    removeLogoForm.delete(props.logo.remove_href, { preserveScroll: true });
}

async function copyPublicUrl(): Promise<void> {
    await navigator.clipboard.writeText(props.profile.libraryPresence.public_url);
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 1600);
}
</script>

<template>
    <Head title="Company Profile" />

    <ManufacturerAdminShell
        :company="company"
        title="Company Profile"
        subtitle="Company-level manufacturer master data. Datasheet and model metadata remain managed at datasheet level."
        :breadcrumbs="[{ label: 'Dashboard', href: '/admin/manufacturer' }, { label: 'Company' }, { label: 'Company Profile' }]"
    >
        <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ page.props.flash.success }}</div>

        <section class="mb-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="grid size-20 place-items-center overflow-hidden rounded-lg bg-gradient-to-br from-emerald-500 to-sky-500 text-xl font-black text-white">
                        <img v-if="logo.preview_href" :src="logo.preview_href" alt="" class="h-full w-full object-contain bg-white" />
                        <span v-else>{{ company.name.slice(0, 2).toUpperCase() }}</span>
                    </div>
                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.14em] text-emerald-700">Manufacturer master profile</div>
                        <h2 class="mt-1 text-2xl font-black">{{ company.name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">Logo, public presence and company-level business data.</p>
                        <p v-if="logo.original_filename" class="mt-1 text-xs font-bold text-slate-500">{{ logo.original_filename }} · {{ logo.updated_at || 'Updated pending' }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <input ref="fileInput" class="hidden" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="uploadLogo" />
                    <button class="inline-flex items-center gap-2 rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white disabled:opacity-45" :disabled="logoForm.processing" type="button" @click="chooseLogo">
                        <UploadCloud class="size-4" />
                        {{ logo.preview_href ? 'Replace logo' : 'Upload logo' }}
                    </button>
                    <button v-if="logo.preview_href" class="rounded-md border border-red-200 px-4 py-2 text-sm font-black text-red-700 disabled:opacity-45" :disabled="removeLogoForm.processing" type="button" @click="removeLogo">Remove</button>
                </div>
            </div>
        </section>

        <div class="mb-5 flex gap-2 overflow-x-auto rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
            <button class="rounded-md px-4 py-2 text-sm font-black" :class="activeTab === 'profile' ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50'" type="button" @click="activeTab = 'profile'">Profile Data</button>
            <button class="rounded-md px-4 py-2 text-sm font-black" :class="activeTab === 'preview' ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-50'" type="button" @click="activeTab = 'preview'">Visitor Preview</button>
        </div>

        <section v-if="activeTab === 'profile'" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="space-y-5">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start gap-3">
                        <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                            <Building2 class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black">Company Identity</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Legal and brand identity used across LineWatt Library.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label v-for="[label, value] in visibleEntries(profile.identity)" :key="label" class="block">
                            <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ label }}</span>
                            <input class="mt-2 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" :value="value" />
                        </label>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start gap-3">
                        <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                            <Globe2 class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black">Business Description</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">High-level manufacturer positioning. Product-family metadata belongs on datasheets.</p>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label v-for="[label, value] in visibleEntries(profile.businessDescription)" :key="label" class="block">
                            <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ label }}</span>
                            <textarea v-if="isLong(value)" class="mt-2 min-h-24 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" :value="value" />
                            <input v-else class="mt-2 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" :value="value" />
                        </label>
                    </div>
                </section>

                <div class="grid gap-5 lg:grid-cols-2">
                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-start gap-3">
                            <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                <MapPinned class="size-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-black">Factory Summary</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Company-level manufacturing summary.</p>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label v-for="[label, value] in visibleEntries(profile.factorySummary)" :key="label" class="block">
                                <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ label }}</span>
                                <input class="mt-2 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" :value="value" />
                            </label>
                        </div>
                        <Link :href="profile.factorySummary.href" class="mt-4 inline-flex rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white">Open factory locations</Link>
                    </section>

                    <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-start gap-3">
                            <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                <Globe2 class="size-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-black">Distribution Summary</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Availability and channel summary by market.</p>
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label v-for="[label, value] in visibleEntries(profile.distributionSummary)" :key="label" class="block">
                                <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ label }}</span>
                                <input class="mt-2 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" :value="value" />
                            </label>
                        </div>
                        <Link :href="profile.distributionSummary.href" class="mt-4 inline-flex rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white">Open distribution countries</Link>
                    </section>
                </div>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start gap-3">
                        <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                            <BadgeCheck class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black">Contacts</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Company-level contacts. Country-level contacts remain in the regional contact page.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                                <tr>
                                    <th class="px-3 py-3 text-left">Type</th>
                                    <th class="px-3 py-3 text-left">Name</th>
                                    <th class="px-3 py-3 text-left">Email</th>
                                    <th class="px-3 py-3 text-left">Phone</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="contact in profile.contacts" :key="contact.Type">
                                    <td class="px-3 py-4 font-black">{{ contact.Type }}</td>
                                    <td class="px-3 py-4">{{ contact.Name }}</td>
                                    <td class="px-3 py-4">{{ contact.Email }}</td>
                                    <td class="px-3 py-4">{{ contact.Phone }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Link href="/admin/manufacturer/country-contacts" class="mt-4 inline-flex rounded-md border border-slate-200 px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50">Open country contacts</Link>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start gap-3">
                        <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                            <FileBadge2 class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black">Company-wide Supporting Documents</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Corporate documents that belong to the manufacturer, not a specific datasheet.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.14em] text-slate-500">
                                <tr>
                                    <th v-for="column in documentColumns" :key="column" class="px-3 py-3 text-left">{{ column }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(document, index) in companyDocuments.slice(0, 5)" :key="index">
                                    <td v-for="column in documentColumns" :key="column" class="px-3 py-4">
                                        <span v-if="column === 'Scope'" class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-black text-sky-800 ring-1 ring-sky-200">{{ document[column] }}</span>
                                        <span v-else>{{ document[column] || 'Pending' }}</span>
                                    </td>
                                </tr>
                                <tr v-if="companyDocuments.length === 0">
                                    <td :colspan="documentColumns.length" class="px-3 py-8 text-center text-slate-600">No company-wide documents yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Link href="/admin/manufacturer/supporting-documents?scope=company" class="mt-4 inline-flex rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white">Manage company-wide documents</Link>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start gap-3">
                        <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                            <QrCode class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black">Library Presence</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Public profile address, QR code and publication state.</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Public URL</div>
                            <div class="mt-2 flex gap-2">
                                <input class="min-w-0 flex-1 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm" :value="profile.libraryPresence.public_url" readonly />
                                <button class="inline-flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm font-black text-slate-700 hover:bg-slate-50" type="button" @click="copyPublicUrl">
                                    <Copy class="size-4" />
                                    {{ copied ? 'Copied' : 'Copy' }}
                                </button>
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <img :src="qrInlineHref" alt="Manufacturer public URL QR code" class="mx-auto size-40 rounded-md bg-white p-2" />
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                <a :href="profile.libraryPresence.qr_png_href" class="rounded-md bg-slate-950 px-3 py-2 text-center text-xs font-black text-white">Download PNG</a>
                                <a :href="profile.libraryPresence.qr_svg_href" class="rounded-md border border-slate-200 bg-white px-3 py-2 text-center text-xs font-black text-slate-700">Download SVG</a>
                            </div>
                            <button class="mt-2 w-full rounded-md border border-dashed border-slate-300 px-3 py-2 text-xs font-black text-slate-500" type="button" disabled>Print placeholder</button>
                        </div>

                        <div class="grid gap-3">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Profile Status</div>
                                <div class="mt-1 font-black">{{ profile.libraryPresence.profile_status }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Visibility</div>
                                <div class="mt-1 font-black">{{ profile.libraryPresence.visibility }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Last Published</div>
                                <div class="mt-1 font-black">{{ profile.libraryPresence.last_published }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Future SEO</div>
                                <div class="mt-1 font-black">{{ profile.libraryPresence.seo_status }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start gap-3">
                        <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                            <ShieldCheck class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black">Review / Verification</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Internal verification state for company-level data.</p>
                        </div>
                    </div>
                    <div class="grid gap-4">
                        <label v-for="[label, value] in visibleEntries(profile.verification)" :key="label" class="block">
                            <span class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ label }}</span>
                            <textarea v-if="isLong(value)" class="mt-2 min-h-24 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" :value="value" />
                            <input v-else class="mt-2 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm" :value="value" />
                        </label>
                    </div>
                    <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-800">
                        Save, approve and verification workflow actions will be connected in a later manufacturer data workflow.
                    </div>
                </section>
            </aside>
        </section>

        <section v-if="activeTab === 'preview'" class="space-y-5">
            <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black">Visitor Preview</h2>
                        <p class="mt-1 text-sm text-slate-600">A future public manufacturer page rendered from current profile data. This does not publish the page.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-md px-3 py-2 text-sm font-black" :class="previewViewport === 'desktop' ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700'" type="button" @click="previewViewport = 'desktop'">Desktop Preview</button>
                        <button class="rounded-md px-3 py-2 text-sm font-black" :class="previewViewport === 'mobile' ? 'bg-slate-950 text-white' : 'border border-slate-200 text-slate-700'" type="button" @click="previewViewport = 'mobile'">Mobile Preview</button>
                        <button class="rounded-md px-3 py-2 text-sm font-black" :class="previewState === 'draft' ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-200' : 'border border-slate-200 text-slate-700'" type="button" @click="previewState = 'draft'">Draft Preview</button>
                        <button class="rounded-md px-3 py-2 text-sm font-black" :class="previewState === 'published' ? 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-200' : 'border border-slate-200 text-slate-700'" type="button" @click="previewState = 'published'">Published Preview</button>
                    </div>
                </div>
            </div>

            <ManufacturerPublicProfilePreview :profile="visitorPreview" :viewport="previewViewport" :state="previewState" />
        </section>
    </ManufacturerAdminShell>
</template>
