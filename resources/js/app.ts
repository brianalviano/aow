import "./bootstrap";
import { createInertiaApp } from "@inertiajs/svelte";
import { hydrate, mount } from "svelte";
import "@css/vendor/fontawesome/all.css";
import AdminLayout from "@/Lib/Admin/Layouts/Default.svelte";
import CustomerLayout from "@/Lib/Customer/Layouts/Default.svelte";
import ChefLayout from "@/Lib/Chef/Layouts/Default.svelte";
import { setRoleConfig } from "@/Lib/Admin/Utils/roles";

createInertiaApp({
    progress: false,
    resolve: (name: string) => {
        const pages = import.meta.glob("./Pages/**/*.svelte", {
            eager: true,
        }) as Record<string, any>;
        const page = pages[`./Pages/${name}.svelte`];
        const isAdmin = name.startsWith("Domains/Admin/");
        const isCustomer = name.startsWith("Domains/Customer/");
        const isChef = name.startsWith("Domains/Chef/");
        return {
            default: page.default,
            layout:
                page.layout ||
                (isAdmin
                    ? AdminLayout
                    : isCustomer
                      ? CustomerLayout
                      : isChef
                        ? ChefLayout
                        : undefined),
        };
    },
    setup({ el, App, props }: { el: HTMLElement; App: any; props: any }) {
        const rc = props?.initialPage?.props?.roles_config;
        if (rc) {
            setRoleConfig(rc);
        }
        if (el.dataset.serverRendered === "true") {
            hydrate(App, { target: el, props });
        } else {
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
