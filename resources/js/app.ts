import "./bootstrap";
import { createInertiaApp } from "@inertiajs/svelte";
import { hydrate, mount } from "svelte";
import "@css/vendor/fontawesome/all.css";
import AdminLayout from "@/Lib/Admin/Layouts/Default.svelte";
import CustomerLayout from "@/Lib/Customer/Layouts/Default.svelte";
import { setRoleConfig } from "@/Lib/Admin/Utils/roles";

import { router } from "@inertiajs/svelte";
import { applyThemeClass } from "@/Lib/Admin/Hooks/sidebar";

// Synchronize theme on client-side navigation
router.on("navigate", (event: any) => {
    const componentName = event.detail.page.component;
    const isAdmin = componentName.startsWith("Domains/Admin/");
    if (isAdmin) {
        const savedTheme = localStorage.getItem("theme");
        const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
        const darkMode = savedTheme ? savedTheme === "dark" : prefersDark;
        applyThemeClass(darkMode);
    } else {
        // Public and Customer pages are permanently dark mode
        applyThemeClass(true);
    }
});

const getInitialPage = () => {
    if (typeof window === "undefined") return undefined;
    const el = document.getElementById("app");
    if (el?.dataset?.page) {
        return JSON.parse(el.dataset.page);
    }
    return undefined;
};

createInertiaApp({
    page: getInitialPage(),
    progress: false,
    resolve: (name: string) => {
        const pages = import.meta.glob("./Pages/**/*.svelte", {
            eager: true,
        }) as Record<string, any>;
        const page = pages[`./Pages/${name}.svelte`];
        const isAdmin = name.startsWith("Domains/Admin/");
        const isCustomer = name.startsWith("Domains/Customer/");
        return {
            default: page.default,
            layout:
                page.layout ||
                (isAdmin
                    ? AdminLayout
                    : isCustomer
                      ? CustomerLayout
                      : undefined),
        };
    },
    setup({ el, App, props }: { el: HTMLElement; App: any; props: any }) {
        const rc = props?.initialPage?.props?.roles_config;
        if (rc) {
            setRoleConfig(rc);
        }
        if (el?.dataset?.serverRendered === "true") {
            hydrate(App, { target: el, props });
        } else if (el) {
            mount(App, { target: el, props });
        }
    },
    defaults: {
        future: {
            useDialogForErrorModal: true,
        },
        visitOptions: () => {
            return { viewTransition: true };
        },
    },
});
