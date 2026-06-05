<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import BarcodeScanner from '@/components/BarcodeScanner.vue';
import type { BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue';

interface ImportItem {
    id: number;
    product_name: string;
    variant_name: string | null;
    sku: string | null;
    quantity: number;
    unit_cost: number;
    total_cost: number;
    _new?: boolean; // locally added rows (not yet persisted)
}

const props = defineProps<{
    purchaseImport: {
        id: number;
        original_filename: string;
        supplier: string | null;
        invoice_number: string | null;
        invoice_date: string | null;
        total_amount: number;
        status: 'pending' | 'confirmed';
        items: ImportItem[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Purchase Imports', href: route('purchase-imports.index') },
    { title: 'Review Import', href: '#' },
];

const items = ref<ImportItem[]>(props.purchaseImport.items.map(i => ({ ...i })));

let tempId = -1; // negative IDs for new rows (controller handles them separately)

function addRow() {
    items.value.push({
        id: tempId--,
        product_name: '',
        variant_name: null,
        sku: null,
        quantity: 1,
        unit_cost: 0,
        total_cost: 0,
        _new: true,
    });
}

function removeRow(index: number) {
    items.value.splice(index, 1);
}

const grandTotal = computed(() =>
    items.value.reduce((sum, i) => sum + Number(i.unit_cost) * Number(i.quantity), 0)
);

function recalcTotal(item: ImportItem) {
    item.total_cost = Number(item.unit_cost) * Number(item.quantity);
}

const confirming = ref(false);
const error = ref('');

// Barcode scanner state
const scannerOpen = ref(false);
let scanTarget: ImportItem | null = null;

function openScanner(item: ImportItem) {
    scanTarget = item;
    scannerOpen.value = true;
}

function onScanned(value: string) {
    if (scanTarget) scanTarget.sku = value;
    scannerOpen.value = false;
    scanTarget = null;
}

function confirm() {
    confirming.value = true;
    error.value = '';
    router.post(
        route('purchase-imports.confirm', props.purchaseImport.id),
        { items: items.value },
        {
            onError: (errors) => {
                error.value = errors.error ?? JSON.stringify(errors);
                confirming.value = false;
            },
            onFinish: () => {
                confirming.value = false;
            },
        }
    );
}
</script>

<template>
    <Head title="Review Purchase Import" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">

            <!-- Header -->
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">Review Import</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ purchaseImport.original_filename }}</p>
                </div>
                <span
                    class="rounded-full px-3 py-1 text-xs font-medium"
                    :class="purchaseImport.status === 'confirmed'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-yellow-100 text-yellow-700'"
                >
                    {{ purchaseImport.status }}
                </span>
            </div>

            <!-- Invoice meta -->
            <div class="grid grid-cols-2 gap-3 rounded-xl border p-4 text-sm sm:grid-cols-4">
                <div>
                    <p class="text-gray-500">Supplier</p>
                    <p class="font-medium">{{ purchaseImport.supplier ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Invoice #</p>
                    <p class="font-medium">{{ purchaseImport.invoice_number ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Invoice Date</p>
                    <p class="font-medium">{{ purchaseImport.invoice_date ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Total (extracted)</p>
                    <p class="font-medium">৳{{ Number(purchaseImport.total_amount).toLocaleString() }}</p>
                </div>
            </div>

            <!-- Items table -->
            <div class="overflow-x-auto rounded-xl border">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="p-3 text-left">Product Name</th>
                            <th class="p-3 text-left">Variant</th>
                            <th class="p-3 text-left">SKU</th>
                            <th class="p-3 text-right">Qty</th>
                            <th class="p-3 text-right">Unit Cost</th>
                            <th class="p-3 text-right">Total</th>
                            <th v-if="purchaseImport.status !== 'confirmed'" class="p-3 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in items"
                            :key="item.id"
                            class="border-b hover:bg-gray-50 dark:hover:bg-gray-800"
                            :class="item._new ? 'bg-blue-50/40 dark:bg-blue-950/20' : ''"
                        >
                            <td class="p-2">
                                <input
                                    v-model="item.product_name"
                                    :disabled="purchaseImport.status === 'confirmed'"
                                    placeholder="Product name"
                                    class="w-full rounded border px-2 py-1 text-sm disabled:bg-transparent disabled:border-transparent"
                                />
                            </td>
                            <td class="p-2">
                                <input
                                    v-model="item.variant_name"
                                    :disabled="purchaseImport.status === 'confirmed'"
                                    placeholder="e.g. Red / XL"
                                    class="w-full rounded border px-2 py-1 text-sm disabled:bg-transparent disabled:border-transparent"
                                />
                            </td>
                            <td class="p-2">
                                <div class="flex items-center gap-1">
                                    <input
                                        v-model="item.sku"
                                        :disabled="purchaseImport.status === 'confirmed'"
                                        placeholder="SKU"
                                        class="w-24 rounded border px-2 py-1 text-sm disabled:bg-transparent disabled:border-transparent"
                                    />
                                    <button
                                        v-if="purchaseImport.status !== 'confirmed'"
                                        type="button"
                                        title="Scan barcode"
                                        class="flex items-center justify-center rounded border p-1 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-95"
                                        @click="openScanner(item)"
                                    >
                                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2M7 8v8M10 8v8M13 8v5M16 8v8"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="p-2 text-right">
                                <input
                                    v-model.number="item.quantity"
                                    type="number"
                                    min="1"
                                    :disabled="purchaseImport.status === 'confirmed'"
                                    class="w-20 rounded border px-2 py-1 text-sm text-right disabled:bg-transparent disabled:border-transparent"
                                    @input="recalcTotal(item)"
                                />
                            </td>
                            <td class="p-2 text-right">
                                <input
                                    v-model.number="item.unit_cost"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :disabled="purchaseImport.status === 'confirmed'"
                                    class="w-28 rounded border px-2 py-1 text-sm text-right disabled:bg-transparent disabled:border-transparent"
                                    @input="recalcTotal(item)"
                                />
                            </td>
                            <td class="p-3 text-right font-medium">
                                ৳{{ (Number(item.unit_cost) * Number(item.quantity)).toLocaleString() }}
                            </td>
                            <!-- Remove button -->
                            <td v-if="purchaseImport.status !== 'confirmed'" class="p-2 text-center">
                                <button
                                    type="button"
                                    title="Remove row"
                                    class="rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 active:scale-95"
                                    @click="removeRow(index)"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="!items.length">
                            <td :colspan="purchaseImport.status !== 'confirmed' ? 7 : 6"
                                class="p-6 text-center text-gray-400">
                                No items. Click <strong>+ Add Row</strong> to add manually.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t bg-gray-50 dark:bg-gray-800">
                        <!-- Add row button -->
                        <tr v-if="purchaseImport.status !== 'confirmed'">
                            <td colspan="7" class="p-2">
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950 active:scale-95"
                                    @click="addRow"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add Row
                                </button>
                            </td>
                        </tr>
                        <!-- Grand total -->
                        <tr class="border-t">
                            <td :colspan="purchaseImport.status !== 'confirmed' ? 6 : 5"
                                class="p-3 text-right font-semibold">Grand Total</td>
                            <td class="p-3 text-right font-bold">৳{{ grandTotal.toLocaleString() }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p v-if="error" class="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">{{ error }}</p>

            <!-- Actions -->
            <div class="flex gap-3">
                <Link :href="route('purchase-imports.index')" class="rounded-lg border px-4 py-2 text-sm">
                    Back
                </Link>
                <button
                    v-if="purchaseImport.status === 'pending'"
                    class="rounded-lg bg-green-600 px-6 py-2 text-sm text-white font-medium hover:bg-green-700 disabled:opacity-50"
                    :disabled="confirming"
                    @click="confirm"
                >
                    <span v-if="confirming">Saving…</span>
                    <span v-else>Confirm & Update Stock</span>
                </button>
                <p v-else class="flex items-center gap-2 text-sm text-green-600 font-medium">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Stock has been updated
                </p>
            </div>
        </div>
    </AppLayout>

    <BarcodeScanner
        :is-open="scannerOpen"
        @scanned="onScanned"
        @close="scannerOpen = false"
    />
</template>
