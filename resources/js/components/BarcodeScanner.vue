<script setup lang="ts">
import { ref, onUnmounted, watch } from 'vue';
import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType, NotFoundException } from '@zxing/library';

// All supported formats + TRY_HARDER for better detection at angles/distances
const hints = new Map<DecodeHintType, any>();
hints.set(DecodeHintType.POSSIBLE_FORMATS, [
    BarcodeFormat.EAN_13,
    BarcodeFormat.EAN_8,
    BarcodeFormat.UPC_A,
    BarcodeFormat.UPC_E,
    BarcodeFormat.CODE_128,
    BarcodeFormat.CODE_93,
    BarcodeFormat.CODE_39,
    BarcodeFormat.CODABAR,
    BarcodeFormat.ITF,
    BarcodeFormat.RSS_14,
    BarcodeFormat.RSS_EXPANDED,
    BarcodeFormat.QR_CODE,
    BarcodeFormat.DATA_MATRIX,
    BarcodeFormat.AZTEC,
    BarcodeFormat.PDF_417,
    BarcodeFormat.MAXICODE,
]);
hints.set(DecodeHintType.TRY_HARDER, true);

const emit = defineEmits<{
    scanned: [value: string];
    close: [];
}>();

const props = defineProps<{ isOpen: boolean }>();

const videoRef   = ref<HTMLVideoElement | null>(null);
const error      = ref('');
const scanning   = ref(false);
const devices    = ref<MediaDeviceInfo[]>([]);
const activeId   = ref<string | undefined>(undefined);

let stopFn: (() => void) | null = null;

/* ── helpers ─────────────────────────────────────────── */

async function startWithDevice(deviceId?: string) {
    // stop any existing stream first
    stopFn?.();
    stopFn   = null;
    scanning.value = false;
    error.value    = '';

    const reader = new BrowserMultiFormatReader(hints, {
        delayBetweenScanAttempts: 80,  // scan ~12× per second (default is 500ms)
        delayBetweenScanSuccess: 1000,
    });
    const controls = await reader.decodeFromVideoDevice(
        deviceId,
        videoRef.value!,
        (result, err) => {
            if (result) {
                emit('scanned', result.getText());
                doStop();
            } else if (err && !(err instanceof NotFoundException)) {
                // non-fatal per-frame miss — ignore
            }
        }
    );

    stopFn = () => controls.stop();
    scanning.value = true;
}

async function open() {
    error.value   = '';
    scanning.value = false;

    // 1️⃣  Ask for permission first — without this, enumerateDevices has no labels
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        // immediately release this stream; ZXing will open its own
        stream.getTracks().forEach(t => t.stop());
    } catch (e: any) {
        error.value = 'Camera permission denied. Please allow camera access and try again.';
        return;
    }

    // 2️⃣  Now enumerate — device labels are available after permission grant
    try {
        const all = await BrowserMultiFormatReader.listVideoInputDevices();
        devices.value = all;

        // prefer rear/environment cam on mobile; fall back to first (webcam on laptop)
        const preferred = all.find(d => /back|rear|environment/i.test(d.label)) ?? all[0];
        activeId.value = preferred?.deviceId;
    } catch {
        devices.value  = [];
        activeId.value = undefined;
    }

    // 3️⃣  Start ZXing — small delay so the <video> element is in the DOM
    setTimeout(() => startWithDevice(activeId.value), 80);
}

async function switchCamera(deviceId: string) {
    activeId.value = deviceId;
    await startWithDevice(deviceId);
}

function doStop() {
    stopFn?.();
    stopFn = null;
    scanning.value = false;
}

function close() {
    doStop();
    emit('close');
}

watch(() => props.isOpen, (val) => {
    if (val) open();
    else doStop();
});

onUnmounted(doStop);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 sm:items-center"
            @click.self="close"
        >
            <div class="w-full max-w-sm overflow-hidden rounded-t-2xl bg-white shadow-2xl dark:bg-gray-900 sm:rounded-2xl">

                <!-- Header -->
                <div class="flex items-center justify-between border-b px-4 py-3 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M7 8v8M10 8v8M13 8v5M16 8v8M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/>
                        </svg>
                        <span class="text-sm font-medium">Scan Barcode</span>
                    </div>
                    <button class="rounded-full p-1 hover:bg-gray-100 dark:hover:bg-gray-800" @click="close">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Viewfinder -->
                <div class="relative aspect-square bg-black">
                    <video ref="videoRef" class="h-full w-full object-cover" autoplay muted playsinline />

                    <!-- Aim overlay (only while scanning) -->
                    <div v-if="scanning" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="relative h-40 w-56">
                            <span class="absolute left-0 top-0 h-7 w-7 rounded-tl border-l-4 border-t-4 border-green-400"/>
                            <span class="absolute right-0 top-0 h-7 w-7 rounded-tr border-r-4 border-t-4 border-green-400"/>
                            <span class="absolute bottom-0 left-0 h-7 w-7 rounded-bl border-b-4 border-l-4 border-green-400"/>
                            <span class="absolute bottom-0 right-0 h-7 w-7 rounded-br border-b-4 border-r-4 border-green-400"/>
                            <div class="animate-scan absolute left-3 right-3 top-1/2 h-0.5 bg-green-400/80"/>
                        </div>
                    </div>

                    <!-- Camera switcher (shown only when multiple cameras) -->
                    <div v-if="devices.length > 1" class="absolute bottom-3 left-0 right-0 flex flex-wrap justify-center gap-2 px-4">
                        <button
                            v-for="(device, i) in devices"
                            :key="device.deviceId"
                            type="button"
                            class="rounded-full px-3 py-1 text-xs font-medium transition"
                            :class="device.deviceId === activeId
                                ? 'bg-white text-black'
                                : 'bg-black/50 text-white hover:bg-black/70'"
                            @click="switchCamera(device.deviceId)"
                        >
                            {{ device.label || `Camera ${i + 1}` }}
                        </button>
                    </div>
                </div>

                <!-- Status bar -->
                <div class="px-4 py-3 text-center text-sm">
                    <span v-if="error" class="text-red-500">{{ error }}</span>
                    <span v-else-if="scanning" class="text-gray-500 dark:text-gray-400">
                        Point camera at barcode…
                    </span>
                    <span v-else class="text-gray-400">Starting camera…</span>
                </div>

            </div>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes scan {
    0%, 100% { transform: translateY(-18px); opacity: 0.5; }
    50%       { transform: translateY(18px);  opacity: 1;   }
}
.animate-scan { animation: scan 1.8s ease-in-out infinite; }
</style>
