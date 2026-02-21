<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";

    // TODO: replace with real props from controller
    // e.g. DropPoint[], MenuItem[]
</script>

<svelte:head>
    <title>{siteName($page.props.settings)}</title>
</svelte:head>

<div class="min-h-screen bg-slate-50 dark:bg-neutral-900">
    <!-- Navbar -->
    <header
        class="sticky top-0 z-30 border-b border-slate-200 bg-white/80 backdrop-blur-md dark:border-neutral-700 dark:bg-neutral-900/80"
    >
        <div
            class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4"
        >
            <span class="text-lg font-bold text-gray-800 dark:text-white">
                {siteName($page.props.settings)}
            </span>
            <a
                href="/login"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
            >
                Masuk / Daftar
            </a>
        </div>
    </header>

    <!-- Hero -->
    <main class="mx-auto max-w-5xl px-6 py-12">
        <div class="mb-10 text-center">
            <h1
                class="mb-3 text-3xl font-bold text-gray-800 dark:text-white md:text-4xl"
            >
                Pesan Makanan Sekarang
            </h1>
            <p class="text-gray-500 dark:text-gray-400">
                Pilih drop point terdekatmu, lalu temukan menu favoritmu.
            </p>
        </div>

        <!-- Drop Point picker placeholder -->
        <section class="mb-10">
            <h2
                class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-300"
            >
                Pilih Drop Point
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- TODO: render drop points from props -->
                {#each [1, 2, 3] as i}
                    <button
                        class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 text-left transition hover:border-blue-400 hover:shadow-md dark:border-neutral-700 dark:bg-neutral-800 dark:hover:border-blue-500"
                    >
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30"
                        >
                            <i
                                class="fa-solid fa-location-dot text-lg text-blue-600 dark:text-blue-400"
                            ></i>
                        </div>
                        <div>
                            <p
                                class="font-semibold text-gray-800 dark:text-white"
                            >
                                Drop Point {i}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Alamat drop point {i}
                            </p>
                        </div>
                    </button>
                {/each}
            </div>
        </section>

        <!-- Menu placeholder -->
        <section>
            <h2
                class="mb-4 text-lg font-semibold text-gray-700 dark:text-gray-300"
            >
                Menu Tersedia
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- TODO: render menu items from props -->
                {#each [1, 2, 3, 4] as i}
                    <div
                        class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-neutral-700 dark:bg-neutral-800"
                    >
                        <div
                            class="flex h-36 items-center justify-center bg-slate-100 dark:bg-neutral-700"
                        >
                            <i
                                class="fa-solid fa-bowl-food text-4xl text-slate-400 dark:text-neutral-500"
                            ></i>
                        </div>
                        <div class="p-4">
                            <p
                                class="mb-1 font-semibold text-gray-800 dark:text-white"
                            >
                                Menu Item {i}
                            </p>
                            <p
                                class="mb-3 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Rp 25.000
                            </p>
                            <button
                                class="w-full rounded-lg bg-blue-600 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                            >
                                + Tambah
                            </button>
                        </div>
                    </div>
                {/each}
            </div>
        </section>
    </main>
</div>
