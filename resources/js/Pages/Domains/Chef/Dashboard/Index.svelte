<script lang="ts">
    import { page, useForm, router } from "@inertiajs/svelte";
    import { name as appName } from "@/Lib/Admin/Utils/settings";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";

    let { items = [] } = $props<{ items: any[] }>();

    // @ts-ignore
    const route = window.route;

    const form = useForm({});

    function handleLogout(e: SubmitEvent) {
        e.preventDefault();
        $form.post(route("chef.logout"));
    }

    function approveItem(itemId: string) {
        if (!confirm("Apakah Anda yakin ingin menerima item ini?")) return;

        router.post(route("chef.approve"), {
            item_ids: [itemId],
        });
    }

    function rejectItem(itemId: string) {
        const reason = prompt("Alasan penolakan (opsional):");
        if (reason === null) return;

        if (
            !confirm(
                "Menolak item ini akan membatalkan seluruh pesanan. Lanjutkan?",
            )
        )
            return;

        router.post(route("chef.reject"), {
            item_ids: [itemId],
            reason: reason,
        });
    }

    // Group items by order_id
    const groupedItems = $derived<Record<string, { order: any; items: any[] }>>(
        items.reduce(
            (acc: Record<string, { order: any; items: any[] }>, item: any) => {
                const orderId = item.order.id;
                if (!acc[orderId]) {
                    acc[orderId] = {
                        order: item.order,
                        items: [],
                    };
                }
                acc[orderId].items.push(item);
                return acc;
            },
            {},
        ),
    );
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

    <main class="flex-1 p-4 lg:p-8 max-w-7xl mx-auto w-full">
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

        {#if Object.keys(groupedItems).length === 0}
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
                {#each Object.values(groupedItems) as group}
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
                                    <Badge variant="info" size="sm"
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
                            <div class="text-right">
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
                                        <Button
                                            variant="success"
                                            size="sm"
                                            icon="fa-solid fa-check"
                                            onclick={() => approveItem(item.id)}
                                        >
                                            Terima
                                        </Button>
                                        <Button
                                            variant="danger"
                                            size="sm"
                                            icon="fa-solid fa-xmark"
                                            onclick={() => rejectItem(item.id)}
                                        >
                                            Tolak
                                        </Button>
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
