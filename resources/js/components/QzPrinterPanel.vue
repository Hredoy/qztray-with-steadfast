<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { qzConnect, qzDisconnect, qzIsConnected, listPrinters, printRaw } from "@/lib/qz";
import { getSavedPrinter, savePrinter } from "@/lib/printerStore";

const loading = ref(false);
const status = ref<string>("Not connected");
const error = ref<string>("");
const printers = ref<string[]>([]);
const selectedPrinter = ref<string>(getSavedPrinter());

const connected = computed(() => qzIsConnected());

function setOk(msg: string) {
    status.value = msg;
    error.value = "";
}

function setErr(msg: string, e?: unknown) {
    status.value = msg;
    error.value = (e as any)?.message ? String((e as any).message) : String(e ?? msg);
}

async function connect() {
    loading.value = true;
    try {
        await qzConnect();
        setOk("Connected to QZ Tray ✅");
    } catch (e) {
        setErr("Failed to connect. Is QZ Tray running on this PC?", e);
    } finally {
        loading.value = false;
    }
}

async function disconnect() {
    loading.value = true;
    try {
        await qzDisconnect();
        setOk("Disconnected");
    } catch (e) {
        setErr("Failed to disconnect", e);
    } finally {
        loading.value = false;
    }
}

async function refreshPrinters() {
    loading.value = true;
    try {
        const list = await listPrinters();
        printers.value = list;

        // keep saved printer if it exists
        if (selectedPrinter.value && list.includes(selectedPrinter.value)) {
            // ok
        } else {
            selectedPrinter.value = list[0] ?? "";
            savePrinter(selectedPrinter.value);
        }

        setOk(`Loaded ${list.length} printer(s) ✅`);
    } catch (e) {
        setErr("Failed to load printers", e);
    } finally {
        loading.value = false;
    }
}

function onSelectChange() {
    savePrinter(selectedPrinter.value);
}

async function printTest() {
    loading.value = true;
    try {
        if (!selectedPrinter.value) throw new Error("No printer selected");
        const raw =
            "=== TEST PRINT ===\n" +
            "Laravel 12 + Vue + QZ Tray\n" +
            "--------------------------\n" +
            "Item A     1 x 100 = 100\n" +
            "Item B     2 x  50 = 100\n" +
            "--------------------------\n" +
            "TOTAL              200\n\n" +
            "Thank you!\n\n\n";

        console.log(await printRaw(selectedPrinter.value, raw));
        setOk("Printed test receipt ✅");
    } catch (e) {
        setErr("Print failed", e);
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    // optional: auto connect and load printers
    // connect().then(refreshPrinters).catch(() => {});
});
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-lg font-semibold">QZ Tray Printer</div>
                <div class="text-sm text-muted-foreground">
                    Status:
                    <span :class="connected ? 'text-green-600' : 'text-foreground'">{{ status }} </span>
                </div>
            </div>

            <div class="flex gap-2">
                <button
                    class="px-3 py-2 rounded-md border hover:bg-accent disabled:opacity-60"
                    :disabled="loading || connected"
                    @click="connect"
                >
                    Connect
                </button>
                <button
                    class="px-3 py-2 rounded-md border hover:bg-accent disabled:opacity-60"
                    :disabled="loading || !connected"
                    @click="disconnect"
                >
                    Disconnect
                </button>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Select Printer</label>
                <select
                    class="w-full rounded-md border px-3 py-2 bg-background"
                    v-model="selectedPrinter"
                    @change="onSelectChange"
                    :disabled="loading"
                >
                    <option value="">-- Select Printer --</option>
                    <option v-for="p in printers" :key="p" :value="p">{{ p }}</option>
                </select>
                <div class="text-xs text-muted-foreground mt-1">
                    Saved in browser localStorage.
                </div>
            </div>

            <div class="flex md:flex-col gap-2 md:justify-end">
                <button
                    class="px-3 py-2 rounded-md border hover:bg-accent disabled:opacity-60"
                    :disabled="loading || !connected"
                    @click="refreshPrinters"
                >
                    Load printers
                </button>
                <button
                    class="px-3 py-2 rounded-md border hover:bg-accent disabled:opacity-60"
                    :disabled="loading || !connected || !selectedPrinter"
                    @click="printTest"
                >
                    Print test
                </button>
            </div>
        </div>

        <div v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-md p-3">
            {{ error }}
        </div>
    </div>
</template>
