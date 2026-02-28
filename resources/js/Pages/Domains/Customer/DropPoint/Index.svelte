<script lang="ts">
    import { onMount } from "svelte";
    import { Link, router } from "@inertiajs/svelte";
    import dayImage from "@img/day.jpg"; // Fallback image
    import howToOrderImage from "@img/how-to-order.png";

    export let dropPoint: {
        id: string;
        name: string;
        photo: string | null;
        photo_url: string | null;
        address: string;
        latitude: number | null;
        longitude: number | null;
    };

    let userLocation: { lat: number; lng: number } | null = null;
    let distance: number | null = null;

    onMount(() => {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };

                    if (dropPoint.latitude && dropPoint.longitude) {
                        distance = getDistance(
                            userLocation.lat,
                            userLocation.lng,
                            dropPoint.latitude,
                            dropPoint.longitude,
                        );
                    }
                },
                (error) => {
                    console.warn("Geolocation error:", error);
                },
            );
        }
    });

    // Haversine formula for distance in km
    function getDistance(
        lat1: number,
        lon1: number,
        lat2: number,
        lon2: number,
    ): number {
        const R = 6371; // Radius of the earth in km
        const dLat = deg2rad(lat2 - lat1);
        const dLon = deg2rad(lon2 - lon1);
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(deg2rad(lat1)) *
                Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) *
                Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // Distance in km
    }

    function deg2rad(deg: number): number {
        return deg * (Math.PI / 180);
    }

    function formatDistance(distanceKm: number): string {
        if (distanceKm < 1) {
            return Math.round(distanceKm * 1000) + " M";
        }
        return distanceKm.toFixed(1) + " Km";
    }

    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit("/");
        }
    }

    function shareDropPoint() {
        if (navigator.share) {
            navigator
                .share({
                    title: `AOW Order - ${dropPoint.name}`,
                    text: `Pesan makanan ke ${dropPoint.name} sekarang!`,
                    url: window.location.href,
                })
                .catch(console.error);
        } else {
            // Fallback: Copy to clipboard
            navigator.clipboard.writeText(window.location.href);
            alert("Tautan disalin ke papan klip!");
        }
    }
</script>

<svelte:head>
    <title>{dropPoint.name} | AOW Order</title>
</svelte:head>

<div>
    <!-- Image Header -->
    <div class="relative w-full aspect-video">
        <img
            src={dropPoint.photo_url || dayImage}
            alt={dropPoint.name}
            class="w-full h-full object-cover aspect-video"
        />

        <!-- Top Navigation Overlay -->
        <div
            class="absolute top-0 left-0 right-0 p-4 flex justify-between items-start z-10"
        >
            <button
                on:click={goBack}
                class="w-10 h-10 rounded-full bg-white text-gray-800 flex items-center justify-center shadow-md focus:outline-none focus:ring-2 focus:ring-gray-300 transition-transform hover:scale-105 active:scale-95"
                aria-label="Kembali"
            >
                <i class="fa-solid fa-arrow-left text-lg"></i>
            </button>

            <button
                on:click={shareDropPoint}
                class="w-10 h-10 rounded-full bg-white text-gray-800 flex items-center justify-center shadow-md focus:outline-none focus:ring-2 focus:ring-gray-300 transition-transform hover:scale-105 active:scale-95"
                aria-label="Bagikan"
            >
                <i class="fa-solid fa-share-nodes text-lg"></i>
            </button>
        </div>
    </div>

    <!-- Drop Point Detail Content -->
    <div class="bg-white p-5">
        <h1 class="text-lg font-bold text-gray-900 mb-2 leading-tight">
            {dropPoint.name}
        </h1>
        <p class="text-xs text-gray-700 leading-relaxed mb-3">
            {dropPoint.address || "Alamat tidak tersedia"}
        </p>

        <div class="flex items-center text-red-500 text-xs font-medium gap-2">
            <i class="fa-solid fa-location-dot"></i>
            <span>
                {distance !== null ? formatDistance(distance) : "-"}
            </span>
        </div>
    </div>

    <!-- Instructions Section -->
    <div class="bg-gray-50 px-5 pt-3 pb-20">
        <h2 class="text-center font-bold text-gray-900 text-base mb-4">
            Cara menggunakan AOW Order
        </h2>

        <img
            src={howToOrderImage}
            alt="How to Order"
            class="w-full object-contain"
            loading="lazy"
        />
    </div>

    <!-- Bottom Sticky Bar -->
    <div
        class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 flex items-center justify-between z-20 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]"
    >
        <div class="font-bold text-gray-900 text-sm">Drop Point Benar ?</div>
        <Link
            href={`/order-type?drop_point_id=${dropPoint.id}`}
            class="bg-[#FFD700] hover:bg-[#FFC700] text-slate-800 font-bold py-3 px-6 rounded-xl flex items-center gap-2 transition-transform active:scale-95 text-xs"
            aria-label="Lanjut ke Tipe Pesanan"
        >
            Ya, Lanjut <i class="fa-solid fa-arrow-right text-xs"></i>
        </Link>
    </div>
</div>
