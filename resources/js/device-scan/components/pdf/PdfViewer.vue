<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

const props = defineProps<{
    src: string;
}>();

const container = ref<HTMLDivElement | null>(null);
let renderToken = 0;

async function renderPdf(): Promise<void> {
    const currentToken = ++renderToken;

    await nextTick();

    if (!container.value || !props.src) {
        return;
    }

    container.value.innerHTML = '';

    const loadingTask = pdfjsLib.getDocument({
        url: props.src,
        withCredentials: true,
    });

    const pdf = await loadingTask.promise;

    for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
        if (currentToken !== renderToken || !container.value) {
            return;
        }

        const page = await pdf.getPage(pageNumber);
        const viewport = page.getViewport({ scale: 1.35 });

        const pageWrap = document.createElement('div');
        pageWrap.className = 'mb-6 flex justify-start';

        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        if (!context) {
            continue;
        }

        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        canvas.style.width = `${Math.floor(viewport.width)}px`;
        canvas.style.height = `${Math.floor(viewport.height)}px`;
        canvas.className = 'rounded-xl border border-slate-300 bg-white shadow';

        pageWrap.appendChild(canvas);
        container.value.appendChild(pageWrap);

        await page.render({
            canvas,
            canvasContext: context,
            viewport,
        }).promise;
    }
}

onMounted(renderPdf);

watch(() => props.src, renderPdf);

onBeforeUnmount(() => {
    renderToken++;
});
</script>

<template>
    <div
        ref="container"
        class="h-full min-h-[600px] w-full overflow-auto rounded-xl bg-slate-100 p-2 dark:bg-slate-950"
    />
</template>