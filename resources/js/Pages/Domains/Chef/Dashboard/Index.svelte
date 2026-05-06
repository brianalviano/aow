<script lang="ts">
    import { page, useForm } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { router } from "@inertiajs/svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import OrderCard from "../Components/OrderCard.svelte";

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
        note?: string;
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
</script>

<svelte:head>
    <title>Dashboard Chef | {appName($page.props.settings)}</title>
</svelte:head>

<div class="flex flex-col min-h-screen bg-slate-950 text-slate-100">
    <header
        class="bg-slate-950 border-b border-slate-800 p-4 sticky top-0 z-10 shadow-xl backdrop-blur-md"
    >
        <div class="max-w-7xl mx-auto flex justify-between items-center w-full">
            <div class="flex items-center gap-2">
                <div
                    class="bg-[#FFD700] text-slate-900 p-1.5 rounded-lg shadow-lg shadow-[#FFD700]/20"
                >
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <h1
                    class="text-lg font-black text-slate-100 uppercase tracking-wider"
                >
                    Chef Portal
                </h1>
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

    <main class="flex-1 p-4 max-w-7xl mx-auto w-full space-y-8">
        <div
            class="bg-slate-950 border border-slate-800 rounded-3xl p-8 shadow-2xl relative overflow-hidden"
        >
            <div class="absolute top-0 right-0 p-8 opacity-5">
                <i class="fa-solid fa-utensils text-8xl"></i>
            </div>
            <h2
                class="text-3xl font-black text-slate-100 tracking-tight relative z-10"
            >
                Halo, {$page.props.auth?.user?.name || "Chef"}!
            </h2>
            <p class="text-slate-400 mt-2 font-medium relative z-10">
                Anda memiliki <span class="text-[#FFD700] font-black"
                    >{items.length}</span
                > item pesanan yang menunggu konfirmasi.
            </p>
        </div>

        {#if groupedItems.length === 0}
            <div
                class="bg-slate-950 rounded-3xl border border-slate-800 p-16 text-center shadow-2xl"
            >
                <div
                    class="bg-slate-800 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-700 shadow-inner"
                >
                    <i
                        class="fa-solid fa-clipboard-list text-3xl text-slate-600"
                    ></i>
                </div>
                <h3 class="font-black text-slate-100 text-xl mb-2">
                    Tidak ada pesanan baru
                </h3>
                <p class="text-slate-400 max-w-sm mx-auto">
                    Semua pesanan Anda telah diproses atau belum ada pesanan
                    baru dari Admin.
                </p>
            </div>
        {:else}
            <div class="space-y-6">
                {#each groupedItems as group (group.order.id)}
                    <OrderCard
                        {group}
                        context="dashboard"
                        onApprove={approveItem}
                        onReject={rejectItem}
                        onShip={shipItem}
                    />
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
