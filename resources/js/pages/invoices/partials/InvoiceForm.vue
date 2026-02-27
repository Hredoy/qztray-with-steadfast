<script setup lang="ts">
import { computed, ref, watch } from "vue";
import axios from "axios";

const props = defineProps<{
    form: any;
    submitText?: string;
}>();

const submitLabel = computed(() => props.submitText ?? "Save");

const fraudLoading = ref(false);
const fraudResult = ref<any>(null);
const fraudError = ref<string | null>(null);

let timer: number | null = null;
let lastChecked = "";
let abortCtrl: AbortController | null = null;

function normalizePhone(p: string): string {
    return (p || "").trim().replace(/[^\d+]/g, "");
}

function isReadyForCheck(p: string): boolean {
    // run only when 11 digits (or +88 format)
    const x = normalizePhone(p);
    const digits = x.replace(/\D/g, "");
    // allow 11 digits local OR 13 digits with country code 88
    return digits.length === 11 || digits.length === 13;
}

async function checkFraud() {
    const phone = props.form.phone;
    if (!isReadyForCheck(phone)) return;

    const key = normalizePhone(phone);
    if (key === lastChecked) return;
    lastChecked = key;

    fraudLoading.value = true;
    fraudError.value = null;
    fraudResult.value = null;

    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();

    try {
        const res = await axios.post(
            "/api/fraud-check",
            { phone },
            { signal: abortCtrl.signal }
        );


        fraudResult.value = res.data;

        // Store in form for parent usage
        props.form.fraud_check = res.data;
        props.form.fraud_approved = !!res.data.approved;
    } catch (e: any) {
        if (e?.name === "CanceledError" || e?.code === "ERR_CANCELED") return;

        fraudError.value =
            e?.response?.data?.reason ||
            e?.response?.data?.message ||
            "Fraud check failed";

        props.form.fraud_check = null;
        props.form.fraud_approved = true; // fallback allow (you can set false if you want to block)
    } finally {
        fraudLoading.value = false;
    }
}

function onPhoneBlur() {
    checkFraud();
}

// Debounce while typing
watch(
    () => props.form.phone,
    () => {
        // reset lastChecked so it can re-check if user edits
        // (optional) lastChecked = "";
        if (timer) window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            checkFraud();
        }, 600);
    }
);

const fraudBadgeClass = computed(() => {
    if (fraudLoading.value) return "text-gray-500";
    if (fraudError.value) return "text-red-600";
    if (!fraudResult.value) return "text-gray-500";
    return fraudResult.value.approved ? "text-green-600" : "text-red-600";
});
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
            <input
                v-model="form.phone"
                class="w-full rounded-lg border p-2"
                @blur="onPhoneBlur"
            />

            <div class="mt-1 text-sm" :class="fraudBadgeClass">
                <span v-if="fraudLoading">Checking fraud…</span>

                <span v-else-if="fraudError">⚠️ {{ fraudError }}</span>

                <span v-else-if="fraudResult">
                  <span v-if="fraudResult.approved">✅ {{ fraudResult.note }}</span>
                  <span v-else>⛔ {{ fraudResult.reason }}</span>

                  <span v-if="fraudResult.customer_stats" class="ml-2 text-xs text-gray-500">
                    (S: {{ fraudResult.customer_stats.success }},
                    C: {{ fraudResult.customer_stats.cancel }},
                    T: {{ fraudResult.customer_stats.total }})
                  </span>
                </span>

                <span v-else class="text-gray-500">Enter phone to check</span>
            </div>

            <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                {{ form.errors.phone }}
            </div>
        </div>

        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium">Address</label>
            <input v-model="form.address" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">COD</label>
            <input v-model="form.cod" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.cod" class="mt-1 text-sm text-red-600">{{ form.errors.cod }}</div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Weight (KG)</label>
            <input v-model="form.wgt" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.wgt" class="mt-1 text-sm text-red-600">{{ form.errors.wgt }}</div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Product Instruction</label>
            <textarea v-model="form.instruction" rows="4" class="w-full rounded-lg border p-2"></textarea>
            <div v-if="form.errors.instruction" class="mt-1 text-sm text-red-600">{{ form.errors.instruction }}</div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Delivery Notes</label>
            <textarea v-model="form.notes" rows="4" class="w-full rounded-lg border p-2"></textarea>
            <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</div>
        </div>
    </div>
</template>
