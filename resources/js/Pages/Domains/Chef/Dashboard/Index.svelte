<script lang="ts">
    import { page, useForm } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { router } from "@inertiajs/svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";

    interface Product {
        id: string;
        name: string;
        image?: string;
    }

    interface Order {
        id: string;
        number: string;
        delivery_date: string;
        delivery_time?: string;
        order_status: string;
        customer?: {
            name: string;
        };
        drop_point?: {
            name: string;
        };
    }

    interface Item {
        id: string;
        quantity: number;
        note?: string;
        chef_status: string;
        product?: Product;
        order: Order;
    }

    interface Group {
        order: Order;
        items: Item[];
    }

    let { items = [] } = $props<{ items: Item[] }>();

    const form = useForm({});

    /**
     * Dialog state for confirmation modals.
     */
    let dialogState = $state({
        isOpen: false,
        type: "info" as "info" | "warning" | "danger" | "success",
        title: "",
        message: "",
        confirmText: "Ya, Saya Yakin",
        cancelText: "Batal",
        loading: false,
        formFields: [] as any[],
        onConfirm: async (data?: any) => {},
    });

    function handleLogout(e: SubmitEvent) {
        e.preventDefault();
        $form.post("/chef/logout");
    }

    function approveItem(itemId: string) {
        dialogState = {
            isOpen: true,
            type: "success",
            title: "Konfirmasi Penerimaan",
            message: "Apakah Anda yakin ingin menerima item ini?",
            confirmText: "Ya, Terima",
            cancelText: "Batal",
            loading: false,
            formFields: [],
            onConfirm: async () => {
                dialogState.loading = true;
                router.post(
                    "/chef/orders/approve",
                    {
                        item_ids: [itemId],
                    },
                    {
                        onFinish: () => {
                            dialogState.isOpen = false;
                            dialogState.loading = false;
                        },
                    },
                );
            },
        };
    }

    function rejectItem(itemId: string) {
        dialogState = {
            isOpen: true,
            type: "danger",
            title: "Konfirmasi Penolakan",
            message:
                "Menolak item ini akan membatalkan seluruh pesanan. Apakah Anda yakin?",
            confirmText: "Ya, Tolak",
            cancelText: "Batal",
            loading: false,
            formFields: [
                {
                    id: "reason",
                    name: "reason",
                    type: "textarea",
                    label: "Alasan Penolakan (opsional)",
                    placeholder: "Berikan alasan jika ada...",
                    required: false,
                },
            ],
            onConfirm: async (formData) => {
                dialogState.loading = true;
                router.post(
                    "/chef/orders/reject",
                    {
                        item_ids: [itemId],
                        reason: formData?.reason,
                    },
                    {
                        onFinish: () => {
                            dialogState.isOpen = false;
                            dialogState.loading = false;
                        },
                    },
                );
            },
        };
    }

    function shipItem(itemId: string) {
        dialogState = {
            isOpen: true,
            type: "info",
            title: "Tandai Dikirim",
            message: "Apakah Anda yakin item ini sudah siap untuk dikirim?",
            confirmText: "Ya, Tandai",
            cancelText: "Batal",
            loading: false,
            formFields: [],
            onConfirm: async () => {
                dialogState.loading = true;
                router.post(
                    "/chef/orders/ship",
                    {
                        item_ids: [itemId],
                    },
                    {
                        onFinish: () => {
                            dialogState.isOpen = false;
                            dialogState.loading = false;
                        },
                    },
                );
            },
        };
    }

    function deliverItem(itemId: string) {
        dialogState = {
            isOpen: true,
            type: "success",
            title: "Tandai Selesai / Diterima",
            message: "Tandai item ini telah berhasil dikirim dan diselesaikan?",
            confirmText: "Ya, Selesai",
            cancelText: "Batal",
            loading: false,
            formFields: [
                {
                    id: "delivery_photo",
                    name: "delivery_photo",
                    type: "file",
                    label: "Foto Bukti Pengiriman (Opsional)",
                    required: false,
                },
            ],
            onConfirm: async (formData) => {
                dialogState.loading = true;
                const uploadData = new FormData();
                uploadData.append("item_ids[0]", itemId);
                if (formData?.delivery_photo) {
                    uploadData.append(
                        "delivery_photo",
                        formData.delivery_photo,
                    );
                }

                router.post("/chef/orders/deliver", uploadData, {
                    onFinish: () => {
                        dialogState.isOpen = false;
                        dialogState.loading = false;
                    },
                });
            },
        };
    }

    // Group items by order_id
    const groupedItems = $derived(
        Object.values(
            items.reduce((acc: Record<string, Group>, item: Item) => {
                const orderId = item.order.id;
                if (!acc[orderId]) {
                    acc[orderId] = {
                        order: item.order,
                        items: [],
                    };
                }
                acc[orderId].items.push(item);
                return acc;
            }, {}),
        ) as Group[],
    );

    function isAllItemsApproved(order: Order) {
        const orderItems = (order as any).items || [];
        if (orderItems.length === 0) return true;

        return orderItems.every(
            (item: any) =>
                item.chef_status !== "pending" &&
                item.chef_status !== "rejected",
        );
    }
</script>

<svelte:head>
    <title>Dashboard Chef | {appName($page.props.settings)}</title>
</svelte:head>

