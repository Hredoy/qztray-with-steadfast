<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    imports: {
        id: number;
        original_filename: string;
        supplier: string | null;
        invoice_number: string | null;
        invoice_date: string | null;
        total_amount: number;
        status: 'pending' | 'confirmed';
        items_count: number;
        created_at: string;
    }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Purchase Imports', href: route('purchase-imports.index') },
];
</script>

<template>
    <Head title="Purchase Imports" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Purchase Imports</h1>
                <Link :href="route('purchase-imports.create')" class="rounded-lg bg-black px-4 py-2 text-white text-sm">
                    + Import Invoice PDF
                </Link>
            </div>

            <div class="overflow-x-auto rounded-xl border">
                <table class="min-w-full text-sm">
                    <thead class="border-b bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="p-3 text-left">#</th>
                            <th class="p-3 text-left">File</th>
                            <th class="p-3 text-left">Supplier</th>
                            <th class="p-3 text-left">Invoice #</th>
                            <th class="p-3 text-left">Date</th>
                            <th class="p-3 text-left">Items</th>
                            <th class="p-3 text-left">Total</th>
                            <th class="p-3 text-left">Status</th>
                            <th class="p-3 text-left">Imported</th>
                            <th class="p-3 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in imports" :key="row.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="p-3">{{ row.id }}</td>
                            <td class="p-3 max-w-[160px] truncate" :title="row.original_filename">{{ row.original_filename }}</td>
                            <td class="p-3">{{ row.supplier ?? '—' }}</td>
                            <td class="p-3">{{ row.invoice_number ?? '—' }}</td>
                            <td class="p-3">{{ row.invoice_date ?? '—' }}</td>
                            <td class="p-3">{{ row.items_count }}</td>
                            <td class="p-3">৳{{ Number(row.total_amount).toLocaleString() }}</td>
                            <td class="p-3">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="row.status === 'confirmed'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'"
                                >
                                    {{ row.status }}
                                </span>
                            </td>
                            <td class="p-3 text-gray-500">{{ row.created_at }}</td>
                            <td class="p-3">
                                <Link :href="route('purchase-imports.show', row.id)" class="underline text-blue-600">View</Link>
                            </td>
                        </tr>
                        <tr v-if="!imports.length">
                            <td colspan="10" class="p-6 text-center text-gray-500">No imports yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
