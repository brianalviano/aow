<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type CustomerData = {
        id: string;
        name: string;
        email: string;
        phone: string | null;
        address: string | null;
        latitude?: number | null;
        longitude?: number | null;
        is_active: boolean;
        is_visible_in_pos?: boolean;
        is_visible_in_marketing?: boolean;
        marketer_ids?: string[] | null;
        last_transaction_at: string | null;
    };

    let customer = $derived($page.props.customer as CustomerData | null);
    let isEdit = $derived(customer !== null);
    type Marketer = { id: string; name: string };
    let marketers = $derived(($page.props.marketers as Marketer[]) ?? []);
    type GeofenceCfg = {
        center_lat: number;
        center_long: number;
        radius_m: number;
        tomtom_key: string;
        tomtom_sdk_base: string;
    };
    let geofence = $derived($page.props.geofence as GeofenceCfg);

    const form = useForm(
        untrack(() => ({
            name: customer?.name ?? "",
            email: customer?.email ?? "",
            phone: customer?.phone ?? "",
            address: customer?.address ?? "",
            latitude:
                customer?.latitude != null ? String(customer.latitude) : "",
            longitude:
                customer?.longitude != null ? String(customer.longitude) : "",
            is_active: customer ? (customer.is_active ? "1" : "0") : "1",
            is_visible_in_pos: customer?.is_visible_in_pos ?? true,
            is_visible_in_marketing: customer?.is_visible_in_marketing ?? true,
            marketer_ids: (customer?.marketer_ids as string[] | null) ?? [],
        })),
    );

    function backToList() {
        router.visit("/customers");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        if (isEdit && customer) {
            $form.put(`/customers/${customer.id}`, {
                onSuccess: () => {
                    router.visit("/customers");
                },
                preserveScroll: true,
            });
        } else {
            $form.post("/customers", {
                onSuccess: () => {
                    router.visit("/customers");
                },
                preserveScroll: true,
            });
        }
    }

    let mapInitError = $state<string | null>(null);
    let mapRef: any = null;
    let customerMarker: any = null;
    let centerMarker: any = null;
    let mapReady = $state(false);
    let searchQuery = $state<string>("");
    let searchResults = $state<
        {
            title: string;
            address: string;
            lat: number;
            lon: number;
        }[]
    >([]);
    let searching = $state(false);
    let searchError = $state<string | null>(null);
    let searchTimer: ReturnType<typeof setTimeout> | null = null;
    function initMap() {
        mapInitError = null;
        const base =
            geofence?.tomtom_sdk_base &&
            typeof geofence.tomtom_sdk_base === "string" &&
            geofence.tomtom_sdk_base.trim() !== ""
                ? geofence.tomtom_sdk_base
                : "https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.5.0";
        const cssId = "tt-map-css-customer";
        if (!document.getElementById(cssId)) {
            const link = document.createElement("link");
            link.id = cssId;
            link.rel = "stylesheet";
            link.href = `${base}/maps/maps.css`;
            document.head.appendChild(link);
        }
        const jsId = "tt-map-js-customer";
        if (!document.getElementById(jsId)) {
            const script = document.createElement("script");
            script.id = jsId;
            script.src = `${base}/maps/maps-web.min.js`;
            script.async = true;
            script.onload = () => {
                renderMap();
            };
            script.onerror = () => {
                mapInitError = "Gagal memuat SDK TomTom.";
            };
            document.body.appendChild(script);
        } else {
            renderMap();
        }
    }
    function renderMap() {
        const tt = (window as any).tt;
        if (!tt) {
            mapInitError = "SDK TomTom belum tersedia.";
            return;
        }
        const container = document.getElementById("customer-map");
        if (!container) {
            mapInitError = "Elemen peta tidak ditemukan.";
            return;
        }
        if (!mapRef) {
            mapRef = tt.map({
                key: geofence?.tomtom_key ?? "",
                container,
                center: [
                    (customer?.longitude ?? geofence.center_long) as number,
                    (customer?.latitude ?? geofence.center_lat) as number,
                ],
                zoom: 14,
            });
            mapRef.on("click", (e: any) => {
                const lngLat = e?.lngLat;
                if (!lngLat) return;
                $form.latitude = String(lngLat.lat ?? "");
                $form.longitude = String(lngLat.lng ?? "");
                placeCustomerMarker();
            });
        }
        if (!centerMarker) {
            centerMarker = new tt.Marker({ color: "#ef4444" })
                .setLngLat([
                    geofence.center_long as number,
                    geofence.center_lat as number,
                ])
                .addTo(mapRef);
        }
        placeCustomerMarker();
        mapReady = true;
    }
    function placeCustomerMarker() {
        const tt = (window as any).tt;
        if (!tt || !mapRef) return;
        const lat = $form.latitude ? Number($form.latitude) : null;
        const lng = $form.longitude ? Number($form.longitude) : null;
        const hasCoords =
            lat !== null &&
            !Number.isNaN(lat) &&
            lng !== null &&
            !Number.isNaN(lng);
        const targetLngLat = hasCoords
            ? [lng as number, lat as number]
            : [
                  (customer?.longitude ?? geofence.center_long) as number,
                  (customer?.latitude ?? geofence.center_lat) as number,
              ];
        if (!customerMarker) {
            customerMarker = new tt.Marker({ color: "#3b82f6" })
                .setLngLat(targetLngLat as [number, number])
                .addTo(mapRef);
        } else {
            customerMarker.setLngLat(targetLngLat as [number, number]);
        }
        mapRef.setCenter(targetLngLat as [number, number]);
    }
    function useMyLocation() {
        if (!navigator.geolocation) {
            mapInitError = "Perangkat tidak mendukung Geolocation.";
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                $form.latitude = String(lat);
                $form.longitude = String(lng);
                placeCustomerMarker();
            },
            () => {
                mapInitError = "Gagal mendapatkan lokasi.";
            },
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 },
        );
    }
    async function performSearch(query: string) {
        searchError = null;
        searchResults = [];
        const q = query.trim();
        if (q === "" || !geofence?.tomtom_key) {
            return;
        }
        searching = true;
        try {
            const url =
                "https://api.tomtom.com/search/2/search/" +
                encodeURIComponent(q) +
                ".json?key=" +
                encodeURIComponent(geofence.tomtom_key) +
                "&limit=7&countrySet=ID&lat=" +
                encodeURIComponent(String(geofence.center_lat)) +
                "&lon=" +
                encodeURIComponent(String(geofence.center_long));
            const res = await fetch(url);
            if (!res.ok) {
                throw new Error("Permintaan pencarian gagal.");
            }
            const data = await res.json();
            const rows = Array.isArray(data?.results) ? data.results : [];
            searchResults = rows
                .map((r: any) => {
                    const pos = r?.position ?? {};
                    const addr = r?.address ?? {};
                    const title =
                        typeof r?.poi?.name === "string"
                            ? r.poi.name
                            : typeof addr?.streetName === "string"
                              ? addr.streetName
                              : typeof addr?.freeformAddress === "string"
                                ? addr.freeformAddress
                                : "Tidak dikenal";
                    const freeform =
                        typeof addr?.freeformAddress === "string"
                            ? addr.freeformAddress
                            : title;
                    const lat = Number(pos?.lat ?? NaN);
                    const lon = Number(pos?.lon ?? NaN);
                    if (Number.isNaN(lat) || Number.isNaN(lon)) {
                        return null;
                    }
                    return {
                        title: String(title),
                        address: String(freeform),
                        lat,
                        lon,
                    };
                })
                .filter(Boolean);
        } catch (e) {
            searchError = "Gagal mencari alamat.";
        } finally {
            searching = false;
        }
    }
    function searchChanged(e: Event | { value: string }) {
        const value =
            (e as any)?.value ?? (e as any)?.target?.value ?? searchQuery ?? "";
        searchQuery = String(value);
        if (searchTimer) {
            clearTimeout(searchTimer);
            searchTimer = null;
        }
        searchTimer = setTimeout(() => {
            performSearch(searchQuery);
        }, 400);
    }
    function pickSearchResult(r: {
        title: string;
        address: string;
        lat: number;
        lon: number;
    }) {
        $form.latitude = String(r.lat);
        $form.longitude = String(r.lon);
        if (!$form.address || $form.address.trim() === "") {
            $form.address = r.address;
        }
        placeCustomerMarker();
    }
    $effect.pre(() => {
        queueMicrotask(() => initMap());
    });
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Pelanggan | {siteName(
            $page.props.settings,
        )}</title
    >
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEdit ? "Edit" : "Tambah"} Pelanggan
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Perbarui informasi pelanggan"
                    : "Tambahkan pelanggan baru"}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant={isEdit ? "warning" : "success"}
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="customer-form"
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Pelanggan
            </Button>
        </div>
    </header>

    <form id="customer-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card title="Informasi Pelanggan" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama"
                                placeholder="Nama lengkap"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />
                            <TextInput
                                id="email"
                                name="email"
                                label="Email"
                                type="email"
                                placeholder="email@domain.com"
                                bind:value={$form.email}
                                error={$form.errors.email}
                                required
                            />
                            <TextInput
                                id="phone"
                                name="phone"
                                label="Nomor Telepon"
                                placeholder="08xxxxxxxxxx"
                                bind:value={$form.phone}
                                error={$form.errors.phone}
                            />
                            <Select
                                id="is_active"
                                name="is_active"
                                label="Status"
                                bind:value={$form.is_active}
                                error={$form.errors.is_active}
                                options={[
                                    { value: "1", label: "Aktif" },
                                    { value: "0", label: "Tidak Aktif" },
                                ]}
                            />
                            <div class="md:col-span-2">
                                <TextArea
                                    id="address"
                                    name="address"
                                    label="Alamat"
                                    placeholder="Alamat lengkap"
                                    error={$form.errors.address}
                                    bind:value={$form.address}
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>
                <Card title="Visibilitas" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Checkbox
                                id="is_visible_in_pos"
                                name="is_visible_in_pos"
                                label="Tampil di POS"
                                bind:checked={
                                    $form.is_visible_in_pos as boolean
                                }
                            />
                            <Checkbox
                                id="is_visible_in_marketing"
                                name="is_visible_in_marketing"
                                label="Tampil di Marketing"
                                bind:checked={
                                    $form.is_visible_in_marketing as boolean
                                }
                            />
                        </div>
                    {/snippet}
                </Card>
                <Card title="Lokasi" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TextInput
                                id="search_address"
                                name="search_address"
                                label="Cari Alamat"
                                placeholder="Masukkan alamat atau tempat"
                                value={searchQuery}
                                oninput={searchChanged}
                                rightButton={{
                                    id: "btn-search",
                                    text: "Cari",
                                    icon: "fa-solid fa-magnifying-glass",
                                    variant: "secondary",
                                    size: "sm",
                                    onclick: () => performSearch(searchQuery),
                                }}
                            />
                            <div
                                class="rounded border border-gray-200 dark:border-[#212121] p-2 md:col-span-1"
                                role="listbox"
                                aria-label="Hasil pencarian alamat"
                            >
                                {#if searching}
                                    <div
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Mencari...
                                    </div>
                                {:else if searchError}
                                    <div class="text-sm text-red-600">
                                        {searchError}
                                    </div>
                                {:else if searchResults.length === 0}
                                    <div
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Tidak ada hasil.
                                    </div>
                                {:else}
                                    <ul class="space-y-2">
                                        {#each searchResults as r, i}
                                            <li>
                                                <button
                                                    type="button"
                                                    class="w-full text-left p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800"
                                                    onclick={() =>
                                                        pickSearchResult(r)}
                                                >
                                                    <div
                                                        class="text-sm font-medium text-gray-900 dark:text-white"
                                                    >
                                                        {r.title}
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-600 dark:text-gray-400"
                                                    >
                                                        {r.address}
                                                    </div>
                                                </button>
                                            </li>
                                        {/each}
                                    </ul>
                                {/if}
                            </div>
                            <TextInput
                                id="latitude"
                                name="latitude"
                                label="Latitude"
                                placeholder="-6.200000"
                                bind:value={$form.latitude}
                                error={$form.errors.latitude}
                                rightButton={{
                                    id: "btn-use-my-location",
                                    text: "Gunakan Lokasi Saya",
                                    icon: "fa-solid fa-location-crosshairs",
                                    variant: "secondary",
                                    size: "sm",
                                    onclick: useMyLocation,
                                }}
                            />
                            <TextInput
                                id="longitude"
                                name="longitude"
                                label="Longitude"
                                placeholder="106.816666"
                                bind:value={$form.longitude}
                                error={$form.errors.longitude}
                            />
                            <div class="md:col-span-2">
                                {#if mapInitError}
                                    <div class="text-sm text-red-600">
                                        {mapInitError}
                                    </div>
                                {:else}
                                    <div
                                        id="customer-map"
                                        class="h-90 w-full rounded-lg border border-gray-200 dark:border-[#212121]"
                                        role="region"
                                        aria-label="Peta lokasi customer"
                                    ></div>
                                {/if}
                            </div>
                        </div>
                    {/snippet}
                </Card>
                <Card title="Assign ke Marketer" collapsible={false}>
                    {#snippet children()}
                        {#if marketers.length === 0}
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Tidak ada marketer yang tersedia.
                            </p>
                        {:else}
                            <div
                                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {#each marketers as m}
                                    <label
                                        class="flex items-center gap-3 p-2 rounded border border-gray-200 dark:border-[#212121] hover:bg-gray-50 dark:hover:bg-gray-800"
                                    >
                                        <input
                                            type="checkbox"
                                            value={m.id}
                                            checked={(
                                                $form.marketer_ids as string[]
                                            ).includes(m.id)}
                                            onchange={(e) => {
                                                const checked = (
                                                    e.target as HTMLInputElement
                                                ).checked;
                                                const id = m.id;
                                                const current =
                                                    ($form.marketer_ids as string[]) ??
                                                    [];
                                                if (checked) {
                                                    $form.marketer_ids = [
                                                        ...current,
                                                        id,
                                                    ];
                                                } else {
                                                    $form.marketer_ids =
                                                        current.filter(
                                                            (x) => x !== id,
                                                        );
                                                }
                                            }}
                                            class="w-4 h-4 rounded border-gray-300 text-[#0060B2] focus:ring-[#0060B2]"
                                        />
                                        <span
                                            class="text-sm text-gray-900 dark:text-white"
                                            >{m.name}</span
                                        >
                                    </label>
                                {/each}
                            </div>
                            {#if $form.errors.marketer_ids}
                                <div class="mt-1 text-xs text-red-600">
                                    {$form.errors.marketer_ids}
                                </div>
                            {/if}
                        {/if}
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
