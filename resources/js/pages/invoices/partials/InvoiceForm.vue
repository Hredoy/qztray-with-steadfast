<script setup lang="ts">
import { computed, ref, watch } from "vue";
import axios from "axios";

const props = defineProps<{
    form: any;
    submitText?: string;
}>();

const submitLabel = computed(() => props.submitText ?? "Save");

// ================= Fraud check =================
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
    const x = normalizePhone(p);
    const digits = x.replace(/\D/g, "");
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
        props.form.fraud_check = res.data;
        props.form.fraud_approved = !!res.data.approved;
    } catch (e: any) {
        if (e?.name === "CanceledError" || e?.code === "ERR_CANCELED") return;

        fraudError.value =
            e?.response?.data?.reason ||
            e?.response?.data?.message ||
            "Fraud check failed";

        props.form.fraud_check = null;
        props.form.fraud_approved = true; // fallback allow
    } finally {
        fraudLoading.value = false;
    }
}

function onPhoneBlur() {
    checkFraud();
}

watch(
    () => props.form.phone,
    () => {
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

// ================= Delivery Type + COD =================

// Ensure defaults (optional but helpful)
if (props.form.delivery_type == null) props.form.delivery_type = 2; // default Delivery
if (props.form.cod == null) props.form.cod = 0;

const isPickup = computed(() => Number(props.form.delivery_type) === 1);
const showCod = computed(() => !isPickup.value);

// When Pickup → force COD = 0 and clear error
watch(
    () => props.form.delivery_type,
    (val) => {
        const type = Number(val);
        if (type === 1) {
            props.form.cod = 0;
            // clear existing cod error if any
            if (props.form?.errors) props.form.errors.cod = null;
        }
    },
    { immediate: true }
);

// Optional: tiny client-side validation for COD (server validation still needed)
const codLocalError = computed(() => {
    if (!showCod.value) return null;

    const v = props.form.cod;
    if (v === "" || v === null || typeof v === "undefined") return "COD is required";
    const n = Number(v);
    if (Number.isNaN(n)) return "COD must be a number";
    if (n < 0) return "COD must be minimum 0";
    return null;
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
            <input v-model="form.phone" class="w-full rounded-lg border p-2" @blur="onPhoneBlur" />

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

        <!-- ✅ Delivery Type Dropdown -->
        <div>
            <label class="mb-1 block text-sm font-medium">Delivery Type</label>
            <select v-model="form.delivery_type" class="w-full rounded-lg border p-2">
                <option :value="1">Pickup</option>
                <option :value="2">Delivery</option>
            </select>
            <div v-if="form.errors.delivery_type" class="mt-1 text-sm text-red-600">
                {{ form.errors.delivery_type }}
            </div>
        </div>

        <!-- ✅ COD (only for Delivery) -->
        <div v-if="showCod">
            <label class="mb-1 block text-sm font-medium">COD</label>
            <input v-model="form.cod" type="number" min="0" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.cod" class="mt-1 text-sm text-red-600">{{ form.errors.cod }}</div>
            <div v-else-if="codLocalError" class="mt-1 text-sm text-red-600">{{ codLocalError }}</div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Total Invoice</label>
            <input v-model="form.total" class="w-full rounded-lg border p-2" />
            <div v-if="form.errors.total" class="mt-1 text-sm text-red-600">{{ form.errors.total }}</div>
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
