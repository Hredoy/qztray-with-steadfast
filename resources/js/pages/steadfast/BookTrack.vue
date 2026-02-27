<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";
import { dashboard } from "@/routes";
import { ref } from "vue";

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: dashboard().url },
    { title: "SteadFast", href: dashboard().url },
];

const booking = ref({
    invoice: "",
    recipient_name: "",
    recipient_phone: "",
    recipient_address: "",
    cod_amount: 0,
    note: "",
});

const tracking_code = ref("");
const bookingRes = ref<any>(null);
const trackingRes = ref<any>(null);
const loadingBook = ref(false);
const loadingTrack = ref(false);
const err = ref("");

function csrfToken(): string {
    const el = document.querySelector(
        'meta[name="csrf-token"]'
    ) as HTMLMetaElement | null;

    return el?.content || "";
}

async function bookNow() {
    err.value = "";
    bookingRes.value = null;

    loadingBook.value = true;
    try {
        const r = await fetch("/steadfast/book", {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken(),
                "Accept": "application/json"
            },
            body: JSON.stringify(booking.value),
        });
        bookingRes.value = await r.json();
    } catch (e: any) {
        err.value = e?.message || String(e);
    } finally {
        loadingBook.value = false;
    }
}

async function trackNow() {
    err.value = "";
    trackingRes.value = null;

    loadingTrack.value = true;
    try {
        const r = await fetch("/steadfast/track", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf(),
            },
            body: JSON.stringify({ tracking_code: tracking_code.value }),
        });
        trackingRes.value = await r.json();
    } catch (e: any) {
        err.value = e?.message || String(e);
    } finally {
        loadingTrack.value = false;
    }
}
</script>

<template>
    <Head title="SteadFast Booking & Tracking" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <!-- BOOK -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4 space-y-3">
                    <div class="text-lg font-semibold">Book Consignment</div>

                    <div class="grid gap-3">
                        <div>
                            <label class="text-sm">Invoice</label>
                            <input v-model="booking.invoice" class="w-full mt-1 rounded-md border px-3 py-2 bg-background" />
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="text-sm">Recipient Name</label>
                                <input v-model="booking.recipient_name" class="w-full mt-1 rounded-md border px-3 py-2 bg-background" />
                            </div>
                            <div>
                                <label class="text-sm">Recipient Phone</label>
                                <input v-model="booking.recipient_phone" class="w-full mt-1 rounded-md border px-3 py-2 bg-background" />
                            </div>
                        </div>

                        <div>
                            <label class="text-sm">Recipient Address</label>
                            <textarea v-model="booking.recipient_address" class="w-full mt-1 rounded-md border px-3 py-2 bg-background" rows="3" />
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <label class="text-sm">COD Amount</label>
                                <input type="number" v-model.number="booking.cod_amount" class="w-full mt-1 rounded-md border px-3 py-2 bg-background" />
                            </div>
                            <div>
                                <label class="text-sm">Note</label>
                                <input v-model="booking.note" class="w-full mt-1 rounded-md border px-3 py-2 bg-background" />
                            </div>
                        </div>

                        <button
                            class="px-3 py-2 rounded-md border hover:bg-accent disabled:opacity-60"
                            :disabled="loadingBook"
                            @click="bookNow"
                        >
                            {{ loadingBook ? "Booking..." : "Book Now" }}
                        </button>
                    </div>

                    <div v-if="bookingRes" class="text-xs bg-muted rounded-md p-3 overflow-auto">
                        <pre>{{ bookingRes }}</pre>
                    </div>
                </div>

                <!-- TRACK -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4 space-y-3">
                    <div class="text-lg font-semibold">Track Consignment</div>

                    <div>
                        <label class="text-sm">Tracking / Consignment Code</label>
                        <input v-model="tracking_code" class="w-full mt-1 rounded-md border px-3 py-2 bg-background" />
                    </div>

                    <button
                        class="px-3 py-2 rounded-md border hover:bg-accent disabled:opacity-60"
                        :disabled="loadingTrack || !tracking_code"
                        @click="trackNow"
                    >
                        {{ loadingTrack ? "Tracking..." : "Track" }}
                    </button>

                    <div v-if="trackingRes" class="text-xs bg-muted rounded-md p-3 overflow-auto">
                        <pre>{{ trackingRes }}</pre>
                    </div>
                </div>
            </div>

            <div v-if="err" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-md p-3">
                {{ err }}
            </div>

            <div class="text-xs text-muted-foreground">
                Note: If SteadFast endpoint names differ for your account, update the URLs in <code>SteadfastService</code>.
            </div>
        </div>
    </AppLayout>
</template>
