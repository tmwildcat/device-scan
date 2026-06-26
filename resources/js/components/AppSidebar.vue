<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Boxes,
    ClipboardCheck,
    FileInput,
    FolderGit2,
    GitCompare,
    LayoutDashboard,
    LayoutGrid,
    Library,
    NotebookTabs,
    PackageCheck,
    PlugZap,
    SolarPanel,
} from 'lucide-vue-next';

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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Equipment',
        href: '/library/equipment/modules',
        icon: Boxes,
        children: [
            {
                title: 'PV Modules',
                href: '/library/equipment/modules',
                icon: SolarPanel,
            },
            {
                title: 'String Inverters',
                href: '/library/equipment/string-inverters',
                icon: PlugZap,
            },
            {
                title: 'Central Inverters',
                href: '/library/equipment/central-inverters',
                icon: Boxes,
            },
        ],
    },
    {
        title: 'Import',
        href: '/library/import/module',
        icon: FileInput,
        children: [
            {
                title: 'PV Modules',
                href: '/library/import/module',
                icon: SolarPanel,
            },
            {
                title: 'String Inverters',
                href: '/library/import/string-inverter',
                icon: PlugZap,
            },
            {
                title: 'Central Inverters',
                href: '/library/import/central-inverter',
                icon: Boxes,
            },
        ],
    },
    {
        title: 'Export',
        href: '/library/export',
        icon: PackageCheck,
    },
    {
        title: 'Compare',
        href: '/library/compare',
        icon: GitCompare,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
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