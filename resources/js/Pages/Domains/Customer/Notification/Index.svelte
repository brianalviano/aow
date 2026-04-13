<script lang="ts">
    import { page, Link, router } from "@inertiajs/svelte";
    import { name } from "@/Lib/Admin/Utils/settings";

    $: notifications = $page.props.notifications;
    $: settings = $page.props.settings;

    function markAsRead(id: string | null = null) {
        if (id) {
            router.post(
                "/notifications/mark-as-read",
                { id: id },
                { preserveScroll: true },
            );
        } else {
            router.post(
                "/notifications/mark-as-read",
                {},
                { preserveScroll: true },
            );
        }
    }

    function formatTime(dateString: string) {
        if (!dateString) return "";
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor(
            (now.getTime() - date.getTime()) / 1000,
        );

        if (diffInSeconds < 60) {
            return "Baru saja";
        }

        const diffInMinutes = Math.floor(diffInSeconds / 60);
        if (diffInMinutes < 60) {
            return `${diffInMinutes} menit yang lalu`;
        }

        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) {
            return `${diffInHours} jam yang lalu`;
        }

        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) {
            return `${diffInDays} hari yang lalu`;
        }

        return date.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year:
                date.getFullYear() !== now.getFullYear()
                    ? "numeric"
                    : undefined,
        });
    }
</script>

<svelte:head>
    <title>Notifikasi | {name(settings)}</title>
</svelte:head>

<div class="min-h-screen bg-slate-950 pb-20">
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-slate-950 sticky top-0 z-20 border-b border-slate-800 shadow-sm"
    >
        <Link
            href="/menu"
            class="text-slate-300 focus:outline-none p-1"
            aria-label="Kembali ke Menu"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </Link>
        <h1
            class="font-bold text-lg leading-tight text-slate-100 absolute left-1/2 -translate-x-1/2"
        >
            Notifikasi
        </h1>
        <div class="w-7">
            <!-- Spacer for center alignment -->
        </div>
    </header>

    <main class="w-full max-w-md mx-auto relative">
        <!-- Mark All Read Action -->
        {#if notifications && notifications.data && notifications.data.length > 0}
            <div class="flex justify-end p-4 pb-2">
                <button
                    type="button"
                    class="text-sm font-semibold text-[#FFD700] hover:text-yellow-300 transition-colors"
                    on:click={() => markAsRead()}
                >
                    Tandai Semua Dibaca
                </button>
            </div>
        {/if}

        <!-- Notifications List -->
        <div class="px-4 space-y-3 pb-8 mt-2">
            {#if !notifications || !notifications.data || notifications.data.length === 0}
                <div
                    class="flex flex-col items-center justify-center pt-20 pb-10 text-center"
                >
                    <div
                        class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center text-slate-600 mb-4"
                    >
                        <i class="fa-solid fa-bell-slash text-4xl"></i>
                    </div>
                    <h2 class="text-lg font-bold text-slate-100 mb-1">
                        Belum ada notifikasi
                    </h2>
                    <p class="text-sm text-slate-400 max-w-[250px] mx-auto">
                        Semua informasi penting dan pembaruan akan muncul di
                        sini.
                    </p>
                </div>
            {:else}
                {#each notifications.data as notification}
                    <!-- svelte-ignore a11y-click-events-have-key-events -->
                    <!-- svelte-ignore a11y-no-static-element-interactions -->
                    <div
                        class="w-full text-left bg-slate-950 p-4 rounded-xl border transition-all cursor-pointer {notification.read_at ===
                        null
                            ? 'border-blue-700 bg-blue-900/20'
                            : 'border-slate-700 hover:border-slate-600'}"
                        on:click={() => {
                            if (!notification.read_at) {
                                markAsRead(notification.id);
                            }
                        }}
                    >
                        <div class="flex gap-4">
                            <!-- Icon/Avatar -->
                            <div class="shrink-0 mt-0.5">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center {notification.read_at ===
                                    null
                                        ? 'bg-blue-900/30 text-blue-400'
                                        : 'bg-slate-800 text-slate-500'}"
                                >
                                    <!-- Use icon from data or default fallback -->
                                    <i
                                        class="fa-solid {notification.data
                                            ?.icon || 'fa-bell'}"
                                    ></i>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div
                                    class="flex justify-between items-start mb-1 gap-2"
                                >
                                    <h3
                                        class="font-bold text-[15px] {notification.read_at ===
                                        null
                                            ? 'text-slate-100'
                                            : 'text-slate-300'}"
                                    >
                                        {notification.data?.title ||
                                            "Notifikasi Baru"}
                                    </h3>
                                    <span
                                        class="text-[11px] text-slate-500 whitespace-nowrap pt-0.5"
                                    >
                                        {formatTime(notification.created_at)}
                                    </span>
                                </div>
                                <p
                                    class="text-[13px] {notification.read_at ===
                                    null
                                        ? 'text-slate-300'
                                        : 'text-slate-400'} leading-snug"
                                >
                                    {notification.data?.message || "-"}
                                </p>

                                {#if notification.data?.action_url && notification.data?.action_text}
                                    <div class="mt-3">
                                        <!-- svelte-ignore a11y-click-events-have-key-events -->
                                        <!-- svelte-ignore a11y-no-static-element-interactions -->
                                        <div
                                            class="inline-block"
                                            on:click|stopPropagation
                                        >
                                            {#if notification.data.action_url.startsWith("http")}
                                                <a
                                                    href={notification.data
                                                        .action_url}
                                                    class="inline-block text-xs font-semibold border border-slate-700 hover:bg-slate-800 text-slate-300 px-3 py-1.5 rounded-lg transition-colors"
                                                >
                                                    {notification.data
                                                        .action_text}
                                                </a>
                                            {:else}
                                                <!-- svelte-ignore a11y-missing-attribute -->
                                                <Link
                                                    href={notification.data
                                                        .action_url}
                                                    class="inline-block text-xs font-semibold border border-slate-700 hover:bg-slate-800 text-slate-300 px-3 py-1.5 rounded-lg transition-colors"
                                                >
                                                    {notification.data
                                                        .action_text}
                                                </Link>
                                            {/if}
                                        </div>
                                    </div>
                                {/if}
                            </div>

                            <!-- Unread Indicator -->
                            {#if notification.read_at === null}
                                <div class="shrink-0 flex items-center mt-2.5">
                                    <div
                                        class="w-2.5 h-2.5 bg-[#FFD700] rounded-full"
                                    ></div>
                                </div>
                            {/if}
                        </div>
                    </div>
                {/each}
            {/if}
        </div>
    </main>
</div>
