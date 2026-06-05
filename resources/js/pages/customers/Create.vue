<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import CustomerForm from "./partials/CustomerForm.vue";
import type { BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard") },
    { title: "Customers", href: route("customers.index") },
    { title: "Create", href: route("customers.create") },
];

const form = useForm({
    name: "",
    phone: "",
    addresses: [{ address: "" }],
});

function submit() {
    form.post(route("customers.store"));
}
</script>

<template>
    <Head title="Create Customer" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Create Customer</h2>
                <Link :href="route('customers.index')" class="underline">Back</Link>
            </div>

            <div class="rounded-xl border p-4">
                <CustomerForm :form="form" />
                <div class="mt-4 flex justify-end">
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
