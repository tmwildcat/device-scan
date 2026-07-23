<script setup lang="ts">
import PublicSiteFooter from '@/components/linewatt/PublicSiteFooter.vue';
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const publicLegalDocuments = computed<Array<{ title: string; href: string }>>(
    () => (page.props as any).publicLegalDocuments ?? [],
);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50 text-slate-950">
        <WorkspaceNavigation />
        <main class="flex-1">
            <slot />
        </main>
        <PublicSiteFooter :legal-documents="publicLegalDocuments" />
    </div>
</template>
