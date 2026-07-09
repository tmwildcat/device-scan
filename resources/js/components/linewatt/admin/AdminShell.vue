<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { logout } from '@/routes';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown, ChevronRight, HelpCircle, LogOut, Search, Settings } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useLineWattI18n } from '@/lib/linewatt-i18n';

type NavItem = {
    label: string;
    href: string;
    children?: Array<{ label: string; href: string }>;
};

const props = defineProps<{
    workspaceTitle: string;
    contextName?: string | null;
    roleLabel?: string | null;
    planLabel?: string | null;
    planCode?: string | null;
    navItems: NavItem[];
    homeHref?: string;
    hideWorkspaceIdentity?: boolean;
    workspaceButtonLabel?: string | null;
    searchHref?: string | null;
    breadcrumbs?: Array<{ label: string; href?: string | null }>;
    title: string;
    subtitle?: string | null;
    primaryAction?: { label: string; href: string } | null;
    secondaryActions?: Array<{ label: string; href: string }>;
    settingsHref?: string | null;
    headerBadges?: Array<{ label: string; tone?: 'emerald' | 'amber' | 'sky' | 'slate' }>;
    upgradeHref?: string | null;
    upgradeMessage?: string | null;
    includedEntitlements?: string[];
    enterpriseEntitlements?: string[];
}>();

const page = usePage();
const user = computed(() => (page.props as any).auth?.user ?? null);
const notifications = computed(() => (page.props as any).notifications ?? { unread_count: 0, recent: [] });
const { dir, t } = useLineWattI18n();
const isLibraryPublisher = computed(() => user.value?.role === 'library_publisher');
const sidebarOpen = ref(false);
const subscriptionOpen = ref(false);
const currentUrl = computed(() => String(page.url || '').split('#')[0]);
const expandedGroups = ref<Record<string, boolean>>({});

const planTone = computed(() => {
    if (props.planCode === 'enterprise') return 'border-violet-200 bg-violet-50 text-violet-800';
    if (props.planCode === 'pro') return 'border-sky-200 bg-sky-50 text-sky-800';
    return 'border-emerald-200 bg-emerald-50 text-emerald-800';
});

function headerBadgeTone(tone?: string): string {
    if (tone === 'emerald') return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    if (tone === 'amber') return 'border-amber-200 bg-amber-50 text-amber-800';
    if (tone === 'sky') return 'border-sky-200 bg-sky-50 text-sky-800';

    return 'border-slate-200 bg-slate-100 text-slate-700';
}

function handleLogout(): void {
    router.flushAll();
}

function cleanUrl(value: string): string {
    return value.split('#')[0];
}

function isActiveHref(href: string): boolean {
    const current = cleanUrl(currentUrl.value);
    const target = cleanUrl(href);

    if (current === target) {
        return true;
    }

    const currentPath = current.split('?')[0];
    const targetPath = target.split('?')[0];

    return targetPath !== '/' && currentPath === targetPath;
}

function itemIsActive(item: NavItem): boolean {
    return isActiveHref(item.href) || Boolean(item.children?.some((child) => isActiveHref(child.href)));
}

function syncExpandedGroups(): void {
    const next = { ...expandedGroups.value };

    props.navItems.forEach((item) => {
        if (item.children?.length && itemIsActive(item)) {
            next[item.label] = true;
        }
    });

    expandedGroups.value = next;
}

function toggleGroup(label: string): void {
    expandedGroups.value = {
        ...expandedGroups.value,
        [label]: !expandedGroups.value[label],
    };
}

