<script lang="ts">
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import { router, page, Link } from "@inertiajs/svelte";
    import { onMount, tick } from "svelte";
    import {
        formatBadge,
        getTextSizeClass,
        getActiveMenuLink,
        ensureActiveSubmenuExpanded,
        filterMenuGroups,
        applyThemeClass,
        normalizeRoutePath,
    } from "@/Lib/Admin/Hooks/sidebar";
    import type { MenuGroup, MenuItem } from "@/Lib/Admin/Types/sidebar";
    import icon from "@img/icon.png";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        notificationStore,
        notifications as notifList,
        notificationStats as notifStats,
        getNotificationIcon,
        getNotificationColor,
        formatRelativeTime,
    } from "@/Lib/Admin/Stores/notifications";

    interface Props {
        user?: {
            name: string;
            email: string;
            avatar?: string;
        };
        onDesktopCollapsed?: (payload: { collapsed: boolean }) => void;
    }

    let { user, onDesktopCollapsed }: Props = $props();

    let sidebarCollapsed = $state(false);
    let sidebarOpen = $state(false);
    let profileDropdownOpen = $state(false);
    let notifDropdownOpen = $state(false);
    let searchQuery = $state("");
    let darkMode = $state(false);

    let expandedMenus = $state<Set<string>>(new Set());

    let bookingPendingCount = $derived(() => {
        const props = $page.props as unknown as {
            booking_pending_count?: number;
        };
        const value = props?.booking_pending_count;
        return typeof value === "number" ? value : 0;
    });

    let userRole = $derived(() => {
        const props = $page.props as unknown as {
            auth?: { user?: { role?: string | null } };
        };
        return props?.auth?.user?.role ?? null;
    });

    let dynamicMenuGroups = $derived((): MenuGroup[] => {
        const menu = ($page.props.menu || []) as MenuGroup[];
        return menu.map(
            (group): MenuGroup => ({
                title: group.title,
                items: group.items.map((item): MenuItem => {
                    if (item.id === "booking") {
                        const count = bookingPendingCount();
                        if (count > 0) {
                            return { ...item, badge: count };
                        }
                        const { badge: _omit, ...rest } = item;
                        return rest;
                    }
                    return item;
                }),
            }),
        );
    });

    let filteredMenuGroups = $derived(() => {
        return filterMenuGroups(dynamicMenuGroups(), searchQuery);
    });

    let activeLink = $derived(() => {
        return getActiveMenuLink(
            dynamicMenuGroups(),
            $page.url,
            $page.component,
        );
    });

    let navContainer: HTMLDivElement | null = null;

    function scrollActiveIntoView() {
        if (!navContainer) return;
        const linkValue = activeLink();
        if (!linkValue) return;
        const targetPath = normalizeRoutePath(linkValue);
        const anchors = Array.from(
            navContainer.querySelectorAll("a[href]"),
        ) as HTMLAnchorElement[];
        let candidate: HTMLAnchorElement | null = null;
        for (const a of anchors) {
            const hrefAttr = a.getAttribute("href") ?? a.href;
            const p = normalizeRoutePath(hrefAttr);
            if (
                p === targetPath ||
                (p.startsWith(targetPath + "/") && p.length > targetPath.length)
            ) {
                candidate = a;
                break;
            }
        }
        if (!candidate) return;
        candidate.scrollIntoView({ block: "center" });
    }

    function toggleSidebar() {
        sidebarOpen = !sidebarOpen;
    }

    function closeSidebarOnMobile() {
        if (typeof window !== "undefined" && window.innerWidth < 1024) {
            sidebarOpen = false;
            profileDropdownOpen = false;
            notifDropdownOpen = false;
        }
    }

    function toggleSidebarDesktop() {
        sidebarCollapsed = !sidebarCollapsed;
        if (sidebarCollapsed) {
            profileDropdownOpen = false;
            notifDropdownOpen = false;
        }
        onDesktopCollapsed?.({ collapsed: sidebarCollapsed });
    }

    function toggleSubmenu(menuId: string) {
        if (expandedMenus.has(menuId)) {
            expandedMenus.delete(menuId);
        } else {
            expandedMenus.add(menuId);
        }
        expandedMenus = expandedMenus;
    }

    function toggleTheme() {
        darkMode = !darkMode;
        localStorage.setItem("theme", darkMode ? "dark" : "light");
        applyThemeClass(darkMode);
    }

    function handleLogout() {
        router.post("/logout");
    }

    function closeDropdowns(event: MouseEvent) {
        const target = event.target as HTMLElement;
        if (
            !target.closest("#profileToggle") &&
            !target.closest("#profileDropdown")
        ) {
            profileDropdownOpen = false;
        }
        if (
            !target.closest("#notifToggle") &&
            !target.closest("#notifDropdown")
        ) {
            // notifDropdownOpen = false;
        }
    }

    onMount(() => {
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme) {
            darkMode = savedTheme === "dark";
        } else {
            darkMode = window.matchMedia(
                "(prefers-color-scheme: dark)",
            ).matches;
        }
        applyThemeClass(darkMode);

        document.addEventListener("click", closeDropdowns);
        onDesktopCollapsed?.({ collapsed: sidebarCollapsed });

        {
            const nextMenus = ensureActiveSubmenuExpanded(
                dynamicMenuGroups(),
                expandedMenus,
                $page.url,
                $page.component,
            );
            if (nextMenus !== expandedMenus) {
                expandedMenus = nextMenus;
            }
            tick().then(() => {
                scrollActiveIntoView();
            });
        }

        return () => {
            document.removeEventListener("click", closeDropdowns);
        };
    });

    $effect(() => {
        $page.url;
        $page.component;

        {
            const nextMenus = ensureActiveSubmenuExpanded(
                dynamicMenuGroups(),
                expandedMenus,
                $page.url,
                $page.component,
            );
            if (nextMenus !== expandedMenus) {
                expandedMenus = nextMenus;
            }
            tick().then(() => {
                scrollActiveIntoView();
            });
        }
        closeSidebarOnMobile();
    });
