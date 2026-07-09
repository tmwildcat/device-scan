<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    FileDown,
    FolderGit2,
    FolderKanban,
    Home,
    Library,
    Search,
    Settings,
    Upload,
} from 'lucide-vue-next';
import { computed } from 'vue';

import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => (page.props as any).auth?.user ?? null);
const entitlements = computed<string[]>(() => user.value?.entitlements ?? ['library.search', 'library.view_record']);
const workspaces = computed(() => user.value?.workspaces ?? {});

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Home',
            href: '/',
            icon: Home,
        },
    ];

    if (entitlements.value.includes('library.search')) {
        items.push({
            title: 'Search',
            href: '/search',
            icon: Search,
        });
    }

    if (workspaces.value.my_library === true) {
        items.push({
            title: 'My Private Datasets',
            href: '/my-library',
            icon: Library,
        });
    }

    if (workspaces.value.central === true) {
        items.push({
            title: 'Library Admin',
            href: '/admin/library',
            icon: FolderKanban,
        });
    }

    if (workspaces.value.partner === true) {
        items.push({
            title: 'Manufacturer Admin',
            href: '/admin/manufacturer',
            icon: Building2,
        });
    }

    if (workspaces.value.platform === true) {
        items.push({
            title: 'Platform Admin',
            href: '/admin/platform',
            icon: FolderKanban,
        });
    }

    if (entitlements.value.includes('library.private_upload') || entitlements.value.includes('central.manage') || entitlements.value.includes('partner.manage_products')) {
        items.push({
            title: 'Upload',
            href: user.value?.workspaces?.central ? '/admin/library' : user.value?.workspaces?.partner ? '/admin/manufacturer' : '/my-library',
            icon: Upload,
        });
    }

    if (entitlements.value.includes('library.export')) {
        items.push({
            title: 'Exports',
            href: user.value?.workspaces?.central ? '/admin/library' : user.value?.workspaces?.partner ? '/admin/manufacturer' : '/my-library',
            icon: FileDown,
        });
    }

    items.push({
        title: 'Settings',
        href: '/settings/profile',
        icon: Settings,
    });

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'LineWatt Library',
        href: '/',
        icon: FolderGit2,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <slot />
</template>
