<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import FileUpload from "@/Lib/Admin/Components/Ui/FileUpload.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateTimeDisplay } from "@/Lib/Admin/Utils/date";

    type Shift = {
        id: string;
        name: string;
        start_time: string | null;
        end_time: string | null;
        is_overnight: boolean;
        is_off: boolean;
    };
    type Schedule = {
        id: string;
        date: string;
        shift: Shift | null;
    } | null;
    type GeofenceCfg = {
        center_lat: number;
        center_long: number;
        radius_m: number;
        tomtom_key: string;
        tomtom_sdk_base: string;
    };

    let schedule = $derived($page.props.schedule as Schedule);
    let geofence = $derived($page.props.geofence as GeofenceCfg);
    let mode = $derived($page.props.mode as "checkin" | "checkout");
    type AuthProps = { auth?: { user?: { role?: string } } };
    let roleRaw = $derived(
        ($page.props as AuthProps).auth?.user?.role ?? null,
    );

    let step = $state<"map" | "selfie" | "summary">("map");
    const stepOrder = { map: 0, selfie: 1, summary: 2 } as const;

    let currentLat = $state<number | null>(null);
    let currentLong = $state<number | null>(null);
    let distanceM = $derived(
        currentLat !== null && currentLong !== null
            ? haversineMeters(
                  geofence.center_lat,
                  geofence.center_long,
                  currentLat,
                  currentLong,
              )
            : null,
    );

    let videoEl = $state<HTMLVideoElement | null>(null);
    let stream: MediaStream | null = null;
    let capturedBlob: Blob | null = null;
    let capturedUrl = $state<string | null>(null);
    let notes = $state("");
    let cameraError = $state<string | null>(null);

    let submitting = $state(false);
    let mapInitError = $state<string | null>(null);
    let locationError = $state<string | null>(null);
    let accuracyM = $state<number | null>(null);
    let mapRef: any = null;
    let userMarker: any = null;
    let geoWatchId: number | null = null;
    let mapCenteredOnUser = $state(false);

    function parseStartDateTime(): Date | null {
        const d = schedule?.date ?? null;
        const t = schedule?.shift?.start_time ?? null;
        if (!d || !t) return null;
        const dt = new Date(`${d}T${t}`);
        return isNaN(dt.getTime()) ? null : dt;
    }
    type LateInfo = {
        isLate: boolean;
        hours: number;
        minutes: number;
        seconds: number;
        diffMs: number;
    } | null;
    let lateInfo: LateInfo = $derived(
        (() => {
            if (mode !== "checkin") return null;
            const start = parseStartDateTime();
            if (!start) return null;
            const now = new Date();
            const diffMs = now.getTime() - start.getTime();
            const isLate = diffMs > 0;
            const hours = isLate ? Math.floor(diffMs / 3600000) : 0;
            const minutes = isLate ? Math.floor((diffMs % 3600000) / 60000) : 0;
            const seconds = isLate ? Math.floor((diffMs % 60000) / 1000) : 0;
            return { isLate, hours, minutes, seconds, diffMs };
        })(),
    );

    function parseEndDateTime(): Date | null {
        const d = schedule?.date ?? null;
        const t = schedule?.shift?.end_time ?? null;
        if (!d || !t) return null;
        const dt = new Date(`${d}T${t}`);
        if (isNaN(dt.getTime())) return null;

        if (schedule?.shift?.is_overnight) {
            dt.setDate(dt.getDate() + 1);
        }
        return dt;
    }
    type EarlyInfo = {
        isEarly: boolean;
        hours: number;
        minutes: number;
        seconds: number;
        diffMs: number;
    } | null;
    let earlyInfo: EarlyInfo = $derived(
        (() => {
            if (mode !== "checkout") return null;
            const end = parseEndDateTime();
            if (!end) return null;
            const now = new Date();
            const diffMs = end.getTime() - now.getTime();
            const isEarly = diffMs > 0;
            const hours = isEarly ? Math.floor(diffMs / 3600000) : 0;
            const minutes = isEarly
                ? Math.floor((diffMs % 3600000) / 60000)
                : 0;
            const seconds = isEarly ? Math.floor((diffMs % 60000) / 1000) : 0;
            return { isEarly, hours, minutes, seconds, diffMs };
        })(),
    );

    let canCheckoutNow = $derived(
        (() => {
            if (mode !== "checkout") return true;
            const r = roleRaw ? roleRaw.trim().toLowerCase() : null;
            if (r === "manager") return true;
            const end = parseEndDateTime();
            if (!end) return true;
            const allowedStart = new Date(end.getTime() - 15 * 60000);
            const now = new Date();
            return now.getTime() >= allowedStart.getTime();
        })(),
    );

    let notesRequired = $derived(
        (() => {
            const r = roleRaw ? roleRaw.trim().toLowerCase() : null;
            const flexible = r === "manager";
            return (
                (mode === "checkin" &&
                    !!lateInfo &&
                    lateInfo.isLate &&
                    !flexible) ||
                (mode === "checkout" &&
                    !!earlyInfo &&
                    earlyInfo.isEarly &&
                    !flexible)
            );
        })(),
    );
    let notesError = $derived(
        notesRequired && !notes.trim()
            ? mode === "checkin"
                ? "Catatan wajib diisi saat terlambat."
                : "Catatan wajib diisi saat pulang lebih awal."
            : undefined,
    );

    function initMap() {
        mapInitError = null;
        const base =
            geofence?.tomtom_sdk_base &&
            typeof geofence.tomtom_sdk_base === "string" &&
            geofence.tomtom_sdk_base.trim() !== ""
                ? geofence.tomtom_sdk_base
                : "https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.5.0";
        const candidates = [{ name: "cfg", base }];
        let idx = 0;
        const tryLoad = () => {
            if ((window as any).tt) {
                renderMap();
                return;
            }
            if (idx >= candidates.length) {
                mapInitError =
                    "Gagal memuat SDK TomTom dari semua versi kandidat.";
                return;
            }
            const c = candidates[idx]!;
            const cssId = `tt-map-css-${c.name}`;
            if (!document.getElementById(cssId)) {
                const link = document.createElement("link");
                link.id = cssId;
                link.rel = "stylesheet";
                link.href = `${c.base}/maps/maps.css`;
                document.head.appendChild(link);
            }
            const jsId = `tt-map-js-${c.name}`;
            if (!document.getElementById(jsId)) {
                const script = document.createElement("script");
                script.id = jsId;
                script.src = `${c.base}/maps/maps-web.min.js`;
                script.onload = () => renderMap();
                script.onerror = () => {
                    idx++;
                    if (idx >= candidates.length) {
                        mapInitError = `Gagal memuat SDK TomTom (terakhir mencoba ${c.name}).`;
                    }
                    tryLoad();
                };
                document.body.appendChild(script);
            } else {
                idx++;
                tryLoad();
            }
        };
        tryLoad();
    }

    function renderMap() {
        mapInitError = null;
        if (!(window as any).tt) return;
        if (!geofence.tomtom_key) {
            mapInitError = "TomTom API key belum dikonfigurasi.";
            return;
        }
        const containerId = "attendance-map";
        const container = document.getElementById(containerId);
        if (!container) {
            mapInitError = "Kontainer peta tidak ditemukan.";
            return;
        }
        const map = (window as any).tt.map({
            key: geofence.tomtom_key,
            container: containerId,
            center: [geofence.center_long, geofence.center_lat],
            zoom: 16,
        });
        mapRef = map;
        const circle = new (window as any).tt.LngLat(
            geofence.center_long,
            geofence.center_lat,
        );
        const geojson = buildCircleGeoJSON(
            geofence.center_lat,
            geofence.center_long,
            geofence.radius_m,
        );
        map.on("load", () => {
            const srcId = "geofence-src";
            if (!map.getSource(srcId)) {
                map.addSource(srcId, { type: "geojson", data: geojson });
            }
            if (!map.getLayer("geofence-circle")) {
                map.addLayer({
                    id: "geofence-circle",
                    type: "fill",
                    source: srcId,
                    paint: {
                        "fill-color": "#10b981",
                        "fill-opacity": 0.25,
                    },
                });
            }
            new (window as any).tt.Marker({ color: "#ef4444" })
                .setLngLat(circle)
                .addTo(map);
            startGeo();
        });
    }

    function startGeo() {
        locationError = null;
        if (!navigator.geolocation) {
            locationError = "Peramban tidak mendukung geolokasi.";
            return;
        }
        const opts = {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 15000,
        };
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                accuracyM = pos.coords.accuracy ?? null;
                currentLat = pos.coords.latitude;
                currentLong = pos.coords.longitude;
                if (mapRef) {
                    if (!userMarker) {
                        userMarker = new (window as any).tt.Marker({
                            color: "#3b82f6",
                        })
                            .setLngLat([
                                currentLong as number,
                                currentLat as number,
                            ])
                            .addTo(mapRef);
                    } else {
                        userMarker.setLngLat([
                            currentLong as number,
                            currentLat as number,
                        ]);
                    }
                    if (!mapCenteredOnUser) {
                        mapRef.setCenter([
                            currentLong as number,
                            currentLat as number,
                        ]);
                        mapCenteredOnUser = true;
                    }
                }
            },
            (err) => {
                if (err.code === 1) locationError = "Izin lokasi ditolak.";
                else if (err.code === 2)
                    locationError = "Posisi tidak tersedia.";
                else if (err.code === 3)
                    locationError = "Permintaan lokasi kedaluwarsa.";
                else locationError = "Gagal mendapatkan lokasi.";
            },
            opts,
        );
        if (geoWatchId !== null) {
            navigator.geolocation.clearWatch(geoWatchId);
            geoWatchId = null;
        }
        geoWatchId = navigator.geolocation.watchPosition(
            (pos) => {
                accuracyM = pos.coords.accuracy ?? null;
                currentLat = pos.coords.latitude;
                currentLong = pos.coords.longitude;
                if (mapRef && userMarker) {
                    userMarker.setLngLat([
                        currentLong as number,
                        currentLat as number,
                    ]);
                }
                if (
                    mapRef &&
                    !mapCenteredOnUser &&
                    currentLat !== null &&
                    currentLong !== null
                ) {
                    mapRef.setCenter([
                        currentLong as number,
                        currentLat as number,
                    ]);
                    mapCenteredOnUser = true;
                }
            },
            (err) => {
                if (err.code === 1) locationError = "Izin lokasi ditolak.";
                else if (err.code === 2)
                    locationError = "Posisi tidak tersedia.";
                else if (err.code === 3)
                    locationError = "Permintaan lokasi kedaluwarsa.";
                else locationError = "Gagal mendapatkan lokasi.";
            },
            opts,
        );
    }

    function refreshLocation() {
        startGeo();
        if (mapRef && currentLat !== null && currentLong !== null) {
            mapRef.setCenter([currentLong as number, currentLat as number]);
            mapCenteredOnUser = true;
        }
    }

    function buildCircleGeoJSON(lat: number, lon: number, radiusM: number) {
        const points = 64;
        const coords: [number, number][] = [];
        for (let i = 0; i < points; i++) {
            const angle = (i * 360) / points;
            const dest = destinationPoint(lat, lon, radiusM, angle);
            coords.push([dest.lon, dest.lat]);
        }
        if (coords.length > 0) {
            const first: [number, number] = coords[0]!;
            coords.push(first);
        }
        return {
            type: "FeatureCollection",
            features: [
                {
                    type: "Feature",
                    geometry: {
                        type: "Polygon",
                        coordinates: [coords],
                    },
                },
            ],
        };
    }

    function destinationPoint(
        lat: number,
        lon: number,
        distanceM: number,
        bearingDeg: number,
    ) {
        const R = 6371e3;
        const δ = distanceM / R;
        const θ = (bearingDeg * Math.PI) / 180;
        const φ1 = (lat * Math.PI) / 180;
        const λ1 = (lon * Math.PI) / 180;
        const sinφ1 = Math.sin(φ1);
        const cosφ1 = Math.cos(φ1);
        const sinδ = Math.sin(δ);
        const cosδ = Math.cos(δ);
        const sinφ2 = sinφ1 * cosδ + cosφ1 * sinδ * Math.cos(θ);
        const φ2 = Math.asin(sinφ2);
        const y = Math.sin(θ) * sinδ * cosφ1;
        const x = cosδ - sinφ1 * sinφ2;
        const λ2 = λ1 + Math.atan2(y, x);
        return {
            lat: (φ2 * 180) / Math.PI,
            lon: (((λ2 * 180) / Math.PI + 540) % 360) - 180,
        };
    }

    function haversineMeters(
        lat1: number,
        lon1: number,
        lat2: number,
        lon2: number,
    ): number {
        const R = 6371e3;
        const φ1 = (lat1 * Math.PI) / 180;
        const φ2 = (lat2 * Math.PI) / 180;
        const Δφ = ((lat2 - lat1) * Math.PI) / 180;
        const Δλ = ((lon2 - lon1) * Math.PI) / 180;
        const a =
            Math.sin(Δφ / 2) ** 2 +
            Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    async function startCamera() {
        if (stream) return;
        cameraError = null;
        if (!navigator.mediaDevices?.getUserMedia) {
            cameraError = "Peramban tidak mendukung akses kamera.";
            return;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: "user" },
                audio: false,
            });
            if (videoEl && stream) {
                const v = videoEl!;
                v.muted = true;
                v.autoplay = true;
                v.srcObject = stream;
                await new Promise<void>((resolve) => {
                    const ready = () => resolve();
                    if (v.readyState >= 2) {
                        resolve();
                    } else {
                        v.addEventListener("loadedmetadata", ready, {
                            once: true,
                        });
                    }
                });
                await v.play();
            }
        } catch {
            cameraError = "Gagal mengakses kamera. Gunakan unggah foto.";
            stream = null;
        }
    }

    async function capturePhoto() {
        if (!videoEl) return;
        const w = videoEl.videoWidth;
        const h = videoEl.videoHeight;
        const canvas = document.createElement("canvas");
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext("2d");
        if (!ctx) return;
        ctx.drawImage(videoEl, 0, 0, w, h);
        const dataUrl = canvas.toDataURL("image/jpeg", 0.92);
        capturedUrl = dataUrl;
        capturedBlob = await (await fetch(dataUrl)).blob();
        stopCamera();
    }

    function stopCamera() {
        if (stream) {
            for (const track of stream.getTracks()) track.stop();
        }
        stream = null;
        if (videoEl) {
            videoEl.srcObject = null;
        }
    }

    function resetFlow() {
        stopCamera();
        capturedBlob = null;
        capturedUrl = null;
        notes = "";
        step = "map";
    }

    function proceedToSelfie() {
        step = "selfie";
        setTimeout(() => startCamera(), 0);
    }

    async function submitAttendance() {
        if (currentLat === null || currentLong === null || !capturedBlob)
            return;
        if (notesRequired && !notes.trim()) return;
        if (mode === "checkout" && !canCheckoutNow) return;
        submitting = true;
        const fd = new FormData();
        fd.append("lat", String(currentLat));
        fd.append("long", String(currentLong));
        fd.append(
            "photo",
            capturedBlob,
            capturedBlob instanceof File ? capturedBlob.name : "selfie.jpg",
        );
        if (notes.trim()) {
            fd.append("notes", notes.trim());
        }
        const url =
            mode === "checkin" ? "/absents/check-in" : "/absents/check-out";
        await router.post(url, fd, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                submitting = false;
                const toast = ($page.props as any)?.toast as
                    | { type?: string }
                    | undefined;
                if (toast?.type === "success") {
                    resetFlow();
                }
            },
            onError: () => {
                submitting = false;
            },
            onFinish: () => {
                submitting = false;
            },
        });
    }

    $effect(() => {
        initMap();
    });

    $effect(() => {
        if (step !== "selfie") {
            stopCamera();
        }
    });

    $effect(() => {
        const handleInertiaNav = () => {
            stopCamera();
        };
        const handleVisibility = () => {
            if (document.hidden) {
                stopCamera();
            } else {
                if (step === "selfie" && !stream) startCamera();
            }
        };
        const handleFocus = () => {
            if (!document.hidden && step === "selfie" && !stream) startCamera();
        };
        window.addEventListener("inertia:start", handleInertiaNav);
        window.addEventListener("inertia:before", handleInertiaNav);
        document.addEventListener("visibilitychange", handleVisibility);
        window.addEventListener("focus", handleFocus);
        return () => {
            window.removeEventListener("inertia:start", handleInertiaNav);
            window.removeEventListener("inertia:before", handleInertiaNav);
            document.removeEventListener("visibilitychange", handleVisibility);
            window.removeEventListener("focus", handleFocus);
            stopCamera();
        };
    });
