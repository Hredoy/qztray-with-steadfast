<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{ customer: any; totals: { spend: number; paid: number; due: number } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Customers", href: route("customers.index") },
    { title: "View", href: route("customers.show", props.customer.id) },
];

function destroyCustomer() {
    if (!confirm("Delete this customer?")) return;
    router.delete(route("customers.destroy", props.customer.id));
}
</script>

<template>
    <Head title="Customer Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ customer.name }}</h2>
                <div class="flex gap-3">
                    <Link :href="route('customers.edit', customer.id)" class="underline">Edit</Link>
                    <button class="text-red-600 underline" @click="destroyCustomer">Delete</button>
                    <Link :href="route('customers.index')" class="underline">Back</Link>
                </div>
            </div>

            <div class="rounded-xl border p-4 text-sm">
                <div class="grid gap-3 md:grid-cols-2">
                    <div><span class="font-medium">Name:</span> {{ customer.name }}</div>
                    <div><span class="font-medium">Phone:</span> {{ customer.phone }}</div>
                    <div class="md:col-span-2">
                        <span class="font-medium">Addresses:</span>
                        <div v-for="address in customer.addresses" :key="address.id">{{ address.address }}</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Total Spend</div>
                    <div class="text-xl font-semibold">{{ Number(totals.spend).toFixed(2) }}</div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Total Paid</div>
                    <div class="text-xl font-semibold">{{ Number(totals.paid).toFixed(2) }}</div>
                </div>
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Total Due</div>
                    <div class="text-xl font-semibold">{{ Number(totals.due).toFixed(2) }}</div>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <h3 class="mb-3 font-semibold">Order Notes</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th class="p-2 text-left">#</th>
                                <th class="p-2 text-left">Product List</th>
                                <th class="p-2 text-left">Paid</th>
                                <th class="p-2 text-left">Due</th>
                                <th class="p-2 text-left">Total</th>
                                <th class="p-2 text-left">Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="note in customer.order_notes" :key="note.id" class="border-b">
                                <td class="p-2">{{ note.id }}</td>
                                <td class="whitespace-pre-wrap p-2">{{ note.product_list }}</td>
                                <td class="p-2">{{ Number(note.paid).toFixed(2) }}</td>
                                <td class="p-2">{{ Number(note.due).toFixed(2) }}</td>
                                <td class="p-2">{{ Number(note.total).toFixed(2) }}</td>
                                <td class="p-2">
                                    <Link v-if="note.invoice" :href="route('invoices.show', note.invoice.id)" class="underline">
                                        {{ note.invoice.invoice_id }}
                                    </Link>
                                    <span v-else>-</span>
                                </td>
                            </tr>
                            <tr v-if="!customer.order_notes.length">
                                <td colspan="6" class="p-3 text-center text-gray-500">No order notes found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <h3 class="mb-3 font-semibold">Invoices</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th class="p-2 text-left">Invoice</th>
                                <th class="p-2 text-left">COD</th>
                                <th class="p-2 text-left">Total</th>
                                <th class="p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in customer.invoices" :key="invoice.id" class="border-b">
                                <td class="p-2">{{ invoice.invoice_id }}</td>
                                <td class="p-2">{{ Number(invoice.cod).toFixed(2) }}</td>
                                <td class="p-2">{{ Number(invoice.total).toFixed(2) }}</td>
                                <td class="p-2">
                                    <Link :href="route('invoices.show', invoice.id)" class="underline">View</Link>
                                </td>
                            </tr>
                            <tr v-if="!customer.invoices.length">
                                <td colspan="4" class="p-3 text-center text-gray-500">No invoices found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
