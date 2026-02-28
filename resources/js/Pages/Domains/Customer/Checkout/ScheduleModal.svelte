<script lang="ts">
    import { onMount } from "svelte";
    import { fade, slide } from "svelte/transition";
    import DateInput from "../../../../Lib/Admin/Components/Ui/DateInput.svelte";
    import TimeInput from "../../../../Lib/Admin/Components/Ui/TimeInput.svelte";

    interface Props {
        initialDateIso?: string;
        initialTime?: string;
        minDateIso: string;
        orderType?: "instant" | "preorder";
        onClose: () => void;
        onSave: (dateIso: string, time: string) => void;
    }

    let {
        initialDateIso = "",
        initialTime = "",
        minDateIso,
        orderType = "preorder",
        onClose,
        onSave,
    }: Props = $props();

    let dateIso = $state("");
    let time = $state("");
    let error = $state("");

    // Sync props to state if props change (e.g. from parent)
    $effect(() => {
        dateIso = initialDateIso || minDateIso;
    });

    $effect(() => {
        time = initialTime || "";
    });

    // Internal reactive state for display in the modal
    const dateObject = $derived(dateIso ? new Date(dateIso) : null);
    const dateStr = $derived(
        dateObject && !isNaN(dateObject.getTime())
            ? new Intl.DateTimeFormat("id-ID", {
                  day: "numeric",
                  month: "long",
                  year: "numeric",
              }).format(dateObject)
            : "",
    );

    // Instant order check: lock to today and check min time
    const isToday = $derived(
        dateIso === new Date().toISOString().split("T")[0],
    );

    const minTime = $derived.by(() => {
        if (orderType === "instant" && isToday) {
            const now = new Date();
            // Add 30 minutes buffer for instant
            now.setMinutes(now.getMinutes() + 30);
            return `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}`;
        }
        return "";
    });

    function handleSave() {
        if (!dateIso || !time) {
            error = "Pilih tanggal dan waktu pengiriman";
            return;
        }

        if (minTime && time < minTime) {
            error = `Waktu minimal untuk hari ini adalah ${minTime}`;
            return;
        }

        error = "";
        onSave(dateIso, time);
    }

    // Prevent scrolling behind modal
    onMount(() => {
        // If instant and no initial time, set to minTime
        if (orderType === "instant" && !time && minTime) {
            time = minTime;
        }

        document.body.style.overflow = "hidden";
        return () => {
            document.body.style.overflow = "";
        };
    });
</script>

<!-- svelte-ignore a11y_click_events_have_key_events -->
<!-- svelte-ignore a11y_no_static_element_interactions -->
<div
    class="fixed inset-0 bg-black/50 z-50 flex items-end justify-center sm:items-center"
    transition:fade={{ duration: 200 }}
    onclick={onClose}
>
    <!-- Modal Content -->
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div
        class="bg-white w-full max-w-md max-h-svh sm:rounded-2xl rounded-t-2xl flex flex-col relative"
        onclick={(e) => e.stopPropagation()}
        transition:slide={{ duration: 300, axis: "y" }}
    >
        <!-- Header -->
        <div
            class="p-4 border-b border-gray-100 flex items-center justify-between"
        >
            <h2 class="text-lg font-bold text-gray-900">
                Ubah Jadwal Pengiriman
            </h2>
            <button
                class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center text-gray-800"
                onclick={onClose}
                aria-label="Tutup"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6 mb-10">
            <div class="space-y-4">
                <DateInput
                    label="Pilih Tanggal"
                    bind:value={dateIso}
                    min={minDateIso}
                    placeholder="Pilih Tanggal"
                    showIcon={true}
                    disabled={orderType === "instant"}
                />

                <TimeInput
                    label="Pilih Waktu"
                    bind:value={time}
                    placeholder="Pilih Waktu"
                    showIcon={true}
                    min={minTime}
                />

                {#if error}
                    <p class="text-red-500 text-xs mt-1 animate-pulse">
                        <i class="fa-solid fa-circle-exclamation mr-1"></i>
                        {error}
                    </p>
                {/if}
            </div>

            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                <p class="text-blue-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-info-circle"></i>
                    <span>Pesanan akan dikirim pada:</span>
                </p>
                <p class="text-blue-900 font-bold mt-1">
                    {#if dateIso && time}
                        {dateStr} pukul {time} WIB
                    {:else}
                        Pilih jadwal pengiriman
                    {/if}
                </p>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="p-4 border-t border-gray-100 bg-white">
            <button
                class="w-full bg-[#FFD700] hover:bg-[#FFC700] text-[#111] font-bold py-3.5 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm shadow-sm"
                onclick={handleSave}
            >
                Simpan Jadwal
            </button>
        </div>
    </div>
</div>

<style>
    /* Add any custom styles here if needed */
</style>
