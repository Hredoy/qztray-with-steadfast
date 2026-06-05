<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Purchase Imports', href: route('purchase-imports.index') },
    { title: 'Import Invoice', href: route('purchase-imports.create') },
];

const dragging = ref(false);
const selectedFile = ref<File | null>(null);
const uploading = ref(false);
const error = ref('');

function onDrop(e: DragEvent) {
    dragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file?.type === 'application/pdf') {
        selectedFile.value = file;
        error.value = '';
    } else {
        error.value = 'Please drop a PDF file.';
    }
}

function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        selectedFile.value = file;
        error.value = '';
    }
}

function submit() {
    if (!selectedFile.value) {
        error.value = 'Please select a PDF file.';
        return;
    }
    uploading.value = true;
    error.value = '';

    const form = new FormData();
    form.append('pdf', selectedFile.value);

    router.post(route('purchase-imports.extract'), form, {
        onError: (errors) => {
            error.value = errors.pdf ?? errors.error ?? 'Something went wrong.';
            uploading.value = false;
        },
        onFinish: () => {
            uploading.value = false;
        },
    });
}
</script>

<template>
    <Head title="Import Invoice PDF" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col items-center justify-center p-8">
            <div class="w-full max-w-xl rounded-xl border p-8 shadow-sm">
                <h1 class="mb-2 text-xl font-semibold">Import Purchase Invoice</h1>
                <p class="mb-6 text-sm text-gray-500">
                    Upload a PDF invoice. Gemini AI will extract the product list, variants, quantities, and costs.
                    You can review and edit before confirming.
                </p>

                <!-- Drop zone -->
                <div
                    class="relative flex min-h-48 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed transition"
                    :class="dragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-950' : 'border-gray-300 dark:border-gray-600'"
                    @dragover.prevent="dragging = true"
                    @dragleave="dragging = false"
                    @drop.prevent="onDrop"
                    @click="($refs.fileInput as HTMLInputElement).click()"
                >
                    <input ref="fileInput" type="file" accept="application/pdf" class="hidden" @change="onFileChange" />

                    <svg v-if="!selectedFile" class="mb-3 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 16v-8m0 0-3 3m3-3 3 3M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M4 12V8a2 2 0 012-2h2" />
                    </svg>

                    <div v-if="selectedFile" class="flex flex-col items-center gap-2">
                        <svg class="h-10 w-10 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                        </svg>
                        <span class="text-sm font-medium">{{ selectedFile.name }}</span>
                        <span class="text-xs text-gray-400">{{ (selectedFile.size / 1024).toFixed(1) }} KB</span>
                    </div>

                    <p v-else class="text-sm text-gray-500">Drag & drop PDF here, or click to browse</p>
                </div>

                <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>

                <button
                    class="mt-6 w-full rounded-lg bg-black py-3 text-white font-medium transition hover:bg-gray-800 disabled:opacity-50"
                    :disabled="uploading || !selectedFile"
                    @click="submit"
                >
                    <span v-if="uploading" class="flex items-center justify-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Extracting with Gemini AI…
                    </span>
                    <span v-else>Upload & Extract</span>
                </button>
            </div>
        </div>
    </AppLayout>
</template>
