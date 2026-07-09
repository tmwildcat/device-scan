<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        initialQuery?: string;
        deviceType?: string;
        compact?: boolean;
    }>(),
    {
        initialQuery: '',
        deviceType: '',
        compact: false,
    },
);

const query = ref(props.initialQuery);
const targetDeviceType = computed(() => props.deviceType);

watch(
    () => props.initialQuery,
    (value) => {
        query.value = value;
    },
);

function submit(): void {
    const tab = targetDeviceType.value === 'module' ? 'modules' : targetDeviceType.value === 'inverter' ? 'inverters' : 'all';

    router.get(
        '/search/results',
        {
            q: query.value,
            tab,
            device_type: targetDeviceType.value,
        },
        {
            preserveState: false,
            replace: true,
        },
    );
}
</script>

<template>
    <form
        class="flex w-full flex-col gap-3 rounded-lg border border-slate-200 bg-white p-2 shadow-sm sm:flex-row"
        :class="compact ? 'max-w-3xl' : 'max-w-4xl'"
        @submit.prevent="submit"
    >
        <label class="sr-only" for="library-search">Search</label>
        <div class="flex min-w-0 flex-1 items-center gap-3 px-3">
            <Search class="size-5 shrink-0 text-slate-400" />
            <input
                id="library-search"
                v-model="query"
                class="h-12 min-w-0 flex-1 border-0 bg-transparent text-base text-slate-950 outline-none placeholder:text-slate-400 focus:ring-0"
                placeholder="Search manufacturer, model, model series, power, technology..."
                type="search"
            />
        </div>
        <button
            class="h-12 rounded-md bg-slate-950 px-5 text-sm font-bold text-white hover:bg-slate-800"
            type="submit"
        >
            Search
        </button>
    </form>
</template>
