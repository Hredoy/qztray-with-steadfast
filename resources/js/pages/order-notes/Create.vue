<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import OrderNoteForm from "./partials/OrderNoteForm.vue";
import type { BreadcrumbItem } from "@/types";

defineProps<{ customers: any[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Order Notes", href: route("order-notes.index") },
    { title: "Create", href: route("order-notes.create") },
];

const form = useForm({
    customer_id: "",
    name: "",
    phone: "",
    address: "",
    product_list: "",
    paid: 0,
    due: 0,
});

function submit() {
    form.post(route("order-notes.store"));
}
</script>

<template>
    <Head title="Create Order Note" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Create Order Note</h2>
                <Link :href="route('order-notes.index')" class="underline">Back</Link>
            </div>
            <div class="rounded-xl border p-4">
                <OrderNoteForm :form="form" :customers="customers" />
                <div class="mt-4 flex justify-end">
                    <button class="rounded-lg bg-black px-4 py-2 text-white disabled:opacity-50" :disabled="form.processing" @click="submit">
                        Create
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
