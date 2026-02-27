const KEY = "pos.selected_printer";

export function getSavedPrinter(): string {
    try {
        return localStorage.getItem(KEY) || "";
    } catch {
        return "";
    }
}

export function savePrinter(name: string): void {
    try {
        localStorage.setItem(KEY, name || "");
    } catch {
        // ignore
    }
}
