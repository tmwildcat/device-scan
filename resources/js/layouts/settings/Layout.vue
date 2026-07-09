<script setup lang="ts">
import WorkspaceNavigation from '@/components/linewatt/WorkspaceNavigation.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const settingsNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Security',
        href: editSecurity(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-950">
        <WorkspaceNavigation />

        <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <Heading
                    title="Settings"
                    description="Manage your LineWatt Library profile, security and appearance preferences."
                />

                <div class="mt-8 grid gap-8 lg:grid-cols-[220px_1fr]">
                    <aside>
                        <nav class="grid gap-2" aria-label="Settings">
                            <Button
                                v-for="item in settingsNavItems"
                                :key="toUrl(item.href)"
                                variant="ghost"
                                :class="[
                                    'justify-start rounded-md',
                                    { 'bg-slate-100 text-slate-950': isCurrentOrParentUrl(item.href) },
                                ]"
                                as-child
                            >
                                <Link :href="item.href">
                                    {{ item.title }}
                                </Link>
                            </Button>
                        </nav>
                    </aside>

                    <div class="max-w-2xl">
                        <section class="space-y-12">
                            <slot />
                        </section>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
