<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();

const openItems = ref<Record<string, boolean>>({});

const isParentActive = (item: NavItem): boolean => {
    return Boolean(
        isCurrentUrl(item.href) ||
        item.children?.some((child) => isCurrentUrl(child.href)),
    );
};

const isOpen = (item: NavItem): boolean => {
    return openItems.value[item.title] ?? isParentActive(item);
};

const toggleOpen = (item: NavItem): void => {
    openItems.value[item.title] = !isOpen(item);
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>

        <SidebarMenu>
            <SidebarMenuItem
                v-for="item in items"
                :key="item.title"
            >
                <SidebarMenuButton
                    v-if="item.children?.length"
                    type="button"
                    :is-active="isParentActive(item)"
                    :tooltip="item.title"
                    @click="toggleOpen(item)"
                >
                    <component :is="item.icon" />
                    <span>{{ item.title }}</span>

                    <ChevronDown
                        class="ml-auto size-4 opacity-60 transition-transform"
                        :class="{ '-rotate-90': !isOpen(item) }"
                    />
                </SidebarMenuButton>

                <SidebarMenuButton
                    v-else
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>

                <div
                    v-if="item.children?.length && isOpen(item)"
                    class="ml-7 mt-1 space-y-1 border-l border-sidebar-border pl-3"
                >
                    <Link
                        v-for="child in item.children"
                        :key="child.title"
                        :href="child.href"
                        class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        :class="{
                            'bg-sidebar-accent text-sidebar-accent-foreground': isCurrentUrl(child.href),
                        }"
                    >
                        <component
                            v-if="child.icon"
                            :is="child.icon"
                            class="size-4"
                        />

                        <span>{{ child.title }}</span>
                    </Link>
                </div>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>