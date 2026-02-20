import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import { svelte } from "@sveltejs/vite-plugin-svelte";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.ts"],
            refresh: true,
        }),
        svelte(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            "@img": path.resolve(__dirname, "resources/img"),
            "@css": path.resolve(__dirname, "resources/css"),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes("node_modules/chart.js")) return "charts";
                    if (id.includes("node_modules/@inertiajs/svelte"))
                        return "inertia";
                    if (id.includes("node_modules/axios")) return "axios";
                    if (id.includes("node_modules/svelte")) return "svelte";
                    if (id.includes("node_modules")) return "vendor";
                },
            },
        },
        chunkSizeWarningLimit: 1000,
    },
});