</script>

<svelte:head>
    <title>
        {mode === "checkin" ? "Absensi Masuk" : "Absensi Keluar"} |
        {siteName($page.props.settings)}
    </title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {mode === "checkin" ? "Absensi Masuk" : "Absensi Keluar"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Langkah awal melihat peta, konfirmasi lokasi, lalu selfie.
            </p>
        </div>
        <div>
            <div
                class="relative rounded-xl p-3 sm:p-4 bg-white ring-1 ring-gray-200 shadow-sm dark:bg-[#0b0b0b] dark:ring-gray-800"
            >
                <div
                    class="flex flex-wrap sm:flex-nowrap items-center gap-3 sm:gap-4 text-xs sm:text-sm font-semibold"
                >
                    <div
                        class="flex gap-2 items-center pointer-events-none shrink-0"
                    >
                        <div
                            class="h-8 w-8 sm:h-10 sm:w-10 text-xs sm:text-base flex items-center justify-center rounded-full ring-2
                    {step === 'map'
                                ? 'bg-blue-600 text-white ring-blue-300 dark:ring-blue-700'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 ring-gray-300 dark:ring-gray-600'}"
                        >
                            1
                        </div>
                        <span
                            class={stepOrder[step] >= 0
                                ? "text-gray-900 dark:text-white"
                                : "text-gray-500"}>Lokasi</span
                        >
                    </div>
                    <div
                        class="hidden sm:block flex-1 h-px bg-gray-300 dark:bg-gray-700"
                    ></div>
                    <div
                        class="flex gap-2 items-center pointer-events-none shrink-0"
                    >
                        <div
                            class="h-8 w-8 sm:h-10 sm:w-10 text-xs sm:text-base flex items-center justify-center rounded-full ring-2
                    {step === 'selfie'
                                ? 'bg-blue-600 text-white ring-blue-300 dark:ring-blue-700'
                                : stepOrder[step] > 1
                                  ? 'bg-green-600 text-white ring-green-300 dark:ring-green-700'
                                  : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 ring-gray-300 dark:ring-gray-600'}"
                        >
                            2
                        </div>
                        <span
                            class={stepOrder[step] >= 1
                                ? "text-gray-900 dark:text-white"
                                : "text-gray-500"}>Selfie</span
                        >
                    </div>
                    <div
                        class="hidden sm:block flex-1 h-px bg-gray-300 dark:bg-gray-700"
                    ></div>
                    <div
                        class="flex gap-2 items-center pointer-events-none shrink-0"
                    >
                        <div
                            class="h-8 w-8 sm:h-10 sm:w-10 text-xs sm:text-base flex items-center justify-center rounded-full ring-2
                    {step === 'summary'
                                ? 'bg-blue-600 text-white ring-blue-300 dark:ring-blue-700'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 ring-gray-300 dark:ring-gray-600'}"
                        >
                            3
                        </div>
                        <span
                            class={stepOrder[step] >= 2
                                ? "text-gray-900 dark:text-white"
                                : "text-gray-500"}>Ringkasan</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </header>

    {#if step === "map"}
        <Card bodyClass="space-y-5">
            <div
                id="attendance-map"
                class="overflow-hidden w-full bg-gray-100 rounded-2xl ring-1 ring-gray-200 h-92 dark:ring-gray-700 dark:bg-gray-800"
            ></div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                <div
                    class="flex items-center gap-3 rounded-lg bg-gray-50 dark:bg-[#131313] p-3 ring-1 ring-gray-200 dark:ring-gray-800"
                >
                    <i
                        class="text-blue-600 fa-solid fa-location-dot dark:text-blue-500"
                    ></i>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <div class="font-semibold">Koordinat Anda</div>
                        <div>
                            {currentLat?.toFixed(6) ?? "-"}, {currentLong?.toFixed(
                                6,
                            ) ?? "-"}
                        </div>
                    </div>
                </div>
                <div
                    class="flex items-center gap-3 rounded-lg bg-gray-50 dark:bg-[#131313] p-3 ring-1 ring-gray-200 dark:ring-gray-800"
                >
                    <i
                        class="text-emerald-600 fa-solid fa-ruler dark:text-emerald-500"
                    ></i>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <div class="font-semibold">Jarak ke Pusat</div>
                        <div>
                            {distanceM !== null
                                ? `${Math.round(distanceM)} m`
                                : "-"}
                        </div>
                    </div>
                </div>
                <div
                    class="flex items-center gap-3 rounded-lg bg-gray-50 dark:bg-[#131313] p-3 ring-1 ring-gray-200 dark:ring-gray-800"
                >
                    <i
                        class="text-orange-600 fa-solid fa-circle-dot dark:text-orange-500"
                    ></i>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <div class="font-semibold">Radius Area</div>
                        <div>{geofence.radius_m} m</div>
                    </div>
                </div>
                <div
                    class="flex items-center gap-3 rounded-lg bg-gray-50 dark:bg-[#131313] p-3 ring-1 ring-gray-200 dark:ring-gray-800"
                >
                    <i
                        class="text-purple-600 fa-solid fa-shield-halved dark:text-purple-500"
                    ></i>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <div class="font-semibold">Akurasi</div>
                        <div>
                            {accuracyM !== null
                                ? `${Math.round(accuracyM)} m`
                                : "-"}
                        </div>
                    </div>
                </div>
            </div>
        </Card>
        <div class="flex gap-2 justify-end items-center">
            <Button
                variant="primary"
                icon="fa-solid fa-location-crosshairs"
                disabled={currentLat === null ||
                    (mode === "checkout" && !canCheckoutNow)}
                onclick={proceedToSelfie}
            >
                Lokasi sudah sesuai
            </Button>
            <Button
                variant="secondary"
                icon="fa-solid fa-arrows-rotate"
                onclick={refreshLocation}
            >
                Refresh lokasi
            </Button>
        </div>
        <div
            class="mt-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 p-3 ring-1 ring-orange-200 dark:ring-orange-800 text-sm text-orange-700 dark:text-orange-400"
            hidden={!(mode === "checkout" && !canCheckoutNow)}
        >
            Checkout hanya dapat dimulai 15 menit sebelum jam pulang.
        </div>
        <div
            class="mt-3 rounded-lg bg-red-50 dark:bg-red-900/20 p-3 ring-1 ring-red-200 dark:ring-red-800 text-sm text-red-700 dark:text-red-400"
            hidden={!locationError}
        >
            {locationError}
        </div>
    {/if}

    {#if step === "selfie"}
        <div class="space-y-4">
            {#if !cameraError}
                {#if capturedUrl}
                    <div
                        class="group relative aspect-3/4 w-full overflow-hidden rounded-2xl bg-gray-100 ring-1 ring-gray-200 shadow-sm dark:bg-[#111] dark:ring-gray-800"
                    >
                        <img
                            src={capturedUrl}
                            alt="Bukti Selfie"
                            class="object-cover w-full h-full transition duration-500 group-hover:scale-105"
                        />
                        <div
                            class="absolute right-0 bottom-0 left-0 p-4 to-transparent bg-linear-to-t from-black/80"
                        >
                            <p class="text-xs font-medium text-white/90">
                                Bukti Foto
                            </p>
                        </div>
                    </div>
                {:else}
                    <div
                        class="relative rounded-2xl bg-white ring-1 ring-gray-200 shadow-sm overflow-hidden dark:bg-[#0b0b0b] dark:ring-gray-800"
                    >
                        <video
                            bind:this={videoEl}
                            class="w-full bg-black object-cover scale-x-[-1]"
                            playsinline
                            aria-label="Selfie webcam preview"
                        >
                            <track kind="captions" />
                        </video>
                        <div class="absolute inset-0 pointer-events-none">
                            <div
                                class="absolute inset-0 ring-1 ring-white/40"
                            ></div>
                            <div
                                class="absolute top-1/2 left-1/2 w-40 h-40 rounded-full border-2 -translate-x-1/2 -translate-y-1/2 border-white/60"
                            ></div>
                            <div
                                class="absolute inset-0 to-transparent rounded-2xl bg-linear-to-t from-black/25"
                            ></div>
                        </div>
                        <div
                            class="absolute bottom-3 left-3 px-2 py-1 text-xs text-white rounded bg-black/40"
                        >
                            Posisikan wajah di dalam cincin
                        </div>
                    </div>
                {/if}
            {:else}
                <Card bodyClass="space-y-3">
                    <div
                        class="rounded-lg p-3 bg-orange-50 ring-1 ring-orange-200 text-sm text-orange-700 dark:bg-orange-900/20 dark:ring-orange-800 dark:text-orange-400"
                    >
                        Kamera tidak dapat diakses. Unggah foto selfie sebagai
                        alternatif.
                    </div>
                    <FileUpload
                        id="selfie-upload"
                        name="photo"
                        label="Unggah Foto Selfie"
                        accept="image/*"
                        capture="user"
                        multiple={false}
                        maxSize={5 * 1024 * 1024}
                        onchange={(files) => {
                            const f = files[0];
                            if (f) {
                                capturedBlob = f;
                                capturedUrl = URL.createObjectURL(f);
                            }
                        }}
                    />
                    {#if capturedUrl}
                        <div
                            class="group relative aspect-3/4 w-full overflow-hidden rounded-2xl bg-gray-100 ring-1 ring-gray-200 shadow-sm dark:bg-[#111] dark:ring-gray-800"
                        >
                            <img
                                src={capturedUrl}
                                alt="Bukti Selfie"
                                class="object-cover w-full h-full transition duration-500 group-hover:scale-105"
                            />
                            <div
                                class="absolute right-0 bottom-0 left-0 p-4 to-transparent bg-linear-to-t from-black/80"
                            >
                                <p class="text-xs font-medium text-white/90">
                                    Bukti Foto
                                </p>
                            </div>
                        </div>
                    {/if}
                </Card>
            {/if}
            <Card bodyClass="space-y-4">
                <TextInput
                    id="notes"
                    name="notes"
                    label="Catatan"
                    placeholder="Tuliskan keterangan..."
                    bind:value={notes}
                    required={notesRequired}
                    error={notesError}
                />
                <div
                    hidden={!(
                        mode === "checkin" &&
                        lateInfo &&
                        lateInfo.isLate
                    )}
                >
                    <div
                        class="text-sm text-gray-700 dark:text-gray-300 rounded-lg bg-gray-50 dark:bg-[#131313] p-3 ring-1 ring-gray-200 dark:ring-gray-800"
                    >
                        <div>
                            Status: Terlambat • Durasi:
                            {lateInfo?.hours
                                ? `${lateInfo?.hours} jam `
                                : ""}{lateInfo?.minutes}
                            menit {lateInfo?.seconds} detik
                        </div>
                    </div>
                </div>
                <div
                    hidden={!(
                        mode === "checkout" &&
                        earlyInfo &&
                        earlyInfo.isEarly
                    )}
                >
                    <div
                        class="text-sm text-gray-700 dark:text-gray-300 rounded-lg bg-gray-50 dark:bg-[#131313] p-3 ring-1 ring-gray-200 dark:ring-gray-800"
                    >
                        <div>
                            Status: Pulang Lebih Awal • Sisa:
                            {earlyInfo?.hours
                                ? `${earlyInfo?.hours} jam `
                                : ""}{earlyInfo?.minutes}
                            menit {earlyInfo?.seconds} detik
                        </div>
                    </div>
                </div>
            </Card>
            <div class="flex gap-2 justify-end">
                <Button
                    variant="primary"
                    onclick={() => (step = "summary")}
                    disabled={!capturedUrl}>Lanjut</Button
                >
                <Button
                    variant="success"
                    icon="fa-solid fa-camera"
                    onclick={capturePhoto}
                    disabled={!!cameraError}>Ambil Foto</Button
                >
                <Button
                    variant="secondary"
                    icon="fa-solid fa-rotate-left"
                    onclick={resetFlow}>Ulangi</Button
                >
            </div>
        </div>
    {/if}

    {#if step === "summary"}
        <div class="grid grid-cols-1 gap-6 items-start md:grid-cols-2">
            <Card
                title={schedule?.shift?.name ?? "Shift Tidak Diketahui"}
                subtitle={schedule?.date
                    ? formatDateTimeDisplay(schedule.date)
                    : "-"}
                bodyClass="space-y-5"
            >
                {#snippet actions()}
                    <Badge
                        size="sm"
                        rounded="pill"
                        variant={mode === "checkin" ? "success" : "warning"}
                    >
                        {#snippet children()}{mode === "checkin"
                                ? "Check In"
                                : "Check Out"}{/snippet}
                    </Badge>
                {/snippet}

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div
                            class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >
                            Jadwal Mulai
                        </div>
                        <div
                            class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-200"
                        >
                            {schedule?.shift?.start_time ?? "-"}
                        </div>
                    </div>
                    <div>
                        <div
                            class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >
                            Jadwal Selesai
                        </div>
                        <div
                            class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-200"
                        >
                            {schedule?.shift?.end_time ?? "-"}
                        </div>
                    </div>
                </div>

                <div hidden={mode !== "checkin" || !lateInfo}>
                    <div
                        class={`rounded-lg p-3 border ${lateInfo?.isLate ? "bg-red-50 border-red-100 dark:bg-red-900/20 dark:border-red-900/30" : "bg-blue-50 border-blue-100 dark:bg-blue-900/20 dark:border-blue-900/30"}`}
                    >
                        <div class="flex gap-3 items-start">
                            <i
                                class="fa-solid fa-clock text-black dark:text-white"
                            ></i>
                            <div>
                                <div
                                    class={`text-sm font-semibold ${lateInfo?.isLate ? "text-red-700 dark:text-red-400" : "text-blue-700 dark:text-blue-400"}`}
                                >
                                    {lateInfo?.isLate
                                        ? "Terlambat"
                                        : "Tepat Waktu"}
                                </div>
                                <div
                                    class="text-xs mt-0.5"
                                    class:text-red-600={lateInfo?.isLate}
                                    class:text-blue-600={!lateInfo?.isLate}
                                >
                                    {#if lateInfo?.isLate}
                                        Telat {lateInfo?.hours
                                            ? `${lateInfo?.hours} jam `
                                            : ""}{lateInfo?.minutes}
                                        menit {lateInfo?.seconds} detik
                                    {:else}
                                        Terima kasih sudah datang tepat waktu.
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div hidden={mode !== "checkout" || !earlyInfo}>
                    <div
                        class={`rounded-lg p-3 border ${earlyInfo?.isEarly ? "bg-orange-50 border-orange-100 dark:bg-orange-900/20 dark:border-orange-900/30" : "bg-blue-50 border-blue-100 dark:bg-blue-900/20 dark:border-blue-900/30"}`}
                    >
                        <div class="flex gap-3 items-start">
                            <i
                                class="fa-solid fa-clock text-black dark:text-white"
                            ></i>
                            <div>
                                <div
                                    class={`text-sm font-semibold ${earlyInfo?.isEarly ? "text-orange-700 dark:text-orange-400" : "text-blue-700 dark:text-blue-400"}`}
                                >
                                    {earlyInfo?.isEarly
                                        ? "Pulang Lebih Awal"
                                        : "Sesuai Jadwal"}
                                </div>
                                <div
                                    class="text-xs mt-0.5"
                                    class:text-orange-600={earlyInfo?.isEarly}
                                    class:text-blue-600={!earlyInfo?.isEarly}
                                >
                                    {#if earlyInfo?.isEarly}
                                        Sisa waktu {earlyInfo?.hours
                                            ? `${earlyInfo?.hours} jam `
                                            : ""}{earlyInfo?.minutes}
                                        menit {earlyInfo?.seconds} detik
                                    {:else}
                                        Terima kasih telah menyelesaikan shift.
                                    {/if}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex gap-3 items-start">
                        <i
                            class="fa-solid fa-location-dot text-black dark:text-white"
                        ></i>
                        <div class="space-y-1">
                            <div
                                class="text-sm font-medium text-gray-900 dark:text-gray-200"
                            >
                                Jarak: {distanceM !== null
                                    ? `${Math.round(distanceM)} meter`
                                    : "-"}
                            </div>
                            <div
                                class="font-mono text-xs text-gray-500 dark:text-gray-400"
                            >
                                {currentLat?.toFixed(6) ?? "-"}, {currentLong?.toFixed(
                                    6,
                                ) ?? "-"}
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <div>
                <div
                    class="group relative aspect-3/4 w-full overflow-hidden rounded-2xl bg-gray-100 ring-1 ring-gray-200 shadow-sm dark:bg-[#111] dark:ring-gray-800 sm:aspect-auto sm:h-full"
                >
                    {#if capturedUrl}
                        <img
                            src={capturedUrl}
                            alt="Bukti Selfie"
                            class="object-cover w-full h-full transition duration-500 group-hover:scale-105"
                        />
                        <div
                            class="absolute right-0 bottom-0 left-0 p-4 to-transparent bg-linear-to-t from-black/80"
                        >
                            <p class="text-xs font-medium text-white/90">
                                Bukti Foto
                            </p>
                        </div>
                    {:else}
                        <div
                            class="flex flex-col justify-center items-center w-full h-full text-gray-400"
                        >
                            <i class="fa-solid fa-camera"></i>
                            <span class="text-sm">Belum ada foto</span>
                        </div>
                    {/if}
                </div>
                <div
                    class="flex flex-col-reverse gap-2 mt-6 sm:flex-row sm:justify-end"
                >
                    <Button
                        variant="secondary"
                        onclick={resetFlow}
                        class="w-full sm:w-auto">Ulangi</Button
                    >
                    <Button
                        variant="primary"
                        loading={submitting}
                        onclick={submitAttendance}
                        class="w-full sm:w-auto"
                        size="lg"
                    >
                        Submit
                    </Button>
                </div>
            </div>
        </div>
    {/if}
</section>

<style>
    video {
        max-height: 310px;
        object-fit: cover;
    }
</style>
