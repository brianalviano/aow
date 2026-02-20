<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Tab from "@/Lib/Admin/Components/Ui/Tab.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateDisplay } from "@/Lib/Admin/Utils/date";
    import {
        notificationStore,
        notifications as notifList,
        notificationStats as notifStats,
        getNotificationIcon,
    } from "@/Lib/Admin/Stores/notifications";

    type Notification = import("@/Lib/Admin/Types").Notification;
    type NotificationStats = import("@/Lib/Admin/Types").NotificationStats;

    /**
     * Sync initial props into notification store
     */
    $effect(() => {
        const anyProps = $page.props as any;
        const list: Notification[] = Array.isArray(anyProps?.notifications)
            ? (anyProps?.notifications as Notification[])
            : ((anyProps?.notifications?.data ?? []) as Notification[]);
        const s: NotificationStats = (anyProps?.stats ?? {
            total: 0,
            unread: 0,
            read: 0,
        }) as NotificationStats;
        notificationStore.updateFromProps(list, s);
    });

    let meta = $derived(
        $page.props.meta as {
            total: number;
            per_page: number;
            current_page: number;
            last_page: number;
        },
    );

    type BadgeVariant =
        | "primary"
        | "success"
        | "warning"
        | "danger"
        | "info"
        | "secondary"
        | "light"
        | "dark"
        | "purple";

    type TabItemLocal = {
        id: string;
        label: string;
        badge?: number | string;
        badgeVariant?: BadgeVariant;
    };

    let filters = $state<{ status: string }>({
        status: (($page.props.filters as { status?: string })?.status ??
            "") as string,
    });

    let statusTabs = $derived<TabItemLocal[]>([
        {
            id: "",
            label: "Semua",
            badge: $notifStats.total ?? 0,
            badgeVariant: "secondary",
        },
        {
            id: "unread",
            label: "Belum dibaca",
            badge: $notifStats.unread ?? 0,
            badgeVariant: "warning",
        },
        {
            id: "read",
            label: "Sudah dibaca",
            badge: $notifStats.read ?? 0,
            badgeVariant: "success",
        },
    ]);

    /**
     * Navigate to page with current filters
     * @param pageNumber Target page number
     */
    function goToPage(pageNumber: number) {
        const params = new URLSearchParams();
        const limit = meta.per_page || 10;
        if (filters.status) params.set("status", filters.status);
        params.set("page", String(pageNumber));
        params.set("limit", String(limit));
        router.get(
            "/notifications?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }
</script>

<svelte:head>
    <title>Notifikasi | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Notifikasi
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola notifikasi sistem
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="warning"
                icon="fa-solid fa-bell"
                disabled={!$notifStats.unread}
                onclick={() => notificationStore.markAllAsRead()}
            >
                Tandai semua dibaca
            </Button>
        </div>
    </header>

    <div>
        <Tab
            tabs={statusTabs}
            activeTab={filters.status}
            variant="underline"
            fullWidth={true}
            justified={true}
            onTabChange={(tabId) => {
                filters.status = tabId;
                const params = new URLSearchParams();
                if (filters.status) params.set("status", filters.status);
                router.get(
                    "/notifications?" + params.toString(),
                    {},
                    { preserveState: true, preserveScroll: true },
                );
            }}
        />
    </div>

    <Card title="Daftar Notifikasi" bodyWithoutPadding={true}>
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Tipe</th>
                            <th>Judul</th>
                            <th>Pesan</th>
                            <th>Dibuat</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if $notifList?.length}
                            {#each $notifList as n}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            <i
                                                class="fa-solid fa-{getNotificationIcon(
                                                    n.type,
                                                )}"
                                                aria-hidden="true"
                                            ></i>
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {n.title}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {n.message || "Tidak ada pesan"}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {formatDateDisplay(n.created_at)}
                                        </div>
                                    </td>
                                    <td>
                                        {#if !n.read_at}
                                            <Badge
                                                size="sm"
                                                rounded="pill"
                                                variant="warning"
                                                title="Belum dibaca"
                                            >
                                                {#snippet children()}Belum
                                                    dibaca{/snippet}
                                            </Badge>
                                        {:else}
                                            <Badge
                                                size="sm"
                                                rounded="pill"
                                                variant="success"
                                                title="Sudah dibaca"
                                            >
                                                {#snippet children()}Sudah
                                                    dibaca{/snippet}
                                            </Badge>
                                        {/if}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex gap-2 items-center">
                                            {#if n.action_url}
                                                <Button
                                                    variant="primary"
                                                    size="sm"
                                                    icon="fa-solid fa-arrow-right"
                                                    href={n.action_url}
                                                >
                                                    Buka
                                                </Button>
                                            {/if}
                                            {#if !n.read_at}
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    icon="fa-solid fa-envelope-open"
                                                    onclick={() =>
                                                        notificationStore.markAsRead(
                                                            n.id,
                                                        )}
                                                >
                                                    Tandai dibaca
                                                </Button>
                                            {/if}
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="6"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
        {#snippet footer()}
            <Pagination
                currentPage={meta.current_page}
                totalPages={meta.last_page}
                totalItems={meta.total}
                itemsPerPage={meta.per_page}
                onPageChange={goToPage}
                showItemsPerPage={false}
            />
        {/snippet}
    </Card>
</section>
