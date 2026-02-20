<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TimeInput from "@/Lib/Admin/Components/Ui/TimeInput.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type ShiftData = {
        id: string;
        start_time: string | null;
        end_time: string | null;
        is_overnight: boolean;
        is_off: boolean;
        color?: string | null;
    };

    let shift = $derived($page.props.shift as ShiftData | null);
    let isEdit = $derived(shift !== null);

    const form = useForm(
        untrack(() => ({
            start_time: shift?.start_time ?? "",
            end_time: shift?.end_time ?? "",
            is_overnight: shift?.is_overnight ?? false,
            is_off: shift?.is_off ?? false,
        })),
    );

    type PreviewColor =
        | "gray"
        | "indigo"
        | "purple"
        | "green"
        | "orange"
        | "yellow";
    function pickPreviewColor(isOff: boolean, t: string): PreviewColor {
        if (isOff) return "gray";
        if (!t || !/^\d{2}:\d{2}$/.test(t)) return "green";
        const [hh] = t.split(":");
        const h = Number(hh);
        if (h >= 0 && h <= 4) return "purple";
        if (h >= 5 && h <= 10) return "green";
        if (h >= 11 && h <= 14) return "yellow";
        if (h >= 15 && h <= 17) return "orange";
        return "indigo";
    }
    const previewColor = $derived<PreviewColor>(
        pickPreviewColor($form.is_off, $form.start_time),
    );
    const previewClassMap: Record<Exclude<PreviewColor, "gray">, string> = {
        indigo: "bg-indigo-500",
        purple: "bg-purple-500",
        green: "bg-green-500",
        orange: "bg-orange-500",
        yellow: "bg-yellow-500",
    };
    const previewLabelMap: Record<Exclude<PreviewColor, "gray">, string> = {
        indigo: "Indigo",
        purple: "Ungu",
        green: "Hijau",
        orange: "Oranye",
        yellow: "Kuning",
    };
    const previewLabel = $derived(() =>
        previewColor === "gray"
            ? "Gray (OFF)"
            : previewLabelMap[previewColor as Exclude<PreviewColor, "gray">],
    );

    function toMinutes(t: string): number | null {
        if (!t || !/^\d{2}:\d{2}$/.test(t)) return null;
        const [hh, mm] = t.split(":");
        const h = Number(hh);
        const m = Number(mm);
        if (Number.isNaN(h) || Number.isNaN(m)) return null;
        return h * 60 + m;
    }

    const overnight = $derived(() => {
        if ($form.is_off) return false;
        const sMin = toMinutes($form.start_time);
        const eMin = toMinutes($form.end_time);
        return sMin !== null && eMin !== null && eMin < sMin;
    });

    function backToList() {
        router.visit("/shifts");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        const isOvernightBool = overnight();
        $form.is_overnight = isOvernightBool;
        if ($form.is_off) {
            $form.is_overnight = false;
        }
        if (isEdit && shift) {
            $form.put(`/shifts/${shift.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    router.visit("/shifts");
                },
            });
        } else {
            $form.post("/shifts", {
                preserveScroll: true,
                onSuccess: () => {
                    router.visit("/shifts");
                },
            });
        }
    }

    $effect(() => {
        if ($form.is_off) {
            const s = untrack(() => $form.start_time);
            const e = untrack(() => $form.end_time);
            if (s !== "" || e !== "") {
                $form.start_time = "";
                $form.end_time = "";
            }
            return;
        }
    });
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Shift | {siteName(
            $page.props.settings,
        )}</title
    >
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {isEdit ? "Edit" : "Tambah"} Shift
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Perbarui informasi shift"
                    : "Tambahkan shift kerja baru"}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant={isEdit ? "warning" : "success"}
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="shift-form"
                onclick={() => {
                    const el = document.getElementById(
                        "shift-form",
                    ) as HTMLFormElement | null;
                    el?.requestSubmit();
                }}
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Shift
            </Button>
        </div>
    </header>

    <form id="shift-form" onsubmit={submitForm}>
        <Card title="Informasi Shift" collapsible={false}>
            {#snippet children()}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <TimeInput
                        id="start_time"
                        name="start_time"
                        label="Jam Mulai"
                        placeholder="HH:MM"
                        bind:value={$form.start_time}
                        error={$form.errors.start_time}
                        disabled={$form.is_off}
                        required={!$form.is_off}
                    />
                    <TimeInput
                        id="end_time"
                        name="end_time"
                        label="Jam Selesai"
                        placeholder="HH:MM"
                        bind:value={$form.end_time}
                        error={$form.errors.end_time}
                        disabled={$form.is_off}
                        required={!$form.is_off}
                    />
                    {#if !$form.is_off && $form.start_time && $form.end_time && overnight()}
                        <div
                            class="flex gap-2 items-center px-3 py-2 text-sm text-yellow-800 bg-yellow-50 rounded border border-yellow-300 md:col-span-2 dark:bg-yellow-900/20 dark:text-yellow-200"
                            role="status"
                            aria-live="polite"
                        >
                            <span
                                class="inline-flex w-2 h-2 bg-yellow-500 rounded-full"
                                aria-hidden="true"
                            ></span>
                            <span
                                >Jam yang anda pilih melewati tengah malam.</span
                            >
                        </div>
                    {/if}
                </div>
            {/snippet}
        </Card>
        <Card title="Warna Otomatis" collapsible={false}>
            {#snippet children()}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="flex gap-3 items-center">
                        <span
                            class={"inline-block w-3 h-3 rounded-full ring-2 ring-white dark:ring-gray-900 " +
                                (previewColor === "gray"
                                    ? "bg-gray-400"
                                    : previewClassMap[previewColor])}
                            aria-hidden="true"
                        ></span>
                        <div class="text-sm text-gray-900 dark:text-white">
                            Otomatis: {previewLabel()}
                        </div>
                    </div>
                </div>
            {/snippet}
        </Card>
    </form>
</section>
