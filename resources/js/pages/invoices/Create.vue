<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import InvoiceForm from "./partials/InvoiceForm.vue";
import type { BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Invoices", href: route("invoices.index") },
    { title: "Create", href: route("invoices.create") },
];

const form = useForm({
    invoice: "",
    stead_fast_id: "",
    wgt: "",
    name: "",
    phone: "",
    address: "",
    cod: "",
    instruction: "",
});

function submit() {
    form.post(route("invoices.store"));
}
</script>

<template>
    <Head title="Create Invoice" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Create Invoice</h2>
                <Link :href="route('invoices.index')" class="underline">Back</Link>
            </div>

            <div class="rounded-xl border  p-4">
                <InvoiceForm :form="form" />
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        class="rounded-lg bg-black px-4 py-2 text-white disabled:opacity-50"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Create
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
