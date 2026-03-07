<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack, onMount, onDestroy } from "svelte";
    import debounce from "lodash-es/debounce";

    interface PickUpPoint {
        id: string;
        name: string;
        address: string;
        latitude: number;
        longitude: number;
        description: string | null;
        is_active: boolean;
        created_at: string;
        updated_at: string;
    }

    interface PickUpPointOfficer {
        id: string;
        name: string;
        pick_up_point_id: string | null;
    }

    let pickUpPoint = $derived(
        ($page.props.pickUpPoint as { data: PickUpPoint } | null)?.data ?? null,
    );
    let tomtomApiKey = $derived($page.props.tomtomApiKey as string);
    let defaultCenter = $derived(
        $page.props.defaultCenter as { lat: number; lng: number },
    );
    let assignedOfficerIds = $derived(
        ($page.props.assignedOfficerIds as string[]) ?? [],
    );
    let officers = $derived(
        ($page.props.officers as PickUpPointOfficer[]) ?? [],
    );

    let isEditMode = $derived(!!pickUpPoint);

    // Default configuration for form initialization
    const DEFAULT_FORM_STATE = {
        _method: "post",
        name: "",
        address: "",
        latitude: 0,
        longitude: 0,
        description: "",
        is_active: true,
        officer_ids: [] as string[],
    };

    const form = useForm(
        untrack(() => ({
            _method: pickUpPoint ? "put" : "post",
            name: pickUpPoint?.name ?? DEFAULT_FORM_STATE.name,
            address: pickUpPoint?.address ?? DEFAULT_FORM_STATE.address,
            latitude:
                pickUpPoint?.latitude ??
                defaultCenter?.lat ??
                DEFAULT_FORM_STATE.latitude,
            longitude:
                pickUpPoint?.longitude ??
                defaultCenter?.lng ??
                DEFAULT_FORM_STATE.longitude,
            description:
                pickUpPoint?.description ?? DEFAULT_FORM_STATE.description,
            is_active: pickUpPoint?.is_active ?? DEFAULT_FORM_STATE.is_active,
            officer_ids: assignedOfficerIds,
        })),
    );

    // Map Implementation
    let mapContainer: HTMLElement;
    let map: any = null;
    let marker: any = null;
    let mapLoaded = $state(false);

    let searchLocationResults = $state<any[]>([]);

    const searchLocation = debounce(async (query: string) => {
        if (!query || query.length < 3) {
            searchLocationResults = [];
            return;
        }

        try {
            const response = await fetch(
                `https://api.tomtom.com/search/2/search/${encodeURIComponent(
                    query,
                )}.json?key=${tomtomApiKey}&limit=5&countrySet=ID`,
            );
            const data = await response.json();
            searchLocationResults = data.results || [];
        } catch (error) {
            console.error("Search failed", error);
            searchLocationResults = [];
        }
    }, 500);

    function handleAddressInput(e: Event) {
        const target = e.target as HTMLTextAreaElement;
        searchLocation(target.value);
    }

    // Effect to update map when inputs change manually
    $effect(() => {
        const lat = Number($form.latitude);
        const lng = Number($form.longitude);
        if (!isNaN(lat) && !isNaN(lng) && map && marker) {
            const newCenter = [lng, lat];
            marker.setLngLat(newCenter);
            map.flyTo({ center: newCenter });
        }
    });

    function selectLocation(result: any) {
        if (result.position) {
            searchLocationResults = [];
            searchLocation.cancel();

            $form.latitude = result.position.lat;
            $form.longitude = result.position.lon;
            $form.address = result.address.freeformAddress;
        }
    }

    onMount(() => {
        if (typeof window !== "undefined" && (window as any).tt) {
            initMap();
        } else {
            const script = document.createElement("script");
            script.src =
                "https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.25.0/maps/maps-web.min.js";
            script.onload = () => {
                initMap();
            };
            document.head.appendChild(script);
        }
    });

    onDestroy(() => {
        if (map) {
            map.remove();
        }
    });

    function initMap() {
        if (!tomtomApiKey) return;

        const center = [$form.longitude, $form.latitude];

        map = (window as any).tt.map({
            key: tomtomApiKey,
            container: mapContainer,
            center: center,
            zoom: 15,
        });

        map.addControl(new (window as any).tt.NavigationControl());

        marker = new (window as any).tt.Marker({ draggable: true })
            .setLngLat(center)
            .addTo(map);

        marker.on("dragend", () => {
            const lngLat = marker?.getLngLat();
            if (lngLat) {
                $form.longitude = lngLat.lng;
                $form.latitude = lngLat.lat;
            }
        });

        map.on("click", (e: any) => {
            const lngLat = e.lngLat;
            if (marker) {
                marker.setLngLat(lngLat);
            }
            $form.longitude = lngLat.lng;
            $form.latitude = lngLat.lat;
        });

        mapLoaded = true;
    }

    function backToIndex() {
        router.visit("/admin/pick-up-points");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        $form.latitude = Number($form.latitude);
        $form.longitude = Number($form.longitude);

        if (isEditMode && pickUpPoint) {
            $form.post(`/admin/pick-up-points/${pickUpPoint.id}`, {
                preserveScroll: true,
            });
        } else {
            $form.post("/admin/pick-up-points", {
                preserveScroll: true,
            });
        }
    }
    function toggleOfficer(id: string) {
        if ($form.officer_ids.includes(id)) {
            $form.officer_ids = $form.officer_ids.filter((i) => i !== id);
        } else {
            $form.officer_ids = [...$form.officer_ids, id];
        }
    }
