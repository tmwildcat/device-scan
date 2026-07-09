<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { logout } from '@/routes';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, Building2, FileDown, FolderKanban, GitCompare, Home, Library, LogOut, Search, Settings, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';

const page = usePage();
const user = computed(() => (page.props as any).auth?.user ?? null);
const notifications = computed(() => (page.props as any).notifications ?? { unread_count: 0, recent: [] });
const { t } = useLineWattI18n();
const entitlements = computed<string[]>(() => user.value?.entitlements ?? ['library.search', 'library.view_record']);
const workspaces = computed(() => user.value?.workspaces ?? {});
const workspaceHomeHref = computed(() => {
    if (workspaces.value.platform && user.value?.role === 'super_admin') {
        return '/admin/platform';
    }

    if (workspaces.value.central) {
        return '/admin/library';
    }

    if (workspaces.value.publisher) {
        return '/publisher';
    }

    if (workspaces.value.partner) {
        return '/admin/manufacturer';
    }

    if (workspaces.value.my_library) {
        return '/my-library';
    }

    return '/';
});

const handleLogout = () => {
    router.flushAll();
};

const navItems = [
    { label: () => t('nav.home'), href: '/', icon: Home, visible: () => !user.value, disabled: false },
    { label: () => t('nav.search'), href: '/search', icon: Search, visible: () => entitlements.value.includes('library.search') && workspaces.value.partner !== true && workspaces.value.central !== true, disabled: false },
    { label: () => t('nav.myLibrary'), href: '/my-library', icon: Library, visible: () => workspaces.value.my_library === true, disabled: false },
    { label: 'Publisher', href: '/publisher', icon: FolderKanban, visible: () => workspaces.value.publisher === true, disabled: false },
    { label: () => t('nav.compare'), href: '/compare/select', icon: GitCompare, visible: () => entitlements.value.includes('library.compare') && workspaces.value.partner !== true && workspaces.value.central !== true, disabled: false },
    { label: () => t('nav.exports'), href: '#', icon: FileDown, visible: () => entitlements.value.includes('library.export') && workspaces.value.partner !== true && workspaces.value.central !== true, disabled: true },
    { label: 'Library Admin', href: '/admin/library', icon: FolderKanban, visible: () => workspaces.value.central === true, disabled: false },
    { label: () => t('nav.manufacturerAdmin'), href: '/admin/manufacturer', icon: Building2, visible: () => workspaces.value.partner === true, disabled: false },
    { label: 'Platform Admin', href: '/admin/platform', icon: ShieldCheck, visible: () => workspaces.value.platform === true, disabled: false },
    { label: () => t('nav.settings'), href: '/settings/profile', icon: Settings, visible: () => Boolean(user.value), disabled: false },
];
const enabledNavItems = computed(() => navItems.filter((navItem) => navItem.visible() && !navItem.disabled));
const disabledNavItems = computed(() => navItems.filter((navItem) => navItem.visible() && navItem.disabled));
</script>

<template>
    <header class="border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto grid min-h-16 max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 sm:px-6 lg:px-8">
            <Link :href="workspaceHomeHref" class="flex min-w-0 items-center gap-3 justify-self-start">
                <AppLogo />
            </Link>

            <nav class="hidden items-center justify-center gap-1 lg:flex">
                <Link
                    v-for="item in enabledNavItems"
                    :key="item.href"
                    :href="item.href"
                    class="inline-flex h-10 items-center gap-2 rounded-md px-3 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950"
                >
                    <component :is="item.icon" class="size-4" />
                    <span>{{ typeof item.label === 'function' ? item.label() : item.label }}</span>
                </Link>
                <span
                    v-for="item in disabledNavItems"
                    :key="typeof item.label === 'function' ? item.label() : item.label"
                    class="inline-flex h-10 cursor-not-allowed items-center gap-2 rounded-md px-3 text-sm font-medium text-slate-400"
                    title="Coming in the next milestone."
                >
                    <component :is="item.icon" class="size-4" />
                    <span>{{ typeof item.label === 'function' ? item.label() : item.label }}</span>
                </span>
            </nav>

            <div class="flex items-center justify-self-end gap-2">
                <details v-if="user" class="relative">
                    <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                        <Bell class="size-4" />
                        <span
                            v-if="notifications.unread_count"
                            class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-black text-white"
                        >
                            {{ notifications.unread_count > 9 ? '9+' : notifications.unread_count }}
                        </span>
                    </summary>
                    <div class="absolute right-0 z-50 mt-2 w-80 rounded-lg border border-slate-200 bg-white p-3 shadow-xl">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-sm font-black text-slate-950">{{ t('nav.notifications') }}</h2>
                            <Link
                                v-if="notifications.unread_count"
                                href="/notifications/read-all"
                                method="post"
                                as="button"
                                class="text-xs font-bold text-emerald-700 hover:text-emerald-800"
                            >
                                {{ t('nav.markAllRead') }}
                            </Link>
                        </div>
                        <div class="mt-3 space-y-2">
                            <div v-if="!notifications.recent.length" class="rounded-md bg-slate-50 p-3 text-sm text-slate-500">
                                {{ t('nav.noNotifications') }}
                            </div>
                            <article
                                v-for="notification in notifications.recent"
                                :key="notification.uuid"
                                class="rounded-md border border-slate-100 p-3"
                                :class="notification.read_at ? 'bg-white' : 'bg-emerald-50'"
                            >
                                <Link
                                    :href="notification.action_url || '#'"
                                    class="block text-sm font-black text-slate-950 hover:text-emerald-800"
                                >
                                    {{ notification.title }}
                                </Link>
                                <p v-if="notification.body" class="mt-1 line-clamp-2 text-xs text-slate-600">{{ notification.body }}</p>
                                <Link
                                    v-if="!notification.read_at"
                                    :href="`/notifications/${notification.id}/read`"
                                    method="post"
                                    as="button"
                                    class="mt-2 text-xs font-bold text-slate-500 hover:text-slate-800"
                                >
                                    {{ t('nav.markRead') }}
                                </Link>
                            </article>
                        </div>
                    </div>
                </details>
                <Link
                    v-if="!user"
                    href="/login"
                    class="rounded-md px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-950"
                >
                    Log in
                </Link>
                <Link
                    v-if="!user"
                    href="/register"
                    class="rounded-md bg-slate-950 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Join
                </Link>
                <Link
                    v-if="user"
                    :href="logout()"
                    @click="handleLogout"
                    as="button"
                    class="inline-flex items-center gap-2 rounded-md bg-slate-950 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                    data-test="linewatt-logout-button"
                >
                    <LogOut class="size-4" />
                    {{ t('nav.logout') }}
                </Link>
            </div>
        </div>
    </header>
</template>
