import { page } from "@inertiajs/svelte";
import { get } from "svelte/store";

let isPopstate = false;
if (typeof window !== "undefined") {
    window.addEventListener("popstate", () => {
        isPopstate = true;
    });
}

export function usePageTransition() {
    let direction = $state(1);
    let oldUrl = $state("");

    $effect.pre(() => {
        // We need to subscribe to the $page store inside the effect
        // to ensure it reacts to Inertia navigation changes
        const pageStore = get(page);
        const newUrl = pageStore.url;
        
        if (oldUrl !== newUrl && typeof window !== "undefined") {
            const prev = oldUrl ? oldUrl.split("?")[0] : "";
            const next = newUrl.split("?")[0];
            const prevParts = prev ? prev.split("/").filter(Boolean) : [];
            const nextParts = next.split("/").filter(Boolean);

            if (isPopstate) {
                direction = -1;
                isPopstate = false;
            } else if (nextParts.length < prevParts.length) {
                direction = -1;
            } else if (
                ["/chef", "/customer", "/admin", "/pic"].includes(next) &&
                prevParts.length > 1
            ) {
                direction = -1;
            } else {
                direction = 1;
            }
            oldUrl = newUrl;
        }
    });

    return {
        get direction() {
            return direction;
        }
    };
}
