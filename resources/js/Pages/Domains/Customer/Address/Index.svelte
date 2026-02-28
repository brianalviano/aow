<script lang="ts">
    import { useForm, Link, page } from "@inertiajs/svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import { onMount, onDestroy } from "svelte";
    import debounce from "lodash-es/debounce";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";

    let tomtomApiKey = $derived($page.props.tomtomApiKey as string);
    let defaultCenter = $derived(
        $page.props.defaultCenter as { lat: number; lng: number },
    );
    let isAuthenticated = $derived($page.props.isAuthenticated as boolean);
    let savedAddresses = $derived($page.props.savedAddresses as any[]);

    // Initialize the form with empty strings
    const form = useForm({
        register_name: "",
        register_phone: "",
        name: "",
        phone: "",
        address: "",
        note: "",
        latitude: null as number | null,
        longitude: null as number | null,
        email: "",
        password: "",
        password_confirmation: "",
    });

    function useAccountName() {
        $form.name = $form.register_name;
    }

    function useAccountPhone() {
        $form.phone = $form.register_phone;
    }

    let editingId = $state<string | null>(null);
    let showDeleteDialog = $state(false);
    let addressToDelete = $state<any>(null);

    // Map Implementation
    let mapContainer: HTMLElement;
    let map: any = null;
    let marker: any = null;
    let mapLoaded = $state(false);
    let isLocating = $state(false);

    let searchLocationResults = $state<any[]>([]);

    async function reverseGeocode(lat: number, lng: number) {
        try {
            const response = await fetch(
                `https://api.tomtom.com/search/2/reverseGeocode/${lat},${lng}.json?key=${tomtomApiKey}`,
            );
            const data = await response.json();
            if (data.addresses && data.addresses.length > 0) {
                $form.address = data.addresses[0].address.freeformAddress;
            }
        } catch (error) {
            console.error("Reverse geocoding failed", error);
        }
    }

    function getCurrentLocation() {
        if (!navigator.geolocation) {
            alert("Geolocation tidak didukung oleh browser Anda.");
            return;
        }

        isLocating = true;
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const { latitude, longitude } = position.coords;
                $form.latitude = latitude;
                $form.longitude = longitude;

                if (map && marker) {
                    const newCenter = [longitude, latitude];
                    marker.setLngLat(newCenter);
                    map.flyTo({ center: newCenter, zoom: 17 });
                }

                await reverseGeocode(latitude, longitude);
                isLocating = false;
            },
            (error) => {
                console.error("Error getting location", error);
                isLocating = false;
                let message = "Gagal mendapatkan lokasi.";
                if (error.code === error.PERMISSION_DENIED) {
                    message = "Izin lokasi ditolak oleh pengguna.";
                }
                alert(message);
            },
            { enableHighAccuracy: true },
        );
    }

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

    function selectLocation(result: any) {
        if (result.position) {
            searchLocationResults = [];
            searchLocation.cancel();

            $form.latitude = result.position.lat;
            $form.longitude = result.position.lon;
            $form.address = result.address.freeformAddress;

            if (map && marker) {
                const newCenter = [result.position.lon, result.position.lat];
                marker.setLngLat(newCenter);
                map.flyTo({ center: newCenter, zoom: 17 });
            }
        }
    }

    onMount(() => {
        // Use defaultCenter for initial coordinates if form is empty
        if (!$form.latitude && defaultCenter?.lat) {
            $form.latitude = defaultCenter.lat;
        }
        if (!$form.longitude && defaultCenter?.lng) {
            $form.longitude = defaultCenter.lng;
        }

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

        const center = [$form.longitude || 0, $form.latitude || 0];

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
                // No reverse geocoding here to prevent overwriting manual address
            }
        });

        map.on("click", (e: any) => {
            const lngLat = e.lngLat;
            if (marker) {
                marker.setLngLat(lngLat);
            }
            $form.longitude = lngLat.lng;
            $form.latitude = lngLat.lat;
            // No reverse geocoding here to prevent overwriting manual address
        });

        mapLoaded = true;
    }

    function handleSelectAddress(address: any) {
        $form.name = address.name;
        $form.phone = address.phone;
        $form.address = address.address;
        $form.note = address.note || "";
        $form.latitude = address.latitude;
        $form.longitude = address.longitude;
        editingId = address.id;

        if (map && marker) {
            const newCenter = [address.longitude, address.latitude];
            marker.setLngLat(newCenter);
            map.flyTo({ center: newCenter, zoom: 17 });
        }

        // Scroll to form
        const formElement = document.querySelector("form");
        if (formElement) {
            formElement.scrollIntoView({ behavior: "smooth" });
        }
    }

    function handleEditAddress(address: any) {
        handleSelectAddress(address);
    }

    function resetForm() {
        $form.reset();
        editingId = null;
        if (defaultCenter) {
            $form.latitude = defaultCenter.lat;
            $form.longitude = defaultCenter.lng;
            if (map && marker) {
                const newCenter = [defaultCenter.lng, defaultCenter.lat];
                marker.setLngLat(newCenter);
                map.flyTo({ center: newCenter, zoom: 15 });
            }
        }
    }

    function handleDeleteAddress(address: any) {
        addressToDelete = address;
        showDeleteDialog = true;
    }

    function confirmDelete() {
        if (addressToDelete) {
            $form.delete(`/custom-address/${addressToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    showDeleteDialog = false;
                    addressToDelete = null;
                },
            });
        }
    }

    // Handle form submission
    const submit = (e: SubmitEvent) => {
        e.preventDefault();

        if (editingId) {
            $form.put(`/custom-address/${editingId}`, {
                preserveScroll: true,
                onError: (errors: Record<string, string>) => {
                    console.error("Validation errors:", errors);
                },
            });
        } else {
            $form.post("/custom-address", {
                preserveScroll: true,
                onError: (errors: Record<string, string>) => {
                    console.error("Validation errors:", errors);
                },
            });
        }
    };
</script>

<svelte:head>
    <title>Gunakan Alamat Lain</title>
    <link
        rel="stylesheet"
        href="https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.25.0/maps/maps.css"
    />
</svelte:head>

<div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header
        class="bg-white border-b border-gray-100 p-4 sticky top-0 z-30 flex items-center justify-between"
    >
        <div class="flex items-center gap-3">
            <Link
                href="/"
                class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors"
                aria-label="Kembali ke Beranda"
            >
                <i class="fa-solid fa-arrow-left"></i>
            </Link>
            <div>
                <h1 class="font-bold text-gray-900 text-lg leading-tight">
                    Alamat Pengiriman
                </h1>
                <p class="text-xs text-gray-500">Isi detail alamat Anda</p>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 px-4 w-full max-w-lg mx-auto pb-5 pt-3">
        {#if isAuthenticated && savedAddresses.length > 0}
            <div class="space-y-4 mb-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-gray-900">Alamat Tersimpan</h2>
                    {#if editingId}
                        <button
                            type="button"
                            onclick={resetForm}
                            class="text-xs font-bold text-blue-600 hover:text-blue-700"
                        >
                            <i class="fa-solid fa-plus mr-1"></i> Tambah Baru
                        </button>
                    {/if}
                </div>

                <div class="grid grid-cols-1 gap-3">
                    {#each savedAddresses as address}
                        <div
                            class="bg-white p-4 rounded-2xl border {editingId ===
                            address.id
                                ? 'border-[#FFD700] ring-1 ring-[#FFD700]'
                                : 'border-gray-100'} shadow-sm relative group"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-sm">
                                        {address.name}
                                    </h3>
                                    <p class="text-xs text-gray-500">
                                        {address.phone}
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        onclick={() =>
                                            handleEditAddress(address)}
                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-50 text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                                        title="Edit Alamat"
                                    >
                                        <i
                                            class="fa-solid fa-pen-to-square text-xs"
                                        ></i>
                                    </button>
                                    <button
                                        type="button"
                                        onclick={() =>
                                            handleDeleteAddress(address)}
                                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-50 text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors"
                                        title="Hapus Alamat"
                                    >
                                        <i class="fa-solid fa-trash-can text-xs"
                                        ></i>
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-gray-600 line-clamp-2 mb-3">
                                {address.address}
                            </p>
                            <button
                                type="button"
                                onclick={() => handleSelectAddress(address)}
                                class="w-full py-2 px-4 rounded-xl text-xs font-bold transition-all {editingId ===
                                address.id
                                    ? 'bg-gray-100 text-gray-400 cursor-default'
                                    : 'bg-blue-50 text-blue-600 hover:bg-blue-100'}"
                                disabled={editingId === address.id}
                            >
                                {editingId === address.id
                                    ? "Sedang Diedit"
                                    : "Gunakan Alamat Ini"}
                            </button>
                        </div>
                    {/each}
                </div>
            </div>
        {/if}

        <form
            onsubmit={submit}
            class="space-y-5 bg-white p-6 rounded-2xl shadow-sm border border-gray-100"
        >
            <!-- Registration Section for Guests -->
            {#if !isAuthenticated && !editingId}
                <div class="space-y-5 pb-6 border-b border-gray-100">
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-gray-900">
                            Buat Akun Baru
                        </h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Lengkapi data di bawah ini untuk mendaftarkan akun
                            Anda.
                        </p>
                    </div>

                    <TextInput
                        id="register_name"
                        name="register_name"
                        label="Nama Lengkap"
                        placeholder="Contoh: Budi Susanto"
                        bind:value={$form.register_name}
                        error={$form.errors.register_name}
                        required
                    />

                    <TextInput
                        id="register_phone"
                        name="register_phone"
                        type="tel"
                        label="Nomor HP"
                        placeholder="Contoh: 081234567890"
                        bind:value={$form.register_phone}
                        error={$form.errors.register_phone}
                        required
                    />

                    <TextInput
                        id="email"
                        name="email"
                        type="email"
                        label="Alamat Email"
                        placeholder="rino@example.com"
                        bind:value={$form.email}
                        error={$form.errors.email}
                        required
                    />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <TextInput
                            id="password"
                            name="password"
                            type="password"
                            label="Kata Sandi"
                            placeholder="••••••••"
                            bind:value={$form.password}
                            error={$form.errors.password}
                            required
                        />

                        <TextInput
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            label="Konfirmasi Kata Sandi"
                            placeholder="••••••••"
                            bind:value={$form.password_confirmation}
                            error={$form.errors.password_confirmation}
                            required
                        />
                    </div>
                </div>
            {/if}

            <div>
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-gray-900">
                        {editingId ? "Edit Alamat" : "Tambah Alamat Baru"}
                    </h2>
                    <p class="text-xs text-gray-500">
                        {editingId
                            ? "Perbarui detail alamat pengiriman Anda"
                            : "Isi detail alamat pengiriman baru"}
                    </p>
                </div>
                <div class="flex items-center justify-between mb-1">
                    <label
                        for="name"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Nama Penerima <span class="text-red-500">*</span>
                    </label>
                    {#if !isAuthenticated && $form.register_name}
                        <button
                            type="button"
                            onclick={useAccountName}
                            class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-2 py-1 rounded-lg"
                        >
                            Gunakan Nama Akun
                        </button>
                    {/if}
                </div>
                <TextInput
                    id="name"
                    name="name"
                    label=""
                    placeholder="Contoh: Budi Susanto"
                    bind:value={$form.name}
                    error={$form.errors.name}
                    required
                />
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label
                        for="phone"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Nomor Telepon/WhatsApp <span class="text-red-500"
                            >*</span
                        >
                    </label>
                    {#if !isAuthenticated && $form.register_phone}
                        <button
                            type="button"
                            onclick={useAccountPhone}
                            class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-2 py-1 rounded-lg"
                        >
                            Gunakan Nomor HP
                        </button>
                    {/if}
                </div>
                <TextInput
                    id="phone"
                    name="phone"
                    type="tel"
                    label=""
                    placeholder="Contoh: 081234567890"
                    bind:value={$form.phone}
                    error={$form.errors.phone}
                    required
                />
            </div>

            <div class="relative">
                <div class="flex items-center justify-between mb-1">
                    <label
                        for="address"
                        class="block text-sm font-medium text-gray-700"
                    >
                        Alamat Lengkap <span class="text-red-500">*</span>
                    </label>
                    <button
                        type="button"
                        onclick={getCurrentLocation}
                        disabled={isLocating}
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-[#0060B2] bg-blue-50 hover:bg-blue-100 transition-colors disabled:opacity-50"
                    >
                        {#if isLocating}
                            <i class="fa-solid fa-spinner fa-spin"></i> Mencari...
                        {:else}
                            <i class="fa-solid fa-location-crosshairs"></i>
                            Lokasi Saat Ini
                        {/if}
                    </button>
                </div>
                <TextArea
                    id="address"
                    name="address"
                    label=""
                    placeholder="Contoh: Jl. Sudirman No. 123, RT 01/RW 02, Patokan: Pagar Hitam"
                    bind:value={$form.address}
                    oninput={handleAddressInput}
                    error={$form.errors.address}
                    rows={3}
                    required
                />
                {#if searchLocationResults.length > 0}
                    <ul
                        class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-auto"
                    >
                        {#each searchLocationResults as result}
                            <li>
                                <button
                                    type="button"
                                    class="w-full text-left px-4 py-3 hover:bg-gray-50 focus:outline-none border-b border-gray-50 last:border-0 transition-colors"
                                    onclick={() => selectLocation(result)}
                                >
                                    <div
                                        class="text-sm font-bold text-gray-900"
                                    >
                                        {result.poi
                                            ? result.poi.name
                                            : result.address.streetName ||
                                              result.address.freeformAddress}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {result.address.freeformAddress}
                                    </div>
                                </button>
                            </li>
                        {/each}
                    </ul>
                {/if}
            </div>

            <TextArea
                id="note"
                name="note"
                label="Catatan untuk Kurir (Opsional)"
                placeholder="Contoh: Titip di pos satpam saja"
                bind:value={$form.note}
                error={$form.errors.note}
                rows={2}
            />

            <!-- Map Section -->
            <div class="space-y-3 pt-4 border-t border-gray-100">
                <span class="block text-sm font-semibold text-gray-700">
                    Titik Lokasi Pengiriman <span class="text-red-500">*</span>
                </span>
                <p class="text-xs text-gray-500">
                    Geser pin atau klik peta untuk menentukan titik pengiriman
                    yang akurat. Ketik alamat di atas untuk mencari koordinat
                    otomatis.
                </p>

                <div
                    class="w-full h-64 rounded-2xl overflow-hidden border border-gray-200 bg-gray-100"
                    bind:this={mapContainer}
                ></div>
            </div>

            <!-- Submit Button -->
            <div class="pt-3">
                <button
                    type="submit"
                    disabled={$form.processing}
                    class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-slate-900 bg-[#FFD700] hover:bg-[#FFC700] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#FFD700] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {#if $form.processing}
                        <i class="fa-solid fa-spinner fa-spin mr-2"></i> Memproses...
                    {:else}
                        {editingId ? "Perbarui &" : "Simpan &"} Lanjut Pilih Menu
                        <i class="fa-solid fa-arrow-right ml-2"></i>
                    {/if}
                </button>
            </div>
        </form>
    </main>

    <Dialog
        bind:isOpen={showDeleteDialog}
        type="danger"
        title="Konfirmasi Hapus"
        message="Apakah Anda yakin ingin menghapus alamat ini? Tindakan ini tidak dapat dibatalkan."
        confirmText="Ya, Hapus"
        cancelText="Batal"
        onConfirm={confirmDelete}
    />
</div>

<style>
    :global(.mapboxgl-ctrl-bottom-right) {
        z-index: 10;
    }
</style>
