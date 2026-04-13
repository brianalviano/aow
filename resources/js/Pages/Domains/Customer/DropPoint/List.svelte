<script lang="ts">
    import { onMount } from "svelte";
    import icon from "@img/icon.png";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { page, Link, router } from "@inertiajs/svelte";

    // Props from controller
    export let totalDropPoints: number;
    export let dropPoints: Array<{
        id: string;
        name: string;
        address: string;
        latitude: number | null;
        longitude: number | null;
    }> = [];
    export let activeOrdersCount: number = 0;
    export let unreadNotificationsCount: number = 0;

    $: totalBadgeCount = activeOrdersCount + unreadNotificationsCount;

    let searchQuery = "";
    let userLocation: { lat: number; lng: number } | null = null;

    onMount(() => {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };
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

    // Extended drop points with calculated distance
    $: dropPointsWithDistance = dropPoints.map((dp) => {
        let distance = null;
        if (userLocation && dp.latitude && dp.longitude) {
            distance = getDistance(
                userLocation.lat,
                userLocation.lng,
                dp.latitude,
                dp.longitude,
            );
        }
        return { ...dp, distance };
    });

    // Sort by distance if available, otherwise keep original order
    $: sortedDropPoints = [...dropPointsWithDistance].sort((a, b) => {
        if (a.distance !== null && b.distance !== null) {
            return a.distance - b.distance;
        }
        return 0;
    });

    // Filter by search query
    $: filteredDropPoints = sortedDropPoints.filter(
        (dp) =>
            dp.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            (dp.address &&
                dp.address.toLowerCase().includes(searchQuery.toLowerCase())),
    );

    function goBack() {
        router.visit("/");
    }
</script>

<svelte:head>
    <title>Pilih Drop Point | {name($page.props.settings)}</title>
</svelte:head>

<div>
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 border-b border-slate-800 bg-slate-950 sticky top-0 z-10"
    >
        <div class="flex items-center gap-3">
            <button
                on:click={goBack}
                class="w-8 h-8 flex items-center justify-center text-slate-300"
                aria-label="Kembali"
            >
                <i class="fa-solid fa-arrow-left text-lg"></i>
            </button>
            <div>
                <h1 class="font-bold text-lg leading-tight text-slate-100">
                    Pilih Drop Point
                </h1>
                <p class="text-xs text-slate-400">
                    {totalDropPoints} lokasi tersedia
                </p>
            </div>
        </div>
        <Link
            href="/menu"
            class="relative text-slate-300 p-2 focus:outline-none"
            aria-label="Menu"
        >
            <i class="fa-solid fa-bars text-xl"></i>
            {#if totalBadgeCount > 0}
                <span
                    class="absolute top-0 right-0 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white ring-2 ring-slate-900"
                >
                    {totalBadgeCount > 99 ? "99+" : totalBadgeCount}
                </span>
            {/if}
        </Link>
    </header>

    <!-- Main Content -->
    <main class="p-4 space-y-6">
        <!-- Choose Drop Point -->
        <section>
            <!-- Search Input -->
            <div class="relative mb-4">
                <div
                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                >
                    <i class="fa-solid fa-magnifying-glass text-slate-500"></i>
                </div>
                <input
                    type="text"
                    bind:value={searchQuery}
                    class="block w-full pl-10 pr-3 py-3 border border-slate-700 rounded-xl leading-5 bg-slate-800 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-[#FFD700] focus:border-[#FFD700] sm:text-sm transition-shadow shadow-sm"
                    placeholder="Cari Drop Point"
                />
            </div>

            <!-- Drop Point List -->
            <div class="space-y-3">
                {#if filteredDropPoints.length === 0}
                    <div class="text-center py-8 text-slate-400 text-sm">
                        Tidak ada drop point yang ditemukan.
                    </div>
                {/if}

                {#each filteredDropPoints as dp (dp.id)}
                    <Link
                        href={`/drop-points/${dp.id}`}
                        class="block border border-slate-700 rounded-xl p-4 shadow-sm bg-slate-800 hover:border-[#FFD700] transition-colors group cursor-pointer"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1 pr-4">
                                <h3
                                    class="font-medium text-slate-100 mb-1 leading-tight"
                                >
                                    {dp.name}
                                </h3>
                                <p
                                    class="text-xs text-slate-400 mb-3 leading-relaxed line-clamp-2"
                                >
                                    {dp.address || "Alamat tidak tersedia"}
                                </p>
                                <div
                                    class="flex items-center text-red-400 text-xs font-medium bg-red-900/20 w-fit px-2 py-1 rounded-md gap-1"
                                >
                                    <i class="fa-solid fa-location-dot"></i>
                                    {#if dp.distance !== null}
                                        {formatDistance(dp.distance)}
                                    {:else}
                                        -
                                    {/if}
                                </div>
                            </div>
                            <div
                                class="text-slate-500 group-hover:text-slate-300 transition-colors"
                            >
                                <i class="fa-solid fa-chevron-right text-lg"
                                ></i>
                            </div>
                        </div>
                    </Link>
                {/each}
            </div>
        </section>
    </main>
</div>