</script>

<svelte:head>
    <title
        >{isEditMode ? "Edit" : "Tambah"} Pick Up Point | {getSettingName(
            $page.props.settings,
        )}</title
    >
    <link
        rel="stylesheet"
        href="https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.25.0/maps/maps.css"
    />
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEditMode ? "Edit Pick Up Point" : "Tambah Pick Up Point"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk {isEditMode
                    ? "mengubah"
                    : "menambahkan"} lokasi pick up point
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToIndex}>Kembali</Button
            >
            <Button
                variant="success"
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="pick-up-point-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="pick-up-point-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-6">
                <Card title="Informasi Umum" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama Lokasi"
                                placeholder="Contoh: Lokasi Pusat"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />

                            <div class="relative">
                                <TextArea
                                    id="address"
                                    name="address"
                                    label="Alamat Lengkap"
                                    placeholder="Jelaskan detail alamat bangunan"
                                    bind:value={$form.address}
                                    oninput={handleAddressInput}
                                    error={$form.errors.address}
                                    rows={3}
                                    required
                                />
                                {#if searchLocationResults.length > 0}
                                    <ul
                                        class="absolute z-60 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-auto"
                                    >
                                        {#each searchLocationResults as result}
                                            <li>
                                                <button
                                                    type="button"
                                                    class="w-full text-left px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none border-b border-gray-50 dark:border-gray-700 last:border-0 transition-colors"
                                                    onclick={() =>
                                                        selectLocation(result)}
                                                >
                                                    <div
                                                        class="text-sm font-bold text-gray-900 dark:text-white"
                                                    >
                                                        {result.poi
                                                            ? result.poi.name
                                                            : result.address
                                                                  .streetName ||
                                                              result.address
                                                                  .freeformAddress}
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                                    >
                                                        {result.address
                                                            .freeformAddress}
                                                    </div>
                                                </button>
                                            </li>
                                        {/each}
                                    </ul>
                                {/if}
                            </div>

                            <TextArea
                                id="description"
                                name="description"
                                label="Keterangan / Patokan Lokasi (Opsional)"
                                placeholder="Contoh: Pagar hitam depan gang"
                                bind:value={$form.description}
                                error={$form.errors.description}
                                rows={2}
                            />

                            <div class="flex items-center pt-2">
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    label="Aktif"
                                    bind:checked={$form.is_active}
                                    error={$form.errors.is_active}
                                />
                                <span
                                    class="ml-2 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    (Pick up point bisa dinonaktifkan sementara)
                                </span>
                            </div>
                        </div>
                    {/snippet}
                </Card>
                <Card title="Pick Up Point Officer (PIC)" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            {#if officers.length > 0}
                                <div>
                                    <span
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                    >
                                        Pilih Officer (Bisa lebih dari satu)
                                    </span>
                                    <div
                                        class="mt-2 space-y-2 max-h-48 overflow-y-auto p-3 border border-gray-200 dark:border-gray-700 rounded-md"
                                    >
                                        {#each officers as pkOfficer}
                                            <div class="flex items-center">
                                                <Checkbox
                                                    id={`officer-${pkOfficer.id}`}
                                                    name={`officer-${pkOfficer.id}`}
                                                    checked={$form.officer_ids.includes(
                                                        pkOfficer.id,
                                                    )}
                                                    onchange={() =>
                                                        toggleOfficer(
                                                            pkOfficer.id,
                                                        )}
                                                />
                                                <!-- svelte-ignore a11y_label_has_associated_control -->
                                                <label
                                                    for={`officer-${pkOfficer.id}`}
                                                    class="ml-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none truncate max-w-full"
                                                >
                                                    {pkOfficer.name}
                                                    {#if pkOfficer.pick_up_point_id && pkOfficer.pick_up_point_id !== pickUpPoint?.id}
                                                        <span
                                                            class="text-xs text-orange-500 ml-1"
                                                            >(Bakal dipindahkan
                                                            dari tempat lain)</span
                                                        >
                                                    {/if}
                                                </label>
                                            </div>
                                        {/each}
                                    </div>
                                    {#if $form.errors.officer_ids}
                                        <p
                                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                                        >
                                            {$form.errors.officer_ids}
                                        </p>
                                    {/if}
                                </div>
                            {:else}
                                <div
                                    class="text-sm text-gray-500 italic p-3 bg-gray-50 dark:bg-gray-800 rounded-md"
                                >
                                    Belum ada data Officer. Silakan tambahkan
                                    Pick Up Point Officer terlebih dahulu.
                                </div>
                            {/if}
                        </div>
                    {/snippet}
                </Card>
            </div>

            <div class="space-y-6">
                <!-- Location map logic here -->
                <Card
                    title="Titik Koordinat Peta"
                    collapsible={false}
                    class="h-full"
                >
                    {#snippet children()}
                        <div class="space-y-4 h-full flex flex-col">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Geser pin atau klik peta untuk menentukan titik
                                koordinat yang akurat. Ketik alamat di form
                                sebelah kiri untuk mencari koordinat otomatis.
                            </p>

                            <div
                                class="w-full h-80 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-800"
                                bind:this={mapContainer}
                            ></div>

                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <TextInput
                                    id="latitude"
                                    name="latitude"
                                    label="Latitude"
                                    value={$form.latitude.toString()}
                                    oninput={(e) => {
                                        if (
                                            e &&
                                            typeof e === "object" &&
                                            "target" in e
                                        ) {
                                            $form.latitude = Number(
                                                (e.target as HTMLInputElement)
                                                    .value,
                                            );
                                        }
                                    }}
                                    error={$form.errors.latitude}
                                    required
                                />

                                <TextInput
                                    id="longitude"
                                    name="longitude"
                                    label="Longitude"
                                    value={$form.longitude.toString()}
                                    oninput={(e) => {
                                        if (
                                            e &&
                                            typeof e === "object" &&
                                            "target" in e
                                        ) {
                                            $form.longitude = Number(
                                                (e.target as HTMLInputElement)
                                                    .value,
                                            );
                                        }
                                    }}
                                    error={$form.errors.longitude}
                                    required
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>

<style>
    /* Ensure TomTom controls don't conflict with sidebar */
    :global(.mapboxgl-ctrl-bottom-right) {
        z-index: 10;
    }
</style>
