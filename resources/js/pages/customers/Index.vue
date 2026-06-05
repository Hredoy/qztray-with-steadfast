<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";
import { ref, watch } from "vue";

const props = defineProps<{
    customers: {
        data: any[];
        links: any[];
        total: number;
    };
    filters: { q: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Customers", href: route("customers.index") },
];

const q = ref(props.filters.q ?? "");

watch(q, (val) => {
    router.get(route("customers.index"), { q: val }, { preserveState: true, replace: true });
});

function destroyCustomer(id: number) {
    if (!confirm("Delete this customer?")) return;
    router.delete(route("customers.destroy", id));
}
</script>

<template>
    <Head title="Customers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between gap-3">
                <input v-model="q" placeholder="Search: name / phone" class="w-full max-w-xl rounded-lg border p-2" />
                <Link :href="route('customers.create')" class="rounded-lg bg-black px-4 py-2 text-white">+ New</Link>
            </div>

            <div class="overflow-x-auto rounded-xl border">
                <table class="min-w-full text-sm">
                    <thead class="border-b">
                        <tr>
                            <th class="p-3 text-left">#</th>
                            <th class="p-3 text-left">Name</th>
                            <th class="p-3 text-left">Phone</th>
                            <th class="p-3 text-left">Addresses</th>
                            <th class="p-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in customers.data" :key="row.id" class="border-b">
                            <td class="p-3">{{ row.id }}</td>
                            <td class="p-3">{{ row.name }}</td>
                            <td class="p-3">{{ row.phone }}</td>
                            <td class="p-3">
                                <div v-for="address in row.addresses" :key="address.id">{{ address.address }}</div>
                            </td>
                            <td class="flex gap-2 p-3">
                                <Link :href="route('customers.show', row.id)" class="underline">View</Link>
                                <Link :href="route('customers.edit', row.id)" class="underline">Edit</Link>
                                <button class="text-red-600 underline" @click="destroyCustomer(row.id)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!customers.data.length">
                            <td colspan="5" class="p-4 text-center text-gray-500">No customers found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in customers.links"
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
