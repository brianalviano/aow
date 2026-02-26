import { writable, derived } from "svelte/store";
import type { Notification, NotificationStats } from "@/Lib/Admin/Types/index";
import { router, page } from "@inertiajs/svelte";

interface NotificationStore {
    notifications: Notification[];
    stats: NotificationStats;
    loading: boolean;
    error: string | null;
    lastFetch: number | null;
}

const initialState: NotificationStore = {
    notifications: [],
    stats: {
        total: 0,
        unread: 0,
        read: 0,
    },
    loading: false,
    error: null,
    lastFetch: null,
};

/**
 * Create notification store for managing notification state
 */
function createNotificationStore() {
    const { subscribe, set, update } =
        writable<NotificationStore>(initialState);

    return {
        subscribe,

        /**
         * Fetch notification stats from the server
         */
        async fetch(force: boolean = false) {
            update((state) => ({ ...state, loading: true, error: null }));

            try {
                // Check if we need to fetch (cache for 30 seconds unless forced)
                const now = Date.now();
                const shouldFetch =
                    force ||
                    !this.getState().lastFetch ||
                    now - this.getState().lastFetch! > 30000;

                if (!shouldFetch && this.getState().notifications.length > 0) {
                    update((state) => ({ ...state, loading: false }));
                    return;
                }

                // Use regular fetch for stats only
                const url = new URL(
                    "/admin/notifications/stats",
                    window.location.origin,
                );

                const response = await fetch(url.toString(), {
                    method: "GET",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                    credentials: "same-origin",
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const stats = await response.json();

                update((state) => ({
                    ...state,
                    stats: stats.stats,
                    loading: false,
                    error: null,
                    lastFetch: now,
                }));
            } catch (error) {
                console.error("Failed to fetch notifications:", error);
                update((state) => ({
                    ...state,
                    loading: false,
                    error:
                        error instanceof Error
                            ? error.message
                            : "Failed to fetch notifications",
                }));
            }
        },

        /**
         * Fetch latest notifications list for dropdown
         */
        async fetchList(limit: number = 5) {
            update((state) => ({ ...state, loading: true, error: null }));
            try {
                const url = new URL(
                    "/admin/notifications/list",
                    window.location.origin,
                );
                url.searchParams.append("limit", limit.toString());
                const response = await fetch(url.toString(), {
                    method: "GET",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                    credentials: "same-origin",
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                update((state) => ({
                    ...state,
                    notifications: data.notifications,
                    stats: data.stats,
                    loading: false,
                    error: null,
                    lastFetch: Date.now(),
                }));
            } catch (error) {
                console.error("Failed to fetch notifications list:", error);
                update((state) => ({
                    ...state,
                    loading: false,
                    error:
                        error instanceof Error
                            ? error.message
                            : "Failed to fetch notifications list",
                }));
            }
        },

        /**
         * Navigate to notifications page
         */
        async navigateToNotifications(page: number = 1, limit: number = 10) {
            const url = new URL("/admin/notifications", window.location.origin);
            url.searchParams.append("page", page.toString());
            url.searchParams.append("limit", limit.toString());

            router.visit(url.toString(), {
                preserveState: false,
                preserveScroll: false,
            });
        },

        /**
         * Get current state
         */
        getState(): NotificationStore {
            let currentState: NotificationStore;
            this.subscribe((state) => {
                currentState = state;
            })();
            return currentState!;
        },

        /**
         * Update notifications from page props
         */
        updateFromProps(
            notifications: Notification[],
            stats: NotificationStats,
        ) {
            update((state) => ({
                ...state,
                notifications,
                stats,
                loading: false,
                error: null,
                lastFetch: Date.now(),
            }));
        },

        /**
         * Mark a notification as read
         */
        async markAsRead(notificationId: string) {
            try {
                const response = await fetch(
                    `/admin/notifications/${notificationId}`,
                    {
                        method: "PATCH",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN":
                                document
                                    .querySelector('meta[name="csrf-token"]')
                                    ?.getAttribute("content") || "",
                        },
                    },
                );

                if (response.ok) {
                    // Update local state
                    update((state) => ({
                        ...state,
                        notifications: state.notifications.map((n) =>
                            n.id === notificationId
                                ? { ...n, read_at: new Date().toISOString() }
                                : n,
                        ),
                        stats: {
                            ...state.stats,
                            unread: Math.max(0, state.stats.unread - 1),
                            read: state.stats.read + 1,
                        },
                    }));
                }
            } catch (error) {
                console.error("Failed to mark notification as read:", error);
            }
        },

        /**
         * Mark all notifications as read
         */
        async markAllAsRead() {
            try {
                const response = await fetch(
                    "/admin/notifications/mark-all-read",
                    {
                        method: "PATCH",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN":
                                document
                                    .querySelector('meta[name="csrf-token"]')
                                    ?.getAttribute("content") || "",
                        },
                    },
                );

                if (response.ok) {
                    // Update local state
                    update((state) => ({
                        ...state,
                        notifications: state.notifications.map((n) => ({
                            ...n,
                            read_at: n.read_at || new Date().toISOString(),
                        })),
                        stats: {
                            ...state.stats,
                            unread: 0,
                            read: state.stats.total,
                        },
                    }));
                }
            } catch (error) {
                console.error(
                    "Failed to mark all notifications as read:",
                    error,
                );
            }
        },

        /**
         * Handle notification click action
         */
        async handleNotificationClick(notification: Notification) {
            // Mark as read if not already read
            if (!notification.read_at) {
                await this.markAsRead(notification.id);
            }

            // Navigate to action URL if provided
            if (notification.action_url) {
                router.visit(notification.action_url);
            }
        },

        /**
         * Refresh notification stats
         */
        async refreshStats() {
            try {
                // Check if user is authenticated before making request
                let currentPageProps: any;
                page.subscribe((pageData) => {
                    currentPageProps = pageData.props;
                })();

                if (!currentPageProps?.auth?.user) {
                    console.log(
                        "User not authenticated, skipping notification stats refresh",
                    );
                    return;
                }

                const response = await fetch("/admin/notifications/stats", {
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                });

                if (response.ok) {
                    const data = await response.json();
                    update((state) => ({
                        ...state,
                        stats: data.stats,
                    }));
                } else if (response.status === 401) {
                    console.log(
                        "User not authenticated for notification stats",
                    );
                    // Clear any existing stats on authentication failure
                    update((state) => ({
                        ...state,
                        stats: {
                            total: 0,
                            unread: 0,
                            read: 0,
                        },
                    }));
                }
            } catch (error) {
                console.error("Failed to refresh notification stats:", error);
            }
        },

        /**
         * Clear all notifications
         */
        clear() {
            set(initialState);
        },

        /**
         * Add a new notification
         */
        addNotification(notification: Notification) {
            update((state) => ({
                ...state,
                notifications: [notification, ...state.notifications],
                stats: {
                    ...state.stats,
                    total: state.stats.total + 1,
                    unread: notification.read_at
                        ? state.stats.unread
                        : state.stats.unread + 1,
                    read: notification.read_at
                        ? state.stats.read + 1
                        : state.stats.read,
                },
            }));
        },

        /**
         * Remove a notification
         */
        removeNotification(notificationId: string) {
            update((state) => {
                const notification = state.notifications.find(
                    (n) => n.id === notificationId,
                );
                if (!notification) return state;

                return {
                    ...state,
                    notifications: state.notifications.filter(
                        (n) => n.id !== notificationId,
                    ),
                    stats: {
                        ...state.stats,
                        total: Math.max(0, state.stats.total - 1),
                        unread: notification.read_at
                            ? state.stats.unread
                            : Math.max(0, state.stats.unread - 1),
                        read: notification.read_at
                            ? Math.max(0, state.stats.read - 1)
                            : state.stats.read,
                    },
                };
            });
        },

        /**
         * Initialize the notification system
         */
        async init() {
            // Check if user is authenticated before fetching stats
            let currentPageProps: any;
            page.subscribe((pageData) => {
                currentPageProps = pageData.props;
            })();

            // Only initialize if user is authenticated
            if (currentPageProps?.auth?.user) {
                await this.refreshStats();
            }
        },

        /**
         * Cleanup
         */
        destroy() {
            // Cleanup logic if needed
        },
    };
}

export const notificationStore = createNotificationStore();

// Initialize on client side only if user is authenticated
if (typeof window !== "undefined") {
    // Wait for page props to be available before initializing
    let unsubscribe: (() => void) | null = null;

    const checkAndInit = () => {
        unsubscribe = page.subscribe((pageData) => {
            if (pageData.props?.auth?.user) {
                notificationStore.init();
                if (unsubscribe) {
                    unsubscribe();
                    unsubscribe = null;
                }
            }
        });
    };

    // Check immediately if page is already loaded
    if (document.readyState === "complete") {
        checkAndInit();
    } else {
        // Wait for page to load
        window.addEventListener("load", checkAndInit);
    }
}

export const notifications = derived(
    notificationStore,
    ($store) => $store.notifications,
);

export const notificationStats = derived(
    notificationStore,
    ($store) => $store.stats,
);

export const unreadNotifications = derived(notificationStore, ($store) =>
    $store.notifications.filter((n) => !n.read_at),
);

export const isLoadingNotifications = derived(
    notificationStore,
    ($store) => $store.loading,
);

export const notificationError = derived(
    notificationStore,
    ($store) => $store.error,
);

/**
 * Get notification icon based on type
 */
export function getNotificationIcon(type: Notification["type"]): string {
    const icons = {
        general: "bell",
    };
    return icons[type] || "bell";
}

/**
 * Get notification color based on priority
 */
export function getNotificationColor(
    priority: Notification["priority"],
): string {
    const colors = {
        low: "blue",
        medium: "yellow",
        high: "orange",
        urgent: "red",
    };
    return colors[priority] || "blue";
}

/**
 * Format relative time
 */
export function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffInSeconds < 60) {
        return "Baru saja";
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} menit yang lalu`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} jam yang lalu`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} hari yang lalu`;
    }
}