<div class="flex flex-col min-h-screen bg-gray-50">
    <header
        class="bg-white border-b border-gray-100 p-4 sticky top-0 z-10 shadow-sm"
    >
        <div class="max-w-7xl mx-auto flex justify-between items-center w-full">
            <div class="flex items-center gap-2">
                <div class="bg-orange-500 text-white p-1.5 rounded-lg">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h1 class="text-lg font-bold text-gray-900">Chef Portal</h1>
            </div>
            <form onsubmit={handleLogout}>
                <Button
                    type="submit"
                    variant="danger"
                    size="sm"
                    icon="fa-solid fa-right-from-bracket"
                >
                    Keluar
                </Button>
            </form>
        </div>
    </header>

    <main class="flex-1 p-4 max-w-7xl mx-auto w-full">
        <div
            class="bg-white border border-gray-100 rounded-2xl p-6 mb-8 shadow-sm"
        >
            <h2 class="text-2xl font-bold text-gray-900">
                Halo, {$page.props.auth?.user?.name || "Chef"}!
            </h2>
            <p class="text-gray-500 mt-1">
                Anda memiliki {items.length} item pesanan yang menunggu konfirmasi.
            </p>
        </div>

        {#if groupedItems.length === 0}
            <div
                class="bg-white rounded-2xl border border-gray-100 p-12 text-center shadow-sm"
            >
                <div
                    class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
                >
                    <i class="fa-solid fa-clipboard-list text-2xl text-gray-300"
                    ></i>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">
                    Tidak ada pesanan baru
                </h3>
                <p class="text-gray-500">
                    Semua pesanan Anda telah diproses atau belum ada pesanan
                    baru dari Admin.
                </p>
            </div>
        {:else}
            <div class="space-y-6">
                {#each groupedItems as group (group.order.id)}
                    <div
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                    >
                        <div
                            class="bg-gray-50/50 p-4 border-b border-gray-100 flex flex-wrap justify-between items-center gap-4"
                        >
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span
                                        class="text-xs font-bold text-gray-400 uppercase tracking-wider"
                                        >Nomor Pesanan</span
                                    >
                                    <Badge variant="info" size="sm" outlined
                                        >{group.order.number}</Badge
                                    >
                                </div>
                                <div class="text-sm font-medium text-gray-900">
                                    {group.order.customer?.name}
                                    <span class="text-gray-400 mx-1">•</span>
                                    <span class="text-gray-500 font-normal"
                                        >{group.order.drop_point?.name ||
                                            "Alamat Kustom"}</span
                                    >
                                </div>
                            </div>
                            <div>
                                <span class="text-xs text-gray-400 block mb-1"
                                    >Tanggal Pengiriman</span
                                >
                                <span
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    {new Date(
                                        group.order.delivery_date,
                                    ).toLocaleDateString("id-ID", {
                                        weekday: "long",
                                        day: "numeric",
                                        month: "long",
                                        year: "numeric",
                                    })}
                                    {#if group.order.delivery_time}
                                        <span class="text-gray-400 mx-1">•</span
                                        >
                                        {group.order.delivery_time.includes("T")
                                            ? new Date(
                                                  group.order.delivery_time,
                                              ).toLocaleTimeString("id-ID", {
                                                  hour: "2-digit",
                                                  minute: "2-digit",
                                              })
                                            : group.order.delivery_time.substring(
                                                  0,
                                                  5,
                                              )} WIB
                                    {/if}
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-gray-100">
                            {#each group.items as item}
                                <div
                                    class="p-4 flex flex-wrap items-center justify-between gap-4"
                                >
                                    <div class="flex items-center gap-4">
                                        {#if item.product?.image}
                                            <img
                                                src={item.product.image}
                                                alt={item.product.name}
                                                class="w-16 h-16 rounded-xl object-cover border border-gray-100"
                                            />
                                        {:else}
                                            <div
                                                class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center border border-gray-100"
                                            >
                                                <i
                                                    class="fa-solid fa-bowl-food text-gray-300"
                                                ></i>
                                            </div>
                                        {/if}
                                        <div>
                                            <h4 class="font-bold text-gray-900">
                                                {item.product?.name}
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                Jumlah: <span
                                                    class="font-semibold text-gray-900"
                                                    >{item.quantity}x</span
                                                >
                                            </p>
                                            {#if item.note}
                                                <p
                                                    class="text-xs text-orange-600 bg-orange-50 px-2 py-0.5 rounded mt-1 inline-block"
                                                >
                                                    <i
                                                        class="fa-solid fa-comment-dots mr-1"
                                                    ></i>
                                                    {item.note}
                                                </p>
                                            {/if}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        {#if item.chef_status === "pending"}
                                            <Button
                                                variant="success"
                                                size="sm"
                                                icon="fa-solid fa-check"
                                                onclick={() =>
                                                    approveItem(item.id)}
                                            >
                                                Terima
                                            </Button>
                                            <Button
                                                variant="outline-danger"
                                                size="sm"
                                                icon="fa-solid fa-xmark"
                                                onclick={() =>
                                                    rejectItem(item.id)}
                                            >
                                                Tolak
                                            </Button>
                                        {:else if item.chef_status === "accepted" && group.order.order_status === "confirmed" && isAllItemsApproved(group.order)}
                                            <Button
                                                variant="primary"
                                                size="sm"
                                                icon="fa-solid fa-truck"
                                                onclick={() =>
                                                    shipItem(item.id)}
                                            >
                                                Kirim
                                            </Button>
                                        {:else if item.chef_status === "shipped"}
                                            <Button
                                                variant="success"
                                                size="sm"
                                                icon="fa-solid fa-circle-check"
                                                onclick={() =>
                                                    deliverItem(item.id)}
                                            >
                                                Selesai
                                            </Button>
                                        {/if}
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
                {/each}
            </div>
        {/if}
    </main>
</div>

<Dialog
    bind:isOpen={dialogState.isOpen}
    type={dialogState.type}
    title={dialogState.title}
    message={dialogState.message}
    confirmText={dialogState.confirmText}
    cancelText={dialogState.cancelText}
    loading={dialogState.loading}
    form_fields={dialogState.formFields}
    onConfirm={dialogState.onConfirm}
/>
