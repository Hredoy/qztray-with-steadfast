<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{ orderNote: any }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Order Notes", href: route("order-notes.index") },
    { title: "View", href: route("order-notes.show", props.orderNote.id) },
];

function convertToInvoice() {
    router.post(route("order-notes.convert", props.orderNote.id));
}

function destroyOrderNote() {
    if (!confirm("Delete this order note?")) return;
    router.delete(route("order-notes.destroy", props.orderNote.id));
}
</script>

<template>
    <Head title="Order Note Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Order Note #{{ orderNote.id }}</h2>
                <div class="flex gap-3">
                    <button v-if="!orderNote.invoice_id" class="rounded-lg bg-black px-4 py-2 text-white" @click="convertToInvoice">
                        Convert to Invoice
                    </button>
                    <Link v-if="orderNote.invoice" :href="route('invoices.show', orderNote.invoice.id)" class="underline">Invoice</Link>
                    <Link v-if="!orderNote.invoice_id" :href="route('order-notes.edit', orderNote.id)" class="underline">Edit</Link>
                    <button v-if="!orderNote.invoice_id" class="text-red-600 underline" @click="destroyOrderNote">Delete</button>
                    <Link :href="route('order-notes.index')" class="underline">Back</Link>
                </div>
            </div>

            <div class="rounded-xl border p-4 text-sm">
                <div class="grid gap-3 md:grid-cols-2">
                    <div><span class="font-medium">Customer:</span>
                        <Link :href="route('customers.show', orderNote.customer.id)" class="underline">
                            {{ orderNote.customer.name }}
                        </Link>
                    </div>
                    <div><span class="font-medium">Phone:</span> {{ orderNote.phone }}</div>
                    <div><span class="font-medium">Name:</span> {{ orderNote.name }}</div>
                    <div><span class="font-medium">Address:</span> {{ orderNote.address }}</div>
                    <div><span class="font-medium">Paid:</span> {{ Number(orderNote.paid).toFixed(2) }}</div>
                    <div><span class="font-medium">Due:</span> {{ Number(orderNote.due).toFixed(2) }}</div>
                    <div><span class="font-medium">Total:</span> {{ Number(orderNote.total).toFixed(2) }}</div>
                    <div class="md:col-span-2">
                        <span class="font-medium">Product List:</span>
                        <div class="mt-1 whitespace-pre-wrap">{{ orderNote.product_list }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
