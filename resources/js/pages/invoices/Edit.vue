<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import InvoiceForm from "./partials/InvoiceForm.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{ invoice: any }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Invoices", href: route("invoices.index") },
    { title: "Edit", href: route("invoices.edit", props.invoice.id) },
];

const form = useForm({
    invoice: props.invoice.invoice ?? "",
    stead_fast_id: props.invoice.stead_fast_id ?? "",
    wgt: props.invoice.wgt ?? "",
    name: props.invoice.name ?? "",
    phone: props.invoice.phone ?? "",
    address: props.invoice.address ?? "",
    cod: props.invoice.cod ?? "",
    instruction: props.invoice.instruction ?? "",
});

function submit() {
    form.put(route("invoices.update", props.invoice.id));
}
</script>

<template>
    <Head title="Edit Invoice" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Edit Invoice #{{ props.invoice.id }}</h2>
                <Link :href="route('invoices.index')" class="underline">Back</Link>
            </div>

            <div class="rounded-xl border  p-4">
                <InvoiceForm :form="form" />
                <div class="mt-4 flex justify-end">
                    <button
                        class="rounded-lg bg-black px-4 py-2 text-white disabled:opacity-50"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Update
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