</script>

<!-- Toggle Button when Collapsed (Desktop) -->
{#if sidebarCollapsed}
    <button
        onclick={toggleSidebarDesktop}
        class="hidden lg:flex fixed left-0 top-1/2 -translate-y-1/2 z-50
               bg-[#0060B2] text-white
               p-3 rounded-r-xl shadow-lg hover:shadow-xl
               hover:bg-[#00559F]
               transition-all duration-300 items-center justify-center
               group"
        type="button"
        aria-label="Toggle sidebar"
    >
        <i class="text-sm fa-solid fa-angles-right"></i>
    </button>
{/if}

<header
    class="sticky top-0 z-20 bg-white dark:bg-[#0f0f0f] border-b border-gray-200 dark:border-[#2c2c2c] lg:hidden print:hidden"
>
    <div class="flex justify-between items-center px-4 py-3">
        <div class="flex gap-3 items-center">
            <img
                src={icon}
                alt="Logo"
                class="object-contain w-8 h-8 rounded-lg"
                loading="lazy"
            />
            <span class="text-sm font-semibold text-gray-900 dark:text-white"
                >{siteName($page.props.settings)}</span
            >
        </div>
        <button
            onclick={toggleSidebar}
            class="p-2.5 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-400 transition-colors"
            type="button"
            aria-label="Toggle sidebar"
        >
            <i class="text-sm fa-solid fa-bars"></i>
        </button>
    </div>
</header>

<!-- Mobile Overlay -->
{#if sidebarOpen}
    <button
        type="button"
        onclick={toggleSidebar}
        aria-label="Tutup sidebar"
        class="fixed inset-0 z-30 backdrop-blur-sm transition-opacity duration-300 bg-black/50 lg:hidden focus:outline-none print:hidden"
    ></button>
{/if}

<!-- Sidebar -->
<aside
    class="fixed top-0 left-0 h-dvh lg:h-screen z-40 flex flex-col transition-all duration-300
           bg-white dark:bg-[#0f0f0f] border-r border-gray-200 dark:border-[#2c2c2c]
           w-full lg:w-[15%] {sidebarOpen ? '' : '-translate-x-full'}
           {sidebarCollapsed ? 'lg:-translate-x-full' : 'lg:translate-x-0'}
           shadow-xl lg:shadow-none print:hidden"
>
    <!-- Header -->
    <div class="shrink-0">
        <div class="flex justify-between items-center p-4">
            <div class="flex gap-3 items-center">
                <div>
                    <img
                        src={icon}
                        alt="Logo Utama"
                        loading="lazy"
                        class="object-contain rounded-lg size-8"
                    />
                </div>
                <div>
                    <h1
                        class="text-[13px] font-bold text-gray-900 dark:text-white"
                    >
                        {siteName($page.props.settings)}
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Admin Panel
                    </p>
                </div>
            </div>

            <div class="flex gap-2 items-center">
                <button
                    onclick={toggleSidebar}
                    class="p-2 text-gray-600 rounded-lg transition-colors lg:hidden hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-400"
                    type="button"
                    aria-label="Tutup sidebar"
                >
                    <i class="text-sm fa-solid fa-times"></i>
                </button>
                <button
                    onclick={toggleSidebarDesktop}
                    class="hidden p-2 text-gray-600 rounded-lg transition-colors lg:inline-flex hover:bg-gray-100 dark:hover:bg-gray-800 dark:text-gray-400"
                    type="button"
                    aria-label="Toggle sidebar"
                >
                    <i class="text-sm fa-solid fa-angles-left"></i>
                </button>
            </div>
        </div>

        <!-- Search -->
        <div class="px-4 pb-3">
            <TextInput
                id="search"
                name="search"
                placeholder="Cari menu..."
                type="text"
                icon="fa-solid fa-search"
                bind:value={searchQuery}
            />
        </div>
    </div>

    <!-- Navigation -->
    <div
        bind:this={navContainer}
        class="min-h-0 overflow-y-auto flex-1 px-3 pb-4 custom-scrollbar"
    >
        <nav class="space-y-3">
            {#each filteredMenuGroups() as group}
                <div class="menu-group">
                    <div class="px-3 mb-2">
                        <span
                            class="text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >
                            {group.title}
                        </span>
                    </div>

                    <div class="space-y-1">
                        {#each group.items as item}
                            {#if item.submenu}
                                <div>
                                    <button
                                        onclick={() => toggleSubmenu(item.id)}
                                        class="flex items-center px-3 py-2 w-full text-gray-700 rounded-lg transition-all group dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                                    >
                                        <i
                                            class="fa-solid {item.icon} w-4 text-center text-sm
                                                   text-gray-500 dark:text-gray-400
                                                   group-hover:text-[#0060B2] dark:group-hover:text-[#0060B2]
                                                   transition-colors"
                                        ></i>
                                        <span
                                            class="ml-3 flex-1 min-w-0 truncate text-left {getTextSizeClass(
                                                item.label,
                                            )}"
                                        >
                                            {item.label}
                                        </span>
                                        {#if item.badge}
                                            <span
                                                class="text-white bg-red-500 badge-modern"
                                            >
                                                {formatBadge(item.badge)}
                                            </span>
                                        {/if}
                                        <i
                                            class="fa-solid fa-chevron-down ml-2 text-xs
                                                   text-gray-400 transition-transform duration-200
                                                   {expandedMenus.has(item.id)
                                                ? 'rotate-180'
                                                : ''}"
                                        ></i>
                                    </button>

                                    {#if expandedMenus.has(item.id)}
                                        <div class="mt-1 ml-7 space-y-1">
                                            {#each item.submenu as subitem}
                                                {@const isActive =
                                                    subitem.link ===
                                                    activeLink()}
                                                <Link
                                                    href={subitem.link}
                                                    onclick={closeSidebarOnMobile}
                                                    class="w-full flex items-center px-3 py-2 rounded-lg
                                                           transition-all group
                                                           {isActive
                                                        ? 'bg-[#0060B2] text-white shadow-lg shadow-[#0060B2]/30'
                                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'}"
                                                >
                                                    <i
                                                        class="fa-solid fa-circle text-[6px] mr-3
                                                               {isActive
                                                            ? 'text-white'
                                                            : 'text-gray-400 group-hover:text-[#0060B2] dark:group-hover:text-[#0060B2]'}"
                                                    ></i>
                                                    <span
                                                        class="flex-1 min-w-0 truncate text-left {getTextSizeClass(
                                                            subitem.label,
                                                        )}"
                                                    >
                                                        {subitem.label}
                                                    </span>
                                                    {#if subitem.badge}
                                                        <span
                                                            class="ml-auto text-white bg-red-500 badge-modern"
                                                        >
                                                            {formatBadge(
                                                                subitem.badge,
                                                            )}
                                                        </span>
                                                    {/if}
                                                </Link>
                                            {/each}
                                        </div>
                                    {/if}
                                </div>
                            {:else}
                                {@const isActive = item.link === activeLink()}
                                <Link
                                    href={item.link}
                                    onclick={closeSidebarOnMobile}
                                    class="w-full flex items-center px-3 py-2 rounded-lg
                                           transition-all group
                                           {isActive
                                        ? 'bg-[#0060B2] text-white shadow-lg shadow-[#0060B2]/30'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'}"
                                >
                                    <i
                                        class="fa-solid {item.icon} w-4 text-center text-sm
                                               {isActive
                                            ? 'text-white'
                                            : 'text-gray-500 dark:text-gray-400 group-hover:text-[#0060B2] dark:group-hover:text-[#0060B2]'} 
                                               transition-colors"
                                    ></i>
                                    <span
                                        class="ml-3 flex-1 min-w-0 truncate text-left {getTextSizeClass(
                                            item.label,
                                        )}"
                                    >
                                        {item.label}
                                    </span>
                                    {#if item.badge}
                                        <span
                                            class="ml-auto text-white bg-red-500 badge-modern"
                                        >
                                            {formatBadge(item.badge)}
                                        </span>
                                    {/if}
                                </Link>
                            {/if}
                        {/each}
                    </div>
                </div>
            {/each}
        </nav>
    </div>

    <!-- Footer Section -->
    <div class="shrink-0 border-t border-gray-200 dark:border-[#1a1a1a]">
        <!-- Notifications -->
        <div class="relative p-2">
            <button
                id="notifToggle"
                onclick={(e) => {
                    e.stopPropagation();
                    notifDropdownOpen = !notifDropdownOpen;
                    profileDropdownOpen = false;
                    if (notifDropdownOpen) {
                        notificationStore.fetchList(5);
                    }
                }}
                class="flex items-center px-3 py-2 w-full text-gray-700 rounded-lg transition-all group dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
            >
                <i
                    class="fa-solid fa-bell w-4 text-center text-sm
                           text-gray-500 dark:text-gray-400
                           group-hover:text-[#0060B2] dark:group-hover:text-[#0060B2]
                           transition-colors"
                ></i>
                <span class="flex-1 ml-3 text-sm text-left">Notifikasi</span>
                {#if $notifStats.unread > 0}
                    <span class="text-white bg-red-500 badge-modern">
                        {formatBadge($notifStats.unread)}
                    </span>
                {/if}
            </button>

            {#if notifDropdownOpen}
                <div
                    id="notifDropdown"
                    class="absolute bottom-full left-4 right-4
                           lg:left-full lg:right-auto lg:bottom-0
                           mb-2 lg:mb-0 lg:ml-4 w-auto lg:w-80
                           bg-white dark:bg-[#0f0f0f] rounded-xl
                           shadow-2xl border border-gray-200 dark:border-[#1a1a1a]
                           z-50 flex flex-col overflow-hidden"
                    style="max-height: calc(100vh - 200px);"
                >
                    <div
                        class="p-4 border-b border-gray-200 dark:border-[#1a1a1a]
                               flex items-center justify-between shrink-0"
                    >
                        <h3
                            class="text-sm font-semibold text-gray-900 dark:text-white"
                        >
                            Notifikasi
                        </h3>
                        <button
                            onclick={() => (notifDropdownOpen = false)}
                            class="text-gray-400 lg:hidden hover:text-gray-600 dark:hover:text-gray-300"
                            type="button"
                            aria-label="Tutup notifikasi"
                        >
                            <i class="text-sm fa-solid fa-times"></i>
                        </button>
                    </div>

                    <div class="overflow-y-auto flex-1 custom-scrollbar">
                        {#if $notifList.length === 0}
                            <div
                                class="p-4 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Belum ada notifikasi.
                            </div>
                        {:else}
                            {#each $notifList as n}
                                <button
                                    onclick={() => {
                                        closeSidebarOnMobile();
                                        notificationStore.handleNotificationClick(
                                            n,
                                        );
                                    }}
                                    class="block w-full text-left p-4
                                       hover:bg-gray-50 dark:hover:bg-gray-800
                                       transition-colors border-b border-gray-100
                                       dark:border-[#1a1a1a] last:border-b-0"
                                >
                                    <div class="flex gap-3 items-start">
                                        <div
                                            class="w-9 h-9 rounded-lg
                                               bg-gray-100 dark:bg-gray-800
                                               flex items-center justify-center shrink-0"
                                        >
                                            <i
                                                class="text-xs fa-solid fa-{getNotificationIcon(
                                                    n.type,
                                                )}"
                                            ></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p
                                                class="text-sm font-medium text-gray-900 dark:text-white"
                                            >
                                                {n.title}
                                            </p>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                            >
                                                {n.message}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-gray-400 dark:text-gray-500"
                                            >
                                                {formatRelativeTime(
                                                    n.created_at,
                                                )}
                                            </p>
                                        </div>
                                    </div>
                                </button>
                            {/each}
                        {/if}
                    </div>

                    <div
                        class="p-3 border-t border-gray-200 dark:border-[#1a1a1a] shrink-0"
                    >
                        <button
                            onclick={() => {
                                closeSidebarOnMobile();
                                router.visit("/notifications");
                            }}
                            class="block w-full text-center py-2 text-sm
                                   text-[#0060B2] dark:text-[#0060B2]
                                   hover:text-[#00559F] dark:hover:text-[#00559F]
                                   font-medium transition-colors"
                        >
                            Lihat Semua Notifikasi
                        </button>
                        <button
                            onclick={() => notificationStore.markAllAsRead()}
                            class="block w-full text-center py-2 text-xs mt-2
                                   text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300
                                   transition-colors"
                        >
                            Tandai Semua Dibaca
                        </button>
                    </div>
                </div>
            {/if}
        </div>

        <!-- Profile -->
        <div class="relative p-2">
            <button
                id="profileToggle"
                onclick={(e) => {
                    e.stopPropagation();
                    profileDropdownOpen = !profileDropdownOpen;
                    notifDropdownOpen = false;
                }}
                class="flex gap-3 items-center px-3 py-2 w-full rounded-lg transition-colors hover:bg-gray-100 dark:hover:bg-gray-800 group"
            >
                <img
                    src={user?.avatar ||
                        `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || "User")}&background=0060B2&color=fff`}
                    alt="Profile"
                    class="w-8 h-8 rounded-lg shrink-0 ring-2 ring-gray-200 dark:ring-[#1a1a1a]"
                    loading="lazy"
                />
                <div class="flex-1 min-w-0 text-left">
                    <p
                        class="text-sm font-semibold text-gray-900 truncate dark:text-white"
                    >
                        {user?.name || "User"}
                    </p>
                    <p
                        class="text-xs text-gray-500 truncate dark:text-gray-400"
                    >
                        {userRole() || "Administrator"}
                    </p>
                </div>
                <i
                    class="text-xs text-gray-400 transition-colors fa-solid fa-chevron-up group-hover:text-gray-600 dark:group-hover:text-gray-300"
                ></i>
            </button>

            {#if profileDropdownOpen}
                <div
                    id="profileDropdown"
                    class="absolute bottom-full left-4 right-4 mb-2
                           bg-white dark:bg-[#0f0f0f] rounded-xl
                           shadow-2xl border border-gray-200 dark:border-[#1a1a1a]
                           overflow-hidden"
                >
                    <button
                        onclick={() => {
                            closeSidebarOnMobile();
                            const role = userRole();
                            router.visit(
                                role === "Admin"
                                    ? "/settings"
                                    : "/account/settings",
                            );
                        }}
                        class="w-full flex items-center px-4 py-2.5
                               hover:bg-gray-50 dark:hover:bg-gray-800
                               transition-colors text-gray-700 dark:text-gray-300
                               text-sm"
                    >
                        <i class="w-4 text-sm text-center fa-solid fa-cog"></i>
                        <span class="ml-3"
                            >{userRole() === "Admin"
                                ? "Pengaturan"
                                : "Pengaturan Akun"}</span
                        >
                    </button>

                    <div
                        class="px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800
                               transition-colors"
                    >
                        <div class="flex justify-between items-center">
                            <div
                                class="flex items-center text-gray-700 dark:text-gray-300"
                            >
                                <i
                                    class="fa-solid {darkMode
                                        ? 'fa-moon'
                                        : 'fa-sun'} w-4 text-center text-sm"
                                ></i>
                                <span class="ml-3 text-sm">
                                    {darkMode ? "Dark Mode" : "Light Mode"}
                                </span>
                            </div>
                            <button
                                onclick={toggleTheme}
                                class="relative inline-flex h-6 w-11 items-center rounded-full
                                       transition-colors focus:outline-none focus:ring-2
                                       focus:ring-[#0060B2] focus:ring-offset-2
                                       {darkMode
                                    ? 'bg-[#0060B2]'
                                    : 'bg-gray-300'}"
                                type="button"
                                role="switch"
                                aria-checked={darkMode}
                                aria-label={darkMode
                                    ? "Light Mode"
                                    : "Dark Mode"}
                            >
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full
                                           bg-white transition-transform
                                           {darkMode
                                        ? 'translate-x-6'
                                        : 'translate-x-1'}"
                                ></span>
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-[#1a1a1a]">
                        <button
                            onclick={handleLogout}
                            class="w-full flex items-center px-4 py-2.5
                                   hover:bg-red-50 dark:hover:bg-red-900/10
                                   transition-colors text-red-600 dark:text-red-400
                                   text-sm"
                        >
                            <i
                                class="w-4 text-sm text-center fa-solid fa-sign-out-alt"
                            ></i>
                            <span class="ml-3">Logout</span>
                        </button>
                    </div>
                </div>
            {/if}
        </div>
    </div>
</aside>

<style>
    /* Custom Scrollbar */
    :global(.custom-scrollbar::-webkit-scrollbar) {
        width: 6px;
    }
    :global(.custom-scrollbar::-webkit-scrollbar-track) {
        background: transparent;
    }

    /* Ensure toggle button is visible on desktop */
    @media (min-width: 1024px) {
        aside button[onclick*="toggleSidebarDesktop"] {
            display: flex !important;
        }
    }
    :global(.badge-modern) {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
        font-weight: 700;
        font-size: 0.625rem;
        line-height: 1;
        height: 1.25rem;
        min-width: 1.25rem;
        padding: 0 0.35rem;
    }
</style>
