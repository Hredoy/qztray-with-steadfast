<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";
import { ref } from "vue";

const props = defineProps<{ invoice: any }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Invoices", href: "/invoices" },
    { title: "View", href: `/invoices/${props.invoice.id}` },
];

function destroyInvoice() {
    if (!confirm("Delete this invoice?")) return;
    router.delete(`/invoices/${props.invoice.id}`);
}

/** Print modal */
const printOpen = ref(false);
const printTitle = ref("Print");
const printUrl = ref<string>("");

function openPrint(type: "instruction" | "logo" | "packaging-slip") {
    printOpen.value = true;

    if (type === "instruction") {
        printTitle.value = "Print Instruction";
        printUrl.value = `/invoices/${props.invoice.id}/print/instruction`;
    } else if (type === "logo") {
        printTitle.value = "Print Logo";
        printUrl.value = `/invoices/${props.invoice.id}/print/logo-pdf`;
    } else {
        printTitle.value = "Print Packaging Slip";
        printUrl.value = `/invoices/${props.invoice.id}/print/packaging-slip`;
    }
}

function closePrint() {
    printOpen.value = false;
    printUrl.value = "";
}

function doPrint() {
    const iframe = document.getElementById("print-frame") as HTMLIFrameElement | null;
    if (!iframe?.contentWindow) return;
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
}
</script>

<template>
    <Head title="Invoice Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">Invoice #{{ props.invoice.id }}</h2>

                <div class="flex flex-wrap gap-3">
                    <!-- NEW PRINT BUTTONS -->
                    <button class="rounded-lg border px-3 py-2" @click="openPrint('instruction')">
                        Print Instruction
                    </button>
                    <button class="rounded-lg border px-3 py-2" @click="openPrint('logo')">
                        Print Logos
                    </button>
                    <button class="rounded-lg border px-3 py-2" @click="openPrint('packaging-slip')">
                        Print Packaging Slip
                    </button>

                    <Link :href="`/invoices/${props.invoice.id}/edit`" class="underline">Edit</Link>
                    <button class="text-red-600 underline" @click="destroyInvoice">Delete</button>
                    <Link href="/invoices" class="underline">Back</Link>
                </div>
            </div>

            <!-- Your existing details card -->
            <div class="rounded-xl border  p-4 text-sm">
                <div class="grid gap-3 md:grid-cols-2">
                    <div><span class="font-medium">Invoice:</span> {{ invoice.invoice }}</div>
                    <div><span class="font-medium">SteadFast ID:</span> {{ invoice.stead_fast_id }}</div>
                    <div><span class="font-medium">Weight:</span> {{ invoice.wgt }}</div>
                    <div><span class="font-medium">COD:</span> {{ invoice.cod }}</div>
                    <div><span class="font-medium">Name:</span> {{ invoice.name }}</div>
                    <div><span class="font-medium">Phone:</span> {{ invoice.phone }}</div>
                    <div class="md:col-span-2"><span class="font-medium">Address:</span> {{ invoice.address }}</div>
                    <div class="md:col-span-2">
                        <span class="font-medium">Instruction:</span>
                        <div class="mt-1 whitespace-pre-wrap text-gray-700">{{ invoice.instruction ?? "-" }}</div>
                    </div>
                </div>
            </div>

            <!-- PRINT MODAL -->
            <div
                v-if="printOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                @click.self="closePrint"
            >
                <div class="w-full max-w-4xl rounded-xl bg-black text-white shadow-lg">
                    <div class="flex items-center justify-between border-b p-3">
                        <div class="font-semibold">{{ printTitle }}</div>
                        <div class="flex gap-2">
                            <button class="rounded-lg border px-3 py-1" @click="doPrint">Print</button>
                            <button class="rounded-lg border px-3 py-1" @click="closePrint">Close</button>
                        </div>
                    </div>

                    <div class="p-3 bg-white">
                        <iframe
                            id="print-frame"
                            :src="printUrl"
                            class="h-[70vh] w-full rounded-lg border"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
