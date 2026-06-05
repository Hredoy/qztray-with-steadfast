<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import CustomerForm from "./partials/CustomerForm.vue";
import type { BreadcrumbItem } from "@/types";

const props = defineProps<{ customer: any }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Customers", href: route("customers.index") },
    { title: "Edit", href: route("customers.edit", props.customer.id) },
];

const form = useForm({
    name: props.customer.name ?? "",
    phone: props.customer.phone ?? "",
    addresses: props.customer.addresses?.length
        ? props.customer.addresses.map((row: any) => ({ address: row.address }))
        : [{ address: "" }],
});

function submit() {
    form.put(route("customers.update", props.customer.id));
}
</script>

<template>
    <Head title="Edit Customer" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Edit Customer</h2>
                <Link :href="route('customers.show', props.customer.id)" class="underline">Back</Link>
            </div>

            <div class="rounded-xl border p-4">
                <CustomerForm :form="form" />
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
