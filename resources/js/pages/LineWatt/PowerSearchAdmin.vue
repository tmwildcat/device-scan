<script setup lang="ts">
import LibraryAdminShell from '@/components/linewatt/admin/LibraryAdminShell.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    categories: any[];
    recentAssignments: any[];
}>();

const page = usePage<{ flash?: { success?: string | null } }>();

const categoryForm = useForm({ name: '', scope: 'all', sort_order: 0 });
const optionForm = useForm({
    power_search_category_id: '',
    label: '',
    scope: 'all',
    country: '',
    region: '',
    subtype: '',
    notes: '',
    reference_source: '',
});
const assignmentForm = useForm({
    compiled_device_record_id: '',
    power_search_option_ids: [] as number[],
    notes: '',
});

function submitCategory(): void {
    categoryForm.post('/admin/library/power-search/categories', {
        preserveScroll: true,
        onSuccess: () => categoryForm.reset(),
    });
}

function submitOption(): void {
    optionForm.post('/admin/library/power-search/options', {
        preserveScroll: true,
        onSuccess: () => optionForm.reset(),
    });
}

function submitAssignment(): void {
    assignmentForm.post('/admin/library/power-search/assignments', {
        preserveScroll: true,
        onSuccess: () => assignmentForm.reset(),
    });
}

function allOptions(): any[] {
    return props.categories.flatMap((category) => category.options || []);
}
</script>

<template>
    <Head title="Power Search Taxonomy" />

    <LibraryAdminShell
        title="Power Search Taxonomy"
        subtitle="Curate guided search categories, options and Engineering Record tag assignments."
        :breadcrumbs="[
            { label: 'Dashboard', href: '/admin/library' },
            { label: 'Library Management' },
            { label: 'Power Search Taxonomy' },
        ]"
    >

            <div v-if="page.props.flash?.success" class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ page.props.flash.success }}
            </div>

            <section class="mt-8 grid gap-6 lg:grid-cols-3">
                <form class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submitCategory">
                    <h2 class="font-black">Create Category</h2>
                    <input v-model="categoryForm.name" class="mt-4 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Category name" />
                    <select v-model="categoryForm.scope" class="mt-3 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="all">All</option>
                        <option value="module">Module</option>
                        <option value="inverter">Inverter</option>
                    </select>
                    <button class="mt-4 rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white" type="submit">Create</button>
                </form>

                <form class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submitOption">
                    <h2 class="font-black">Create Option</h2>
                    <select v-model="optionForm.power_search_category_id" class="mt-4 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Choose category</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <input v-model="optionForm.label" class="mt-3 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Option label" />
                    <select v-model="optionForm.scope" class="mt-3 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="all">All</option>
                        <option value="module">Module</option>
                        <option value="inverter">Inverter</option>
                    </select>
                    <input v-model="optionForm.country" class="mt-3 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Country optional" />
                    <input v-model="optionForm.region" class="mt-3 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Region optional" />
                    <textarea v-model="optionForm.notes" class="mt-3 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Notes/reference"></textarea>
                    <button class="mt-4 rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white" type="submit">Create</button>
                </form>

                <form class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submitAssignment">
                    <h2 class="font-black">Assign Tags</h2>
                    <input v-model="assignmentForm.compiled_device_record_id" class="mt-4 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Engineering Record ID" />
                    <div class="mt-3 max-h-64 space-y-2 overflow-auto rounded-md border border-slate-200 p-3">
                        <label v-for="option in allOptions()" :key="option.id" class="flex items-center gap-2 text-sm">
                            <input v-model="assignmentForm.power_search_option_ids" :value="option.id" type="checkbox" />
                            <span>{{ option.label }}</span>
                        </label>
                    </div>
                    <textarea v-model="assignmentForm.notes" class="mt-3 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Assignment notes"></textarea>
                    <button class="mt-4 rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white" type="submit">Assign</button>
                </form>
            </section>

            <section class="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-black">Active Taxonomy</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div v-for="category in categories" :key="category.id" class="rounded-lg border border-slate-200 p-4">
                        <div class="font-black">{{ category.name }}</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span v-for="option in category.options" :key="option.id" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                                {{ option.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-black">Recent Tagged Records</h2>
                <div class="mt-4 divide-y divide-slate-100">
                    <div v-for="record in recentAssignments" :key="record.id" class="py-3 text-sm">
                        <div class="font-bold">{{ record.display_name || 'Engineering Record' }} <span class="text-slate-500">#{{ record.id }}</span></div>
                        <div class="mt-1 text-slate-600">{{ record.manufacturer || 'Unknown manufacturer' }}</div>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <span v-for="tag in record.tags" :key="tag" class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-800">{{ tag }}</span>
                        </div>
                    </div>
                    <p v-if="recentAssignments.length === 0" class="py-4 text-sm text-slate-600">No curated tag assignments yet.</p>
                </div>
            </section>
    </LibraryAdminShell>
</template>
