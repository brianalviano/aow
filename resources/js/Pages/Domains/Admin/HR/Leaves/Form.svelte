<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type Option = { value: string; label: string };
    type LeaveData = {
        id: string;
        start_date: string;
        end_date: string;
        type: { value: string; label: string };
        reason: string | null;
    };

    let leave = $derived(($page.props.leave as LeaveData | null) ?? null);
    let types = $derived(($page.props.types as Option[]) ?? []);
    let isEdit = $derived(leave !== null);

    const form = useForm(
        untrack(() => ({
            start_date: leave?.start_date ?? "",
            end_date: leave?.end_date ?? "",
            type: leave?.type?.value ?? "",
            reason: leave?.reason ?? "",
        })),
    );

    function backToList() {
        router.visit("/leaves");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        if (isEdit && leave) {
            $form.put(`/leaves/${leave.id}`, {
                onSuccess: () => router.visit("/leaves"),
                preserveScroll: true,
            });
        } else {
            $form.post("/leaves", {
                onSuccess: () => router.visit("/leaves"),
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Ajukan"} Izin | {siteName(
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
                {isEdit ? "Edit" : "Ajukan"} Izin
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit
                    ? "Perbarui pengajuan izin"
                    : "Isi form untuk mengajukan izin"}
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
                form="leave-form"
                class="w-full"
            >
                {isEdit ? "Perbarui" : "Simpan"} Izin
            </Button>
        </div>
    </header>

    <form id="leave-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card title="Detail Izin" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <DateInput
                                id="start_date"
                                name="start_date"
                                label="Tanggal Mulai"
                                bind:value={$form.start_date}
                            />
                            <DateInput
                                id="end_date"
                                name="end_date"
                                label="Tanggal Selesai"
                                bind:value={$form.end_date}
                            />
                            <div class="md:col-span-2">
                                <Select
                                    id="type"
                                    name="type"
                                    label="Tipe Izin"
                                    options={types}
                                    bind:value={$form.type}
                                />
                            </div>
                            <div class="md:col-span-2">
                                <TextArea
                                    id="reason"
                                    name="reason"
                                    label="Alasan"
                                    placeholder="Alasan izin..."
                                    bind:value={$form.reason}
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>
            </div>
            <div class="space-y-6">
                <Card title="Catatan">
                    {#snippet children()}
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Pastikan tanggal dan tipe izin sesuai dengan
                            kebijakan perusahaan.
                        </p>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
