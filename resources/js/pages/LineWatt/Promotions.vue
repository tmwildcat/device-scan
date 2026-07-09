<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    roleLabel?: string | null;
    promotions: { data: Array<any>; links: Array<{ url: string | null; label: string; active: boolean }> };
    plans: Record<string, string>;
    discountTypes: Record<string, string>;
    statuses: Record<string, string>;
}>();

const page = usePage<{ flash?: { success?: string | null } }>();
const form = useForm({
    code: '',
    title: '',
    description: '',
    discount_type: 'percent',
    discount_value: '',
    applies_to_plan: 'all',
    starts_at: '',
    ends_at: '',
    max_redemptions: '',
    status: 'draft',
    notes: '',
});

function submit(): void {
    form.post('/admin/library/promotions', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function postAction(url: string): void {
    router.post(url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Promotions" />

    <LibraryAdminShell
        title="Promotions"
        subtitle="Commercial discount coupons and offers. Paddle coupon synchronization is intentionally pending."
        :role-label="roleLabel"
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Library Management' },
            { label: 'Promotions' },
        ]"
    >
        <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
            {{ page.props.flash.success }}
        </div>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div>
                        <h2 class="font-black">Promotion Offers</h2>
                        <p class="mt-1 text-sm text-slate-600">Discounts, trial extensions, and custom commercial offers for future Paddle checkout.</p>
                    </div>
                    <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-black text-amber-800">Paddle integration pending</span>
                </div>

                <div v-if="promotions.data.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Applies To</th>
                                <th class="px-4 py-3">Discount</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Starts</th>
                                <th class="px-4 py-3">Ends</th>
                                <th class="px-4 py-3">Redemptions</th>
                                <th class="px-4 py-3">Paddle Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="promotion in promotions.data" :key="promotion.uuid" class="hover:bg-slate-50/70">
                                <td class="px-4 py-4 font-black">{{ promotion.code }}</td>
                                <td class="px-4 py-4">
                                    <div class="font-bold">{{ promotion.title }}</div>
                                    <div class="mt-1 max-w-xs truncate text-xs text-slate-500">{{ promotion.description || 'No description' }}</div>
                                </td>
                                <td class="px-4 py-4 capitalize">{{ promotion.applies_to_plan }}</td>
                                <td class="px-4 py-4">{{ promotion.discount_type }} {{ promotion.discount_value || '' }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-black capitalize text-slate-700">{{ promotion.status }}</span>
                                </td>
                                <td class="px-4 py-4">{{ promotion.starts_at || '-' }}</td>
                                <td class="px-4 py-4">{{ promotion.ends_at || '-' }}</td>
                                <td class="px-4 py-4">{{ promotion.redemption_count }}<span v-if="promotion.max_redemptions"> / {{ promotion.max_redemptions }}</span></td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-black text-amber-800">{{ promotion.paddle_coupon_id || 'Pending' }}</span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button class="rounded-md border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50" type="button">View Redemptions</button>
                                        <button class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-black text-amber-800" type="button" @click="postAction(promotion.routes.pause)">Pause</button>
                                        <button class="rounded-md border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50" type="button" @click="postAction(promotion.routes.archive)">Archive</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center">
                    <h2 class="font-black">No promotions yet</h2>
                    <p class="mt-2 text-sm text-slate-600">Create a draft offer now and connect it to Paddle later.</p>
                </div>
            </div>

            <form class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="font-black text-slate-950">Create Promotion</h2>
                <p class="mt-1 text-sm text-slate-600">This stores a local offer only. It does not create a Paddle coupon.</p>

                <div class="mt-5 grid gap-4">
                    <label class="block text-sm font-bold">Code
                        <input v-model="form.code" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" placeholder="PRO-LAUNCH-20" />
                        <span v-if="form.errors.code" class="text-xs text-red-700">{{ form.errors.code }}</span>
                    </label>
                    <label class="block text-sm font-bold">Title
                        <input v-model="form.title" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" placeholder="Pro launch offer" />
                        <span v-if="form.errors.title" class="text-xs text-red-700">{{ form.errors.title }}</span>
                    </label>
                    <label class="block text-sm font-bold">Description
                        <textarea v-model="form.description" class="mt-2 min-h-20 w-full rounded-md border border-slate-200 px-3 py-2" />
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm font-bold">Discount Type
                            <select v-model="form.discount_type" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2">
                                <option v-for="(label, value) in discountTypes" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </label>
                        <label class="block text-sm font-bold">Value
                            <input v-model="form.discount_value" type="number" min="0" step="0.01" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                        </label>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm font-bold">Applies To
                            <select v-model="form.applies_to_plan" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2">
                                <option v-for="(label, value) in plans" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </label>
                        <label class="block text-sm font-bold">Status
                            <select v-model="form.status" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2">
                                <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </label>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm font-bold">Starts
                            <input v-model="form.starts_at" type="date" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                        </label>
                        <label class="block text-sm font-bold">Ends
                            <input v-model="form.ends_at" type="date" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                        </label>
                    </div>
                    <label class="block text-sm font-bold">Max Redemptions
                        <input v-model="form.max_redemptions" type="number" min="1" class="mt-2 w-full rounded-md border border-slate-200 px-3 py-2" />
                    </label>
                    <label class="block text-sm font-bold">Notes
                        <textarea v-model="form.notes" class="mt-2 min-h-20 w-full rounded-md border border-slate-200 px-3 py-2" placeholder="Internal notes..." />
                    </label>
                </div>

                <button class="mt-5 w-full rounded-md bg-slate-950 px-4 py-2.5 text-sm font-black text-white hover:bg-slate-800" :disabled="form.processing">
                    Create
                </button>
            </form>
        </section>

        <nav v-if="promotions.links?.length > 3" class="mt-6 flex flex-wrap gap-2">
            <Link
                v-for="link in promotions.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-md border px-3 py-2 text-sm font-bold"
                :class="link.active ? 'border-slate-950 bg-slate-950 text-white' : link.url ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' : 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-300'"
                v-html="link.label"
            />
        </nav>
    </LibraryAdminShell>
</template>
