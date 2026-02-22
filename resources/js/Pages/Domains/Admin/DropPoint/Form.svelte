<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack, onMount, onDestroy } from "svelte";

    // TomTom Map Types
    type TomTomMap = any;
    type TomTomMarker = any;

    interface DropPoint {
        id: string;
        name: string;
        address: string;
        phone: string;
        latitude: number;
        longitude: number;
        pic_name: string;
        pic_phone: string;
        is_active: boolean;
        delivery_fee: number;
        created_at: string;
        updated_at: string;
    }

    let dropPoint = $derived(
        ($page.props.dropPoint as { data: DropPoint } | null)?.data ?? null,
    );
    let tomtomApiKey = $derived($page.props.tomtomApiKey as string);
    let defaultCenter = $derived(
        $page.props.defaultCenter as { lat: number; lng: number },
    );

    let isEditMode = $derived(!!dropPoint);

    // Default configuration for form initialization
    const DEFAULT_FORM_STATE = {
        _method: "post",
        name: "",
        address: "",
        phone: "",
        latitude: 0,
        longitude: 0,
        pic_name: "",
        pic_phone: "",
        delivery_fee: 0,
        is_active: true,
    };

    const form = useForm(
        untrack(() => ({
            _method: dropPoint ? "put" : "post",
            name: dropPoint?.name ?? DEFAULT_FORM_STATE.name,
            address: dropPoint?.address ?? DEFAULT_FORM_STATE.address,
            phone: dropPoint?.phone ?? DEFAULT_FORM_STATE.phone,
            latitude:
                dropPoint?.latitude ??
                defaultCenter?.lat ??
                DEFAULT_FORM_STATE.latitude,
            longitude:
                dropPoint?.longitude ??
                defaultCenter?.lng ??
                DEFAULT_FORM_STATE.longitude,
            pic_name: dropPoint?.pic_name ?? DEFAULT_FORM_STATE.pic_name,
            pic_phone: dropPoint?.pic_phone ?? DEFAULT_FORM_STATE.pic_phone,
            delivery_fee:
                dropPoint?.delivery_fee ?? DEFAULT_FORM_STATE.delivery_fee,
            is_active: dropPoint?.is_active ?? DEFAULT_FORM_STATE.is_active,
        })),
    );

    // Map Implementation
    let mapContainer: HTMLElement;
    let map: any = null;
    let marker: any = null;
    let mapLoaded = $state(false);

    onMount(() => {
        // Using dynamically injected TomTom scripts from _app context or waiting for window mount.
        // We ensure tt library is available.
        if (typeof window !== "undefined" && (window as any).tt) {
            initMap();
        } else {
            // Load script dynamically if not present (optional, usually provided by layout)
            const script = document.createElement("script");
            script.src =
                "https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.25.0/maps/maps-web.min.js";
            script.onload = () => {
                const css = document.createElement("link");
                css.rel = "stylesheet";
                css.href =
                    "https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.25.0/maps/maps.css";
                document.head.appendChild(css);
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

        mapLoaded = true;
    }

    function backToIndex() {
        router.visit("/admin/drop-points");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        // Convert to standard numbers before submitting just in case
        $form.latitude = Number($form.latitude);
        $form.longitude = Number($form.longitude);

        if (isEditMode && dropPoint) {
            $form.put(`/admin/drop-points/${dropPoint.id}`, {
                preserveScroll: true,
            });
        } else {
            $form.post("/admin/drop-points", {
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEditMode ? "Edit" : "Tambah"} Drop Point | {getSettingName(
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
                {isEditMode ? "Edit Drop Point" : "Tambah Drop Point"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk {isEditMode
                    ? "mengubah"
                    : "menambahkan"} titik jemput
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
                form="drop-point-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="drop-point-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-6">
                <Card title="Informasi Umum" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama Lokasi / Cabang"
                                placeholder="Contoh: Titik Jemput Utama Cabang 1"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />

                            <TextArea
                                id="address"
                                name="address"
                                label="Alamat Lengkap"
                                placeholder="Jelaskan detail alamat titik bangunan"
                                bind:value={$form.address}
                                error={$form.errors.address}
                                rows={3}
                                required
                            />

                            <TextInput
                                id="phone"
                                name="phone"
                                label="Nomor Telepon Tempat (Opsional)"
                                placeholder="Contoh: 021-1234567"
                                bind:value={$form.phone}
                                error={$form.errors.phone}
                                required
                            />

                            <TextInput
                                id="delivery_fee"
                                name="delivery_fee"
                                label="Biaya Pengiriman (Rp)"
                                type="number"
                                placeholder="0"
                                value={$form.delivery_fee.toString()}
                                oninput={(e) => {
                                    if (
                                        e &&
                                        typeof e === "object" &&
                                        "numericValue" in e &&
                                        e.numericValue !== null
                                    ) {
                                        $form.delivery_fee = e.numericValue;
                                    } else if (
                                        e &&
                                        typeof e === "object" &&
                                        "target" in e
                                    ) {
                                        $form.delivery_fee = Number(
                                            (e.target as HTMLInputElement)
                                                .value,
                                        );
                                    }
                                }}
                                error={$form.errors.delivery_fee}
                                required
                            />
                            <p class="text-xs text-gray-500 mt-1">
                                Biaya pengiriman jika pengguna memilih titik
                                ini.
                            </p>

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
                                    (Drop point bisa dinonaktifkan sementara)
                                </span>
                            </div>
                        </div>
                    {/snippet}
                </Card>

                <Card title="Person In Charge (PIC)" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <TextInput
                                id="pic_name"
                                name="pic_name"
                                label="Nama PIC"
                                placeholder="Nama orang yang bertanggung jawab di titik ini"
                                bind:value={$form.pic_name}
                                error={$form.errors.pic_name}
                                required
                            />

                            <TextInput
                                id="pic_phone"
                                name="pic_phone"
                                label="No Whatsapp PIC"
                                placeholder="Contoh: 08123456789"
                                bind:value={$form.pic_phone}
                                error={$form.errors.pic_phone}
                                required
                            />
                        </div>
                    {/snippet}
                </Card>
            </div>

            <div class="space-y-6">
                <!-- Location map logic here -->
                <Card title="Titik Lokasi" collapsible={false} class="h-full">
                    {#snippet children()}
                        <div class="space-y-4 h-full flex flex-col">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Geser pin atau klik pada peta untuk menentukan
                                kordinat titik pengiriman.
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
