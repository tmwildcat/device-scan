<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M13.7 2.2a1 1 0 0 1 .82 1.13L13.4 9H18a1 1 0 0 1 .78 1.63l-8.5 10.5a1 1 0 0 1-1.75-.82L9.6 14H5a1 1 0 0 1-.78-1.63l8.5-9.8a1 1 0 0 1 .98-.37Z"
        />
    </svg>
</template>
