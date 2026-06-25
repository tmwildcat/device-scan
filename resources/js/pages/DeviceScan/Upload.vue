<script setup lang="ts">
import DeviceScanLayout from '@/layouts/DeviceScan/DeviceScanLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm<{
    device_type: 'module' | 'inverter' | '';
    datasheet: File | null;
}>({
    device_type: '',
    datasheet: null,
});

function handleFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.datasheet = input.files?.[0] ?? null;
}
</script>

<template>
    <Head title="Upload Datasheet" />

    <DeviceScanLayout>
        <div class="mx-auto max-w-4xl px-6 py-10">
            <div class="mb-8">
                <p class="text-sm font-semibold text-emerald-600">DeviceScan</p>
                <h1 class="mt-1 text-3xl font-bold">Upload Datasheet</h1>
                <p class="mt-2 text-slate-500">
                    Upload a PV module or inverter datasheet to begin extraction.
                </p>
            </div>

            <div class="rounded-2xl border bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid gap-6">
                    <div>
                        <label class="text-sm font-medium">Device Type</label>
                        <select
                            v-model="form.device_type"
                            class="mt-2 w-full rounded-lg border px-3 py-2 dark:border-slate-700 dark:bg-slate-950"
                        >
                            <option value="">Select device type</option>
                            <option value="module">PV Module</option>
                            <option value="inverter">PV Inverter</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium">Datasheet PDF</label>
                        <input
                            type="file"
                            accept="application/pdf"
                            @change="handleFile"
                            class="mt-2 block w-full rounded-lg border px-3 py-2 dark:border-slate-700 dark:bg-slate-950"
                        />
                    </div>

                    <div class="flex justify-end gap-3">
                        <Link
                            href="/dashboard"
                            class="rounded-xl border px-5 py-2 text-sm font-semibold"
                        >
                            Cancel
                        </Link>

                        <button
                            type="button"
                            :disabled="!form.device_type || !form.datasheet || form.processing"
                            class="rounded-xl bg-emerald-500 px-5 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                            @click="form.post('/device-scan/upload', { forceFormData: true })"
                        >
                            {{ form.processing ? 'Uploading...' : 'Continue to Review' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DeviceScanLayout>
</template>