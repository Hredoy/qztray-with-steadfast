<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";
import { ref, watch } from "vue";

const props = defineProps<{
    orderNotes: {
        data: any[];
        links: any[];
        total: number;
    };
    filters: { q: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Order Notes", href: route("order-notes.index") },
];

const q = ref(props.filters.q ?? "");

watch(q, (val) => {
    router.get(route("order-notes.index"), { q: val }, { preserveState: true, replace: true });
});

function destroyOrderNote(id: number) {
    if (!confirm("Delete this order note?")) return;
    router.delete(route("order-notes.destroy", id));
}
</script>

<template>
    <Head title="Order Notes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between gap-3">
                <input v-model="q" placeholder="Search: name / phone / products" class="w-full max-w-xl rounded-lg border p-2" />
                <Link :href="route('order-notes.create')" class="rounded-lg bg-black px-4 py-2 text-white">+ New</Link>
            </div>

            <div class="overflow-x-auto rounded-xl border">
                <table class="min-w-full text-sm">
                    <thead class="border-b">
                        <tr>
                            <th class="p-3 text-left">#</th>
                            <th class="p-3 text-left">Name</th>
                            <th class="p-3 text-left">Phone</th>
                            <th class="p-3 text-left">Address</th>
                            <th class="p-3 text-left">Paid</th>
                            <th class="p-3 text-left">Due</th>
                            <th class="p-3 text-left">Total</th>
                            <th class="p-3 text-left">Invoice</th>
                            <th class="p-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in orderNotes.data" :key="row.id" class="border-b">
                            <td class="p-3">{{ row.id }}</td>
                            <td class="p-3">{{ row.name }}</td>
                            <td class="p-3">{{ row.phone }}</td>
                            <td class="p-3">{{ row.address }}</td>
                            <td class="p-3">{{ Number(row.paid).toFixed(2) }}</td>
                            <td class="p-3">{{ Number(row.due).toFixed(2) }}</td>
                            <td class="p-3">{{ Number(row.total).toFixed(2) }}</td>
                            <td class="p-3">
                                <Link v-if="row.invoice" :href="route('invoices.show', row.invoice.id)" class="underline">
                                    {{ row.invoice.invoice_id }}
                                </Link>
                                <span v-else>-</span>
                            </td>
                            <td class="flex gap-2 p-3">
                                <Link :href="route('order-notes.show', row.id)" class="underline">View</Link>
                                <Link v-if="!row.invoice_id" :href="route('order-notes.edit', row.id)" class="underline">Edit</Link>
                                <button v-if="!row.invoice_id" class="text-red-600 underline" @click="destroyOrderNote(row.id)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!orderNotes.data.length">
                            <td colspan="9" class="p-4 text-center text-gray-500">No order notes found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in orderNotes.links"
                    :key="link.url + link.label"
                    :href="link.url || ''"
                    class="rounded border px-3 py-1"
                    :class="{ 'bg-black text-white': link.active, 'pointer-events-none opacity-50': !link.url }"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
