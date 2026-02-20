/// <reference types="svelte" />
/// <reference types="vite/client" />
declare module "@inertiajs/core" {
    export interface InertiaConfig {
        flashDataType: {
            toast?: {
                type: "success" | "error" | "warning" | "info";
                message: string;
            };
        };
    }
}
declare global {
    interface Window {
        tt?: any;
    }
}
