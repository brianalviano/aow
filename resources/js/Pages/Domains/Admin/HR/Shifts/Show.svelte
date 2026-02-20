<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { formatDateTimeDisplay } from "@/Lib/Admin/Utils/date";

    type ShiftDetail = {
        id: string;
        name: string;
        start_time: string | null;
        end_time: string | null;
        is_overnight: boolean;
        is_off: boolean;
        created_at: string | null;
        updated_at: string | null;
        color?: string | null;
    };

    let shift = $derived($page.props.shift as ShiftDetail);

    function backToList() {
        router.visit("/shifts");
    }

    function editShift() {
        router.visit(`/shifts/${shift.id}/edit`);
    }

    function colorLabel(c: string | null, isOff: boolean): string {
        if (isOff) return "—";
        switch (c) {
            case "indigo":
                return "Indigo";
            case "purple":
                return "Ungu";
            case "blue":
                return "Biru";
            case "green":
                return "Hijau";
            case "red":
                return "Merah";
            case "orange":
                return "Oranye";
            case "pink":
                return "Pink";
            case "teal":
                return "Teal";
            case "cyan":
                return "Cyan";
            case "yellow":
                return "Kuning";
            case "amber":
                return "Kuning";
            default:
                return "Hijau";
        }
    }
</script>

<svelte:head>
    <title>Detail Shift | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Shift
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {shift.name}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            {#if !shift.is_off}
                <Button
                    variant="warning"
                    icon="fa-solid fa-edit"
                    onclick={editShift}>Edit</Button
                >
            {/if}
        </div>
    </header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <Card title="Informasi Shift" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Nama
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {shift.name}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Warna
                            </p>
                            <div class="flex gap-2 items-center">
                                <span
                                    class={"inline-block w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-gray-900 " +
                                        (shift.is_off
                                            ? "bg-gray-400"
                                            : shift.color === "indigo"
                                              ? "bg-indigo-500"
                                              : shift.color === "purple"
                                                ? "bg-purple-500"
                                                : shift.color === "blue"
                                                  ? "bg-blue-500"
                                                  : shift.color === "green"
                                                    ? "bg-green-500"
                                                    : shift.color === "red"
                                                      ? "bg-red-500"
                                                      : shift.color === "orange"
                                                        ? "bg-orange-500"
                                                        : shift.color === "pink"
                                                          ? "bg-pink-500"
                                                          : shift.color ===
                                                              "teal"
                                                            ? "bg-teal-500"
                                                            : shift.color ===
                                                                "cyan"
                                                              ? "bg-cyan-500"
                                                              : shift.color ===
                                                                  "yellow"
                                                                ? "bg-yellow-500"
                                                                : shift.color ===
                                                                    "amber"
                                                                  ? "bg-yellow-500"
                                                                  : "bg-green-500")}
                                    aria-hidden="true"
                                ></span>
                                <span
                                    class="text-sm font-medium text-gray-900 dark:text-white"
                                >
                                    {colorLabel(
                                        shift.color ?? null,
                                        shift.is_off,
                                    )}
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Jam Mulai
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {shift.start_time || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Jam Selesai
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {shift.end_time || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Overnight
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {shift.is_overnight ? "Ya" : "Tidak"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Off
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {shift.is_off ? "Ya" : "Tidak"}
                            </p>
                        </div>
                    </div>
                {/snippet}
            </Card>
        </div>

        <div class="space-y-4">
            <Card title="Informasi Sistem" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Dibuat Pada
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(shift.created_at)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Terakhir Diperbarui
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(shift.updated_at)}
                            </p>
                        </div>
                    </div>
                {/snippet}
            </Card>
        </div>
    </div>
</section>
