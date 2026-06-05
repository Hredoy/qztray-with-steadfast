<script setup lang="ts">
const props = defineProps<{
    form: any;
}>();

function addAddress() {
    props.form.addresses.push({ address: "" });
}

function removeAddress(index: number) {
    if (props.form.addresses.length === 1) return;
    props.form.addresses.splice(index, 1);
}
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
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
            <div class="mb-2 flex items-center justify-between">
                <label class="block text-sm font-medium">Addresses</label>
                <button type="button" class="rounded-lg border px-3 py-1 text-sm" @click="addAddress">
                    Add Address
                </button>
            </div>

            <div class="space-y-2">
                <div v-for="(row, index) in form.addresses" :key="index" class="flex gap-2">
                    <input v-model="row.address" class="w-full rounded-lg border p-2" />
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-2 text-red-600 disabled:opacity-50"
                        :disabled="form.addresses.length === 1"
                        @click="removeAddress(index)"
                    >
                        Remove
                    </button>
                </div>
            </div>

            <div v-if="form.errors.addresses" class="mt-1 text-sm text-red-600">{{ form.errors.addresses }}</div>
            <div
                v-for="(row, index) in form.addresses"
                :key="`error-${index}`"
                class="mt-1 text-sm text-red-600"
            >
                {{ form.errors[`addresses.${index}.address`] }}
            </div>
        </div>
    </div>
</template>
