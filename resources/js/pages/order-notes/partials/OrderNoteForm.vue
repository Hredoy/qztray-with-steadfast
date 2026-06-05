<script setup lang="ts">
import { computed, watch } from "vue";

const props = defineProps<{
    form: any;
    customers: any[];
}>();

const total = computed(() => Number(props.form.paid || 0) + Number(props.form.due || 0));

function selectedCustomer() {
    return props.customers.find((customer) => Number(customer.id) === Number(props.form.customer_id));
}

watch(
    () => props.form.customer_id,
    () => {
        const customer = selectedCustomer();
        if (!customer) return;

        props.form.name = props.form.name || customer.name;
        props.form.phone = props.form.phone || customer.phone;
        props.form.address = props.form.address || customer.addresses?.[0]?.address || "";
    }
);
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium">Customer</label>
            <select v-model="form.customer_id" class="w-full rounded-lg border p-2">
                <option value="">Find or create by phone</option>
                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }} - {{ customer.phone }}
                </option>
            </select>
            <div v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">{{ form.errors.customer_id }}</div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Name</label>
            <input v-model="form.name" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Phone</label>
            <input v-model="form.phone" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</div>
        </div>

        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium">Address</label>
            <input v-model="form.address" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</div>
        </div>

        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium">Product List</label>
            <textarea v-model="form.product_list" rows="5" class="w-full rounded-lg border p-2"></textarea>
            <div v-if="form.errors.product_list" class="mt-1 text-sm text-red-600">{{ form.errors.product_list }}</div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Paid</label>
            <input v-model="form.paid" type="number" min="0" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.paid" class="mt-1 text-sm text-red-600">{{ form.errors.paid }}</div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Due</label>
            <input v-model="form.due" type="number" min="0" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.due" class="mt-1 text-sm text-red-600">{{ form.errors.due }}</div>
        </div>

        <div class="md:col-span-2 rounded-lg border p-3 text-sm">
            <span class="font-medium">Total:</span> {{ total.toFixed(2) }}
        </div>
    </div>
</template>
