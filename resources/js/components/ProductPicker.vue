<script setup lang="ts">
import { ref, watch, computed, nextTick } from 'vue';
import axios from 'axios';
import BarcodeScanner from '@/components/BarcodeScanner.vue';

interface ProductResult {
    variant_id: number;
    product_id: number;
    product_name: string;
    variant_name: string | null;
    sku: string | null;
    sale_price: number;
    cost_price: number;
    stock: number;
    label: string;
}

interface CartItem extends ProductResult {
    qty: number;
    price: number; // editable unit price
}

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const props = defineProps<{ modelValue: string }>();

/* ── search ───────────────────────────────────────────── */
const query       = ref('');
const results     = ref<ProductResult[]>([]);
const searching   = ref(false);
const showResults = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

watch(query, (val) => {
    if (searchTimer) clearTimeout(searchTimer);
    if (!val.trim()) { results.value = []; showResults.value = false; return; }
    searchTimer = setTimeout(() => doSearch(val.trim()), 280);
});

async function doSearch(q: string) {
    searching.value = true;
    try {
        const { data } = await axios.get(route('products.search'), { params: { q } });
        results.value  = data;
        showResults.value = data.length > 0;
    } finally {
        searching.value = false;
    }
}

/* ── cart ─────────────────────────────────────────────── */
const cart = ref<CartItem[]>([]);

function addProduct(p: ProductResult) {
    const existing = cart.value.find(c => c.variant_id === p.variant_id);
    if (existing) {
        existing.qty++;
    } else {
        cart.value.push({ ...p, qty: 1, price: p.sale_price });
    }
    query.value       = '';
    results.value     = [];
    showResults.value = false;
    syncToText();
    nextTick(() => searchInput.value?.focus());
}

function removeItem(index: number) {
    cart.value.splice(index, 1);
    syncToText();
}

const grandTotal = computed(() =>
    cart.value.reduce((s, i) => s + i.qty * i.price, 0)
);

/* ── sync cart → textarea text ────────────────────────── */
function syncToText() {
    if (!cart.value.length) {
        emit('update:modelValue', props.modelValue); // leave manual text untouched if cart empty
        return;
    }
    const lines = cart.value.map(i => {
        const name = i.variant_name ? `${i.product_name} (${i.variant_name})` : i.product_name;
        const sub  = (i.qty * i.price).toLocaleString();
        return `${name} × ${i.qty} = ৳${sub}`;
    });
    lines.push('', `মোট: ৳${grandTotal.value.toLocaleString()}`);
    emit('update:modelValue', lines.join('\n'));
}

watch(() => [cart.value.map(i => [i.qty, i.price])], syncToText, { deep: true });

/* ── barcode scanner ──────────────────────────────────── */
const scannerOpen = ref(false);

function onScanned(barcode: string) {
    scannerOpen.value = false;
    query.value = barcode;
    doSearch(barcode);
}

/* ── click-outside to close dropdown ─────────────────── */
function onBlur() {
    setTimeout(() => { showResults.value = false; }, 180);
}
</script>

<template>
    <div class="flex flex-col gap-3">

        <!-- Search bar -->
        <div class="relative">
            <div class="flex gap-2">
                <div class="relative flex-1">
                    <input
                        ref="searchInput"
                        v-model="query"
                        type="text"
                        placeholder="Search product by name or scan barcode…"
                        class="w-full rounded-lg border p-2 pr-8 text-sm"
                        autocomplete="off"
                        @focus="showResults = results.length > 0"
                        @blur="onBlur"
                    />
                    <!-- spinner -->
                    <svg v-if="searching" class="absolute right-2 top-2.5 h-4 w-4 animate-spin text-gray-400"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </div>
                <!-- barcode scan button -->
                <button
                    type="button"
                    title="Scan barcode"
                    class="flex items-center gap-1 rounded-lg border px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800 active:scale-95"
                    @click="scannerOpen = true"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M7 8v8M10 8v8M13 8v5M16 8v8M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/>
                    </svg>
                    <span class="hidden sm:inline">Scan</span>
                </button>
            </div>

            <!-- Dropdown results -->
            <div
                v-if="showResults"
                class="absolute left-0 right-0 top-full z-30 mt-1 max-h-64 overflow-y-auto rounded-lg border bg-white shadow-lg dark:bg-gray-900 dark:border-gray-700"
            >
                <button
                    v-for="p in results"
                    :key="p.variant_id"
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                    @mousedown.prevent="addProduct(p)"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ p.product_name }}</p>
                        <p v-if="p.variant_name" class="truncate text-xs text-gray-500">{{ p.variant_name }}</p>
                        <p v-if="p.sku" class="text-xs text-gray-400">SKU: {{ p.sku }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-semibold">৳{{ Number(p.sale_price).toLocaleString() }}</p>
                        <p class="text-xs" :class="p.stock > 0 ? 'text-green-600' : 'text-red-500'">
                            Stock: {{ p.stock }}
                        </p>
                    </div>
                </button>
            </div>
        </div>

        <!-- Cart list -->
        <div v-if="cart.length" class="overflow-hidden rounded-lg border dark:border-gray-700">
            <table class="min-w-full text-sm">
                <thead class="border-b bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="p-2 text-left">Product</th>
                        <th class="p-2 text-right w-20">Price</th>
                        <th class="p-2 text-center w-24">Qty</th>
                        <th class="p-2 text-right w-24">Subtotal</th>
                        <th class="p-2 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in cart" :key="item.variant_id" class="border-b last:border-0">
                        <td class="p-2">
                            <p class="font-medium leading-tight">{{ item.product_name }}</p>
                            <p v-if="item.variant_name" class="text-xs text-gray-500">{{ item.variant_name }}</p>
                        </td>
                        <td class="p-2">
                            <input
                                v-model.number="item.price"
                                type="number"
                                min="0"
                                class="w-full rounded border px-1.5 py-1 text-right text-sm"
                            />
                        </td>
                        <td class="p-2">
                            <div class="flex items-center justify-center gap-1">
                                <button type="button"
                                    class="flex h-6 w-6 items-center justify-center rounded border text-lg leading-none hover:bg-gray-100 dark:hover:bg-gray-700"
                                    @click="item.qty > 1 ? item.qty-- : removeItem(i)">−</button>
                                <input
                                    v-model.number="item.qty"
                                    type="number"
                                    min="1"
                                    class="w-10 rounded border px-1 py-1 text-center text-sm"
                                />
                                <button type="button"
                                    class="flex h-6 w-6 items-center justify-center rounded border text-lg leading-none hover:bg-gray-100 dark:hover:bg-gray-700"
                                    @click="item.qty++">+</button>
                            </div>
                        </td>
                        <td class="p-2 text-right font-medium">
                            ৳{{ (item.qty * item.price).toLocaleString() }}
                        </td>
                        <td class="p-2 text-center">
                            <button type="button" class="text-red-400 hover:text-red-600" @click="removeItem(i)">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="border-t bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <td colspan="3" class="p-2 text-right font-semibold">Grand Total</td>
                        <td class="p-2 text-right font-bold">৳{{ grandTotal.toLocaleString() }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Manual textarea (always visible for free-text notes) -->
        <div>
            <label class="mb-1 block text-xs text-gray-500">
                {{ cart.length ? 'Auto-generated (editable)' : 'Or type product list manually' }}
            </label>
            <textarea
                :value="modelValue"
                rows="4"
                class="w-full rounded-lg border p-2 text-sm"
                @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
            />
        </div>
    </div>

    <BarcodeScanner
        :is-open="scannerOpen"
        @scanned="onScanned"
        @close="scannerOpen = false"
    />
</template>
