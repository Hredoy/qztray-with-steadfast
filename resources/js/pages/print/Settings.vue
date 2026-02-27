<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";
import { dashboard } from "@/routes";

const props = defineProps<{
    settings?: {
        id?: number;
        footer_text?: string | null;
        enable_printing?: boolean;
        print_thank_you?: boolean;
        print_logo?: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: dashboard().url },
    { title: "Printer Settings", href: dashboard().url },
];

const form = useForm({
    footer_text: props.settings?.footer_text ?? "",
    enable_printing: props.settings?.enable_printing ?? true,
    print_thank_you: props.settings?.print_thank_you ?? true,
    print_logo: props.settings?.print_logo ?? false,
});

function submit() {
    form.put(route("printer-settings.update"), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Printer Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="md:col-span-3 rounded-xl border  p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Printer Settings</h2>
                        <button
                            class="rounded-lg bg-black px-4 py-2 text-white disabled:opacity-50"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            Save
                        </button>
                    </div>

                    <!-- Switches -->
                    <div class="grid gap-3 md:grid-cols-3">
                        <label class="flex items-center justify-between rounded-lg border p-3">
                            <div>
                                <div class="font-medium">Enable Printing</div>
                                <div class="text-sm text-gray-500">Turn printing on/off globally</div>
                            </div>
                            <input type="checkbox" v-model="form.enable_printing" class="h-5 w-5" />
                        </label>

                        <label class="flex items-center justify-between rounded-lg border p-3">
                            <div>
                                <div class="font-medium">Print Thank You</div>
                                <div class="text-sm text-gray-500">Append “Thank you” section</div>
                            </div>
                            <input type="checkbox" v-model="form.print_thank_you" class="h-5 w-5" />
                        </label>

                        <label class="flex items-center justify-between rounded-lg border p-3">
                            <div>
                                <div class="font-medium">Print Logo</div>
                                <div class="text-sm text-gray-500">Print logo on receipt header</div>
                            </div>
                            <input type="checkbox" v-model="form.print_logo" class="h-5 w-5" />
                        </label>
                    </div>

                    <!-- Textarea -->
                    <div class="mt-4">
                        <label class="mb-2 block text-sm font-medium text-gray-700">
                            Footer Text (optional)
                        </label>
                        <textarea
                            v-model="form.footer_text"
                            rows="4"
                            class="w-full rounded-lg border p-3 outline-none focus:ring"
                            placeholder="Example: Thank you for shopping with us!"
                        />
                        <div v-if="form.errors.footer_text" class="mt-1 text-sm text-red-600">
                            {{ form.errors.footer_text }}
                        </div>
                    </div>

                    <div v-if="form.hasErrors" class="mt-3 text-sm text-red-600">
                        Please fix the errors and try again.
                    </div>

                    <div v-if="form.recentlySuccessful" class="mt-3 text-sm text-green-700">
                        Saved successfully.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
