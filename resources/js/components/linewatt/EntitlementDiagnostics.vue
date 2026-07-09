<script setup lang="ts">
import { useLineWattI18n } from '@/lib/linewatt-i18n';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => (page.props as any).auth?.user ?? null);
const { t } = useLineWattI18n();
</script>

<template>
    <details
        v-if="user"
        class="rounded-lg border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-600 shadow-sm"
    >
        <summary class="cursor-pointer select-none font-bold text-slate-900">
            {{ t('diagnostics.entitlementDiagnostics') }}
        </summary>
        <dl class="mt-4 grid gap-3 sm:grid-cols-3">
            <div>
                <dt class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ t('diagnostics.role') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ user.role_label || user.role }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ t('diagnostics.plan') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ user.plan_code || t('diagnostics.none') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ t('diagnostics.subscription') }}</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ user.subscription_status || t('diagnostics.notManaged') }}</dd>
            </div>
        </dl>
        <div class="mt-4">
            <div class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ t('diagnostics.entitlements') }}</div>
            <div class="mt-2 flex flex-wrap gap-2">
                <span
                    v-for="entitlement in user.entitlements || []"
                    :key="entitlement"
                    class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700"
                >
                    {{ entitlement }}
                </span>
            </div>
        </div>
    </details>
</template>
