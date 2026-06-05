<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref } from 'vue';

interface Variant {
    id: number;
    variant_name: string | null;
    sku: string | null;
    cost_price: number;
    sale_price: number;
    stock: number;
}

interface Product {
    id: number;
    name: string;
    sku: string | null;
    total_stock: number;
    variants: Variant[];
}

defineProps<{ products: Product[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Products', href: route('products.index') },
];

const expanded = ref<Set<number>>(new Set());

function toggle(id: number) {
    if (expanded.value.has(id)) {
        expanded.value.delete(id);
    } else {
        expanded.value.add(id);
    }
}
</script>

<template>
    <Head title="Products" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Products & Stock</h1>
                <Link :href="route('purchase-imports.create')" class="rounded-lg bg-black px-4 py-2 text-white text-sm">
                    + Import Invoice
                </Link>
            </div>

            <div class="overflow-x-auto rounded-xl border">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="w-8 p-3"></th>
                            <th class="p-3 text-left">Product</th>
                            <th class="p-3 text-left">SKU</th>
                            <th class="p-3 text-center">Variants</th>
                            <th class="p-3 text-right">Total Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="product in products" :key="product.id">
                            <tr
                                class="border-b cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                                @click="toggle(product.id)"
                            >
                                <td class="p-3 text-gray-400">
                                    <svg
                                        class="h-4 w-4 transition-transform"
                                        :class="{ 'rotate-90': expanded.has(product.id) }"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </td>
                                <td class="p-3 font-medium">{{ product.name }}</td>
                                <td class="p-3 text-gray-500">{{ product.sku ?? '—' }}</td>
                                <td class="p-3 text-center text-gray-500">{{ product.variants.length }}</td>
                                <td class="p-3 text-right font-semibold" :class="product.total_stock === 0 ? 'text-red-500' : ''">
                                    {{ product.total_stock }}
                                </td>
                            </tr>

                            <!-- Variant rows -->
                            <tr
                                v-if="expanded.has(product.id)"
                                v-for="v in product.variants"
                                :key="v.id"
                                class="border-b bg-gray-50/50 dark:bg-gray-900/30"
                            >
                                <td class="p-3"></td>
                                <td class="p-3 pl-8 text-gray-600">
                                    {{ v.variant_name ?? '(default)' }}
                                </td>
                                <td class="p-3 text-gray-500 text-xs">{{ v.sku ?? '—' }}</td>
                                <td class="p-3 text-center text-xs text-gray-500">
                                    Cost: ৳{{ Number(v.cost_price).toLocaleString() }}
                                    &nbsp;|&nbsp;
                                    Sale: ৳{{ Number(v.sale_price).toLocaleString() }}
                                </td>
                                <td class="p-3 text-right" :class="v.stock === 0 ? 'text-red-500' : 'text-green-600'">
                                    {{ v.stock }}
                                </td>
                            </tr>
                        </template>

                        <tr v-if="!products.length">
                            <td colspan="5" class="p-6 text-center text-gray-500">
                                No products yet. <Link :href="route('purchase-imports.create')" class="underline text-blue-600">Import your first invoice.</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
