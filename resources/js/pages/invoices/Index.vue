<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";
import { ref, watch, computed } from "vue";
import { dashboard } from '@/routes';

const props = defineProps<{
    invoices: {
        data: any[];
        links: any[];
        meta?: any;
    };
    filters: {
        q: string;
    };
    totalInvoiceSum: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: dashboard().url },
    { title: "Invoices", href: route("invoices.index") },
];

const q = ref(props.filters.q ?? "");

watch(q, (val) => {
    router.get(route("invoices.index"), { q: val }, { preserveState: true, replace: true });
});

const from = computed(() => {
    if (!props.invoices.data.length) return 0;
    return (props.invoices.current_page - 1) * props.invoices.per_page + 1;
});

const to = computed(() => {
    return (props.invoices.current_page - 1) * props.invoices.per_page + props.invoices.data.length;
});
function destroyInvoice(id: number) {
    if (!confirm("Delete this invoice?")) return;
    router.delete(route("invoices.destroy", id));
}
</script>

<template>
    <Head title="Invoices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between gap-3">
                <input
                    v-model="q"
                    placeholder="Search: invoice / stead_fast_id / name / phone"
                    class="w-full max-w-xl rounded-lg border p-2"
                />

                <Link
                    :href="route('invoices.create')"
                    class="rounded-lg bg-black px-4 py-2 text-white"
                >
                    + New
                </Link>
            </div>

            <div class="overflow-x-auto rounded-xl border ">
                <table class="min-w-full text-sm">
                    <thead class="border-b ">
                    <tr>
                        <th class="p-3 text-left">#</th>
                        <th class="p-3 text-left">Invoice</th>
                        <th class="p-3 text-left">SteadFast</th>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Phone</th>
                        <th class="p-3 text-left">COD</th>
                        <th class="p-3 text-left">Invoice Total</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr v-for="row in invoices.data" :key="row.id" class="border-b">
                        <td class="p-3">{{ row.id }}</td>
                        <td class="p-3">{{ row.invoice_id }}</td>
                        <td class="p-3">{{ row.stead_fast_id }}</td>
                        <td class="p-3">{{ row.name }}</td>
                        <td class="p-3">{{ row.phone }}</td>
                        <td class="p-3">{{ Number(row.cod).toFixed(2) }}</td>
                        <td class="p-3">{{ Number(row.total).toFixed(2) }}</td>
                        <td class="p-3 flex gap-2">
                            <Link :href="route('invoices.show', row.id)" class="underline">View</Link>
                            <Link :href="route('invoices.edit', row.id)" class="underline">Edit</Link>
                            <button class="text-red-600 underline" @click="destroyInvoice(row.id)">Delete</button>
                        </td>
                    </tr>

                    <tr v-if="!invoices.data.length">
                        <td colspan="7" class="p-4 text-center text-gray-500">No invoices found.</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Simple pagination (links from Laravel paginator) -->
            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="l in invoices.links"
                    :key="l.url + l.label"
                    :href="l.url || ''"
                    class="rounded border px-3 py-1"
                    :class="{
            'bg-black text-white': l.active,
            'opacity-50 pointer-events-none': !l.url
          }"
                    v-html="l.label"
                />
            </div>

            <div class="flex justify-between mb-3">
                <div class="text-lg font-semibold">
                    Total Invoice Sum: {{ Number(totalInvoiceSum).toFixed(2) }}
                </div>

                <div class="text-sm text-gray-500">
                    Showing {{ from }} - {{ to }} of {{ invoices.total }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
