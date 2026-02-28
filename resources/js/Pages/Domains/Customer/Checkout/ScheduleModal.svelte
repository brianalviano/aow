<script lang="ts">
    import { onMount } from "svelte";
    import { fade, slide } from "svelte/transition";
    import DateInput from "../../../../Lib/Admin/Components/Ui/DateInput.svelte";
    import TimeInput from "../../../../Lib/Admin/Components/Ui/TimeInput.svelte";

    export let initialDateIso: string;
    export let initialTime: string;
    export let minDateIso: string;
    export let onClose: () => void;
    export let onSave: (dateIso: string, time: string) => void;

    let dateIso = initialDateIso || minDateIso;
    let time = initialTime || "08:00";

    // Internal reactive state for display in the modal
    $: dateObject = dateIso ? new Date(dateIso) : null;
    $: dateStr =
        dateObject && !isNaN(dateObject.getTime())
            ? new Intl.DateTimeFormat("id-ID", {
                  day: "numeric",
                  month: "long",
                  year: "numeric",
              }).format(dateObject)
            : "";

    function handleSave() {
        onSave(dateIso, time);
    }

    // Prevent scrolling behind modal
    onMount(() => {
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
    on:click={onClose}
>
    <!-- Modal Content -->
    <!-- svelte-ignore a11y_click_events_have_key_events -->
    <!-- svelte-ignore a11y_no_static_element_interactions -->
    <div
        class="bg-white w-full max-w-md max-h-svh sm:rounded-2xl rounded-t-2xl overflow-hidden flex flex-col relative"
        on:click|stopPropagation
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
                on:click={onClose}
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
                />

                <TimeInput
                    label="Pilih Waktu"
                    bind:value={time}
                    placeholder="Pilih Waktu"
                    showIcon={true}
                />
            </div>

            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                <p class="text-blue-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-info-circle"></i>
                    <span>Pesanan akan dikirim pada:</span>
                </p>
                <p class="text-blue-900 font-bold mt-1">
                    {dateStr} pukul {time} WIB
                </p>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="p-4 border-t border-gray-100 bg-white">
            <button
                class="w-full bg-[#CCFF33] hover:bg-[#bdf33c] text-[#111] font-bold py-3.5 rounded-xl transition-colors flex items-center justify-center gap-2 text-sm shadow-sm"
                on:click={handleSave}
            >
                Simpan Jadwal
            </button>
        </div>
    </div>
</div>

<style>
    /* Add any custom styles here if needed */
</style>
