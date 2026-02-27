/* global qz */

declare const qz: any;

export async function qzConnect(): Promise<void> {
    if (qz.websocket.isActive()) return;
    await qz.websocket.connect();
}

export async function qzDisconnect(): Promise<void> {
    if (!qz.websocket.isActive()) return;
    await qz.websocket.disconnect();
}

export function qzIsConnected(): boolean {
    return qz.websocket.isActive();
}

export async function listPrinters(): Promise<string[]> {
    await qzConnect();
    return await qz.printers.find();
}

export async function printRaw(printerName: string, rawText: string): Promise<void> {
    if (!qz.websocket.isActive()) {
        await qz.websocket.connect();
    }

    const ESC = "\x1B";
    const GS = "\x1D";

    const init = ESC + "@";               // Initialize printer
    const center = ESC + "a" + "\x01";    // Center align
    const left = ESC + "a" + "\x00";      // Left align
    const boldOn = ESC + "E" + "\x01";
    const boldOff = ESC + "E" + "\x00";

    // Cut commands vary by model:
    const cutA = GS + "V" + "\x41" + "\x03"; // Partial cut
    const cutB = GS + "V" + "\x00";          // Full cut (alt)

    const text =
        init +
        center + boldOn + "TEST PRINT\n" + boldOff +
        left +
        "Xprinter XP-T361U\n" +
        "-----------------------------\n" +
        "Item A      1 x 100 = 100\n" +
        "Item B      2 x  50 = 100\n" +
        "-----------------------------\n" +
        "TOTAL               200\n" +
        "\n\n\n" +
        cutA;

    const config = qz.configs.create(printerName);
    const data = [{ type: "raw", format: "plain", data: text }];
    console.log(qz.websocket.isActive());
    await qz.print(config, data);
}
