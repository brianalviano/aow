<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface PickUpPointOfficer {
        id: string;
        pick_up_point_id: string | null;
        name: string;
        phone: string;
        email: string;
        is_active: boolean;
        created_at: string;
        updated_at: string;
    }

    interface PickUpPoint {
        id: string;
        name: string;
    }

    let officer = $derived(
        ($page.props.officer as { data: PickUpPointOfficer } | null)?.data ??
            null,
    );

    let pickUpPoints = $derived(
        ($page.props.pickUpPoints as PickUpPoint[] | null) ?? [],
    );

    let isEditMode = $derived(!!officer);

    // Format single Select options
    let pickUpPointOptions = $derived([
        { value: "", label: "-- Belum Di-assign --" },
        ...pickUpPoints.map((p) => ({
            value: p.id,
            label: p.name,
        })),
    ]);

    const DEFAULT_FORM_STATE = {
        _method: "post",
        name: "",
        phone: "",
        email: "",
        password: "",
        password_confirmation: "",
        pick_up_point_id: "" as string | null,
        is_active: true,
    };

    const form = useForm(
        untrack(() => ({
            _method: officer ? "put" : "post",
            name: officer?.name ?? DEFAULT_FORM_STATE.name,
            phone: officer?.phone ?? DEFAULT_FORM_STATE.phone,
            email: officer?.email ?? DEFAULT_FORM_STATE.email,
            password: DEFAULT_FORM_STATE.password,
            password_confirmation: DEFAULT_FORM_STATE.password_confirmation,
            pick_up_point_id:
                officer?.pick_up_point_id ??
                DEFAULT_FORM_STATE.pick_up_point_id,
            is_active: officer?.is_active ?? DEFAULT_FORM_STATE.is_active,
        })),
    );

    function backToIndex() {
        router.visit("/admin/pick-up-point-officers");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();

        // Convert empty string to null for optional UUID
        if ($form.pick_up_point_id === "") {
            $form.pick_up_point_id = null;
        }

        if (isEditMode && officer) {
            $form.post(`/admin/pick-up-point-officers/${officer.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    $form.password = "";
                    $form.password_confirmation = "";
                },
            });
        } else {
            $form.post("/admin/pick-up-point-officers", {
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEditMode ? "Edit" : "Tambah"} Pick Up Point Officer | {getSettingName(
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
                {isEditMode ? "Edit Officer" : "Tambah Officer"}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk {isEditMode
                    ? "mengubah"
                    : "menambahkan"} petugas Pick Up Point
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToIndex}>Kembali</Button
            >
            <Button
                variant="success"
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="officer-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="officer-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-6">
                <Card
                    title="Informasi Personal & Assignment"
                    collapsible={false}
                >
                    {#snippet children()}
                        <div class="space-y-4">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama Lengkap"
                                placeholder="Masukkan nama..."
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />

                            <TextInput
                                id="email"
                                name="email"
                                type="email"
                                label="Alamat Email"
                                placeholder="admin@email.com"
                                bind:value={$form.email}
                                error={$form.errors.email}
                                required
                            />

                            <TextInput
                                id="phone"
                                name="phone"
                                label="Nomor WhatsApp"
                                placeholder="08123456789"
                                bind:value={$form.phone}
                                error={$form.errors.phone}
                                required
                            />

                            <Select
                                id="pick_up_point_id"
                                name="pick_up_point_id"
                                label="Assign ke Pick Up Point"
                                options={pickUpPointOptions}
                                value={$form.pick_up_point_id || ""}
                                onchange={(val: string | number) => {
                                    $form.pick_up_point_id =
                                        val === "" ? null : (val as string);
                                }}
                                error={$form.errors.pick_up_point_id}
                            />

                            <div class="flex items-center pt-2">
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    label="Aktif"
                                    bind:checked={$form.is_active}
                                    error={$form.errors.is_active}
                                />
                                <span
                                    class="ml-2 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    (Officer aktif dan dapat login)
                                </span>
                            </div>
                        </div>
                    {/snippet}
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Akun & Kata Sandi" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            {#if isEditMode}
                                <div
                                    class="p-3 mb-4 rounded-md bg-yellow-50 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 text-sm border border-yellow-200 dark:border-yellow-800"
                                >
                                    <i class="fa-solid fa-info-circle mr-2"></i>
                                    Kosongkan kata sandi jika tidak ingin mengubahnya.
                                </div>
                            {/if}

                            <TextInput
                                id="password"
                                name="password"
                                type="password"
                                label="Kata Sandi"
                                placeholder="Minimal 8 karakter"
                                bind:value={$form.password}
                                error={$form.errors.password}
                                required={!isEditMode}
                            />

                            <TextInput
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                label="Konfirmasi Kata Sandi"
                                placeholder="Ketik ulang kata sandi"
                                bind:value={$form.password_confirmation}
                                error={$form.errors.password_confirmation}
                                required={!isEditMode &&
                                    $form.password.length > 0}
                            />
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