watch(currentUrl, syncExpandedGroups, { immediate: true });
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-950" :dir="dir">
        <header class="fixed inset-x-0 top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="relative flex h-14 items-center justify-between gap-4 px-4 lg:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button class="rounded-md border border-slate-200 px-2 py-1 text-sm font-black lg:hidden" type="button" @click="sidebarOpen = !sidebarOpen">{{ t('admin.menu') }}</button>
                    <Link :href="homeHref || '/dashboard'" class="flex items-center gap-3">
                        <AppLogo />
                    </Link>
                    <div v-if="!hideWorkspaceIdentity" class="hidden h-6 w-px bg-slate-200 sm:block" />
                    <div v-if="!hideWorkspaceIdentity" class="min-w-0">
                        <div class="truncate text-sm font-black">{{ workspaceTitle }}</div>
                        <div v-if="contextName" class="truncate text-xs font-bold text-slate-500">{{ contextName }}</div>
                    </div>
                    <div v-if="headerBadges?.length" class="hidden items-center gap-2 xl:flex">
                        <span
                            v-for="badge in headerBadges"
                            :key="badge.label"
                            class="rounded-full border px-2.5 py-1 text-xs font-black"
                            :class="headerBadgeTone(badge.tone)"
                        >
                            {{ badge.label }}
                        </span>
                    </div>
                </div>

                <nav
                    v-if="workspaceButtonLabel || searchHref"
                    class="absolute left-1/2 hidden -translate-x-1/2 items-center gap-2 lg:flex"
                    aria-label="Admin header navigation"
                >
                    <Link
                        v-if="workspaceButtonLabel"
                        :href="homeHref || '/dashboard'"
                        class="inline-flex h-10 items-center gap-2 rounded-md px-3 text-sm font-black text-slate-700 hover:bg-slate-100 hover:text-slate-950"
                    >
                        {{ workspaceButtonLabel }}
                    </Link>
                    <Link
                        v-if="searchHref"
                        :href="searchHref"
                        class="inline-flex h-10 items-center gap-2 rounded-md px-3 text-sm font-black text-slate-700 hover:bg-slate-100 hover:text-slate-950"
                    >
                        <Search class="size-4" />
                        {{ t('nav.search') }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="isLibraryPublisher"
                        href="/search"
                        class="hidden rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-black text-slate-700 hover:bg-slate-50 sm:inline-flex"
                    >
                        {{ t('nav.search') }}
                    </Link>
                    <Link
                        v-if="isLibraryPublisher"
                        href="/publisher/uploads/new"
                        class="hidden rounded-md bg-slate-950 px-3 py-2 text-sm font-black text-white hover:bg-slate-800 sm:inline-flex"
                    >
                        {{ t('admin.uploadDatasheet') }}
                    </Link>
                    <div class="relative" @mouseleave="subscriptionOpen = false">
                        <button
                            v-if="planLabel"
                            class="rounded-full border px-3 py-1 text-xs font-black"
                            :class="planTone"
                            type="button"
                            @click="subscriptionOpen = !subscriptionOpen"
                            @mouseenter="subscriptionOpen = true"
                        >
                            {{ planLabel }}
                        </button>
                        <div v-if="subscriptionOpen && planLabel" class="absolute right-0 mt-2 w-80 rounded-lg border border-slate-200 bg-white p-4 text-sm shadow-xl">
                            <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ t('admin.currentPlan') }}</div>
                            <div class="mt-1 text-xl font-black">{{ planLabel }}</div>
                            <div v-if="includedEntitlements?.length" class="mt-4">
                                <div class="font-black">{{ t('admin.included') }}</div>
                                <ul class="mt-2 space-y-1 text-slate-600">
                                    <li v-for="item in includedEntitlements" :key="item">✓ {{ item }}</li>
                                </ul>
                            </div>
                            <div v-if="enterpriseEntitlements?.length" class="mt-4 rounded-md bg-violet-50 p-3 text-violet-900">
                                <div class="font-black">{{ t('admin.enterpriseAdds') }}</div>
                                <ul class="mt-2 space-y-1">
                                    <li v-for="item in enterpriseEntitlements" :key="item">✓ {{ item }}</li>
                                </ul>
                            </div>
                            <Link v-if="upgradeHref" :href="upgradeHref" class="mt-4 inline-flex font-black text-violet-700">{{ t('admin.upgrade') }} →</Link>
                            <p v-else-if="upgradeMessage" class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-800">{{ upgradeMessage }}</p>
                        </div>
                    </div>
                    <button class="rounded-md p-2 text-slate-500 hover:bg-slate-100" title="Help placeholder" type="button"><HelpCircle class="size-4" /></button>
                    <Link
                        v-if="settingsHref"
                        :href="settingsHref"
                        class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-black text-slate-700 hover:bg-slate-50"
                    >
                        <Settings class="size-4" />
                        <span class="hidden xl:inline">Settings</span>
                    </Link>
                    <span v-if="roleLabel" class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 md:inline-flex">{{ roleLabel }}</span>
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
                    <Link :href="logout()" @click="handleLogout" as="button" class="inline-flex items-center gap-2 rounded-md bg-slate-950 px-3 py-2 text-sm font-bold text-white hover:bg-slate-800">
                        <LogOut class="size-4" />
                        <span class="hidden sm:inline">{{ t('nav.logout') }}</span>
                    </Link>
                </div>
            </div>
        </header>

        <aside
            class="fixed inset-y-0 left-0 z-30 w-72 border-r border-slate-200 bg-white pt-14 shadow-sm transition-transform lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <nav class="h-full overflow-y-auto p-4">
                <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">{{ t('admin.navigation') }}</div>
                <div class="mt-3 space-y-1">
                    <div v-for="item in navItems" :key="item.label">
                        <button
                            v-if="item.children?.length"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-black hover:bg-slate-50"
                            :class="itemIsActive(item) ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700'"
                            type="button"
                            :aria-expanded="expandedGroups[item.label] ? 'true' : 'false'"
                            @click="toggleGroup(item.label)"
                        >
                            <span>{{ item.label }}</span>
                            <ChevronDown class="size-3 text-slate-400 transition-transform" :class="expandedGroups[item.label] ? 'rotate-180' : ''" />
                        </button>
                        <Link
                            v-else
                            :href="item.href"
                            class="flex items-center justify-between rounded-md px-3 py-2 text-sm font-black hover:bg-slate-50"
                            :class="itemIsActive(item) ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700'"
                            @click="sidebarOpen = false"
                        >
                            <span>{{ item.label }}</span>
                            <ChevronRight class="size-3 text-slate-400" />
                        </Link>
                        <div v-if="item.children?.length && expandedGroups[item.label]" class="ml-3 mt-1 border-l border-slate-100 pl-3">
                            <Link
                                v-for="child in item.children"
                                :key="child.label"
                                :href="child.href"
                                class="block rounded-md px-3 py-1.5 text-sm font-bold hover:bg-slate-50"
                                :class="isActiveHref(child.href) ? 'text-emerald-800' : 'text-slate-500 hover:text-slate-950'"
                                @click="sidebarOpen = false"
                            >
                                {{ child.label }}
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>
        </aside>

        <div class="pt-14 lg:pl-72">
            <main class="min-h-[calc(100vh-3.5rem)] px-4 py-5 sm:px-6 lg:px-8">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div v-if="breadcrumbs?.length" class="mb-2 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500">
                            <template v-for="(crumb, index) in breadcrumbs" :key="crumb.label">
                                <Link v-if="crumb.href" :href="crumb.href" class="hover:text-emerald-700">{{ crumb.label }}</Link>
                                <span v-else>{{ crumb.label }}</span>
                                <ChevronRight v-if="index < breadcrumbs.length - 1" class="size-3" />
                            </template>
                        </div>
                        <h1 class="text-3xl font-black tracking-tight">{{ title }}</h1>
                        <p v-if="subtitle" class="mt-1 max-w-4xl text-sm leading-6 text-slate-600">{{ subtitle }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="action in secondaryActions"
                            :key="action.href"
                            :href="action.href"
                            class="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 hover:bg-slate-50"
                        >
                            {{ action.label }}
                        </Link>
                        <Link v-if="primaryAction" :href="primaryAction.href" class="rounded-md bg-slate-950 px-4 py-2 text-sm font-black text-white hover:bg-slate-800">
                            {{ primaryAction.label }}
                        </Link>
                    </div>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
