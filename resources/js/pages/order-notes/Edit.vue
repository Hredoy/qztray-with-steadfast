<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import OrderNoteForm from "./partials/OrderNoteForm.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{ orderNote: any; customers: any[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Order Notes", href: route("order-notes.index") },
    { title: "Edit", href: route("order-notes.edit", props.orderNote.id) },
];

const form = useForm({
    customer_id: props.orderNote.customer_id ?? "",
    name: props.orderNote.name ?? "",
    phone: props.orderNote.phone ?? "",
    address: props.orderNote.address ?? "",
    product_list: props.orderNote.product_list ?? "",
    paid: props.orderNote.paid ?? 0,
    due: props.orderNote.due ?? 0,
});

function submit() {
    form.put(route("order-notes.update", props.orderNote.id));
}
</script>

<template>
    <Head title="Edit Order Note" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Edit Order Note</h2>
                <Link :href="route('order-notes.show', props.orderNote.id)" class="underline">Back</Link>
            </div>
            <div class="rounded-xl border p-4">
                <OrderNoteForm :form="form" :customers="customers" />
                <div class="mt-4 flex justify-end">
                    <button class="rounded-lg bg-black px-4 py-2 text-white disabled:opacity-50" :disabled="form.processing" @click="submit">
                        Update
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
