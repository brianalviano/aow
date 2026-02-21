<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type SettingsData = {
        name: string;
        contact_email: string | null;
        whatsapp_number: string | null;
        address: string | null;
        latitude: string | null;
        longitude: string | null;
        bank_name: string | null;
        bank_account_name: string | null;
        bank_account_number: string | null;
    };

    let settings = $derived(
        ($page.props.settings as SettingsData | null) ?? null,
    );

    const form = useForm(
        untrack(() => ({
            name: settings?.name ?? "",
            contact_email: settings?.contact_email ?? "",
            whatsapp_number: settings?.whatsapp_number ?? "",
            address: settings?.address ?? "",
            latitude: settings?.latitude ?? "",
            longitude: settings?.longitude ?? "",
            bank_name: settings?.bank_name ?? "",
            bank_account_name: settings?.bank_account_name ?? "",
            bank_account_number: settings?.bank_account_number ?? "",
        })),
    );

    function backToDashboard() {
        router.visit("/dashboard");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        $form.put("/settings", {
            onSuccess: () => {
                router.visit("/settings");
            },
            preserveScroll: true,
        });
    }
</script>

<svelte:head>
    <title>Pengaturan | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Pengaturan
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola informasi aplikasi dan geofence
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToDashboard}>Kembali</Button
            >
            <Button
                variant="success"
                type="submit"
                loading={$form.processing}
                disabled={$form.processing}
                icon="fa-solid fa-save"
                form="settings-form"
                class="w-full"
            >
                Simpan Pengaturan
            </Button>
        </div>
    </header>

    <form id="settings-form" onsubmit={submitForm}>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card title="Informasi Situs" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TextInput
                                id="name"
                                name="name"
                                label="Nama Situs"
                                placeholder="Nama aplikasi"
                                bind:value={$form.name}
                                error={$form.errors.name}
                                required
                            />
                            <TextInput
                                id="contact_email"
                                name="contact_email"
                                label="Email Kontak"
                                type="email"
                                placeholder="email@domain.com"
                                bind:value={$form.contact_email}
                                error={$form.errors.contact_email}
                            />
                            <TextInput
                                id="whatsapp_number"
                                name="whatsapp_number"
                                label="Nomor WhatsApp"
                                placeholder="08xxxxxxxxxx"
                                bind:value={$form.whatsapp_number}
                                error={$form.errors.whatsapp_number}
                            />
                            <div class="md:col-span-2">
                                <TextArea
                                    id="address"
                                    name="address"
                                    label="Alamat"
                                    placeholder="Alamat kantor"
                                    error={$form.errors.address}
                                    bind:value={$form.address}
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>

                <Card title="Lokasi Geofence" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TextInput
                                id="latitude"
                                name="latitude"
                                label="Latitude"
                                placeholder="-6.200000"
                                bind:value={$form.latitude}
                                error={$form.errors.latitude}
                            />
                            <TextInput
                                id="longitude"
                                name="longitude"
                                label="Longitude"
                                placeholder="106.816666"
                                bind:value={$form.longitude}
                                error={$form.errors.longitude}
                            />
                        </div>
                    {/snippet}
                </Card>
            </div>

            <div>
                <Card title="Informasi Bank" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <TextInput
                                id="bank_name"
                                name="bank_name"
                                label="Nama Bank"
                                placeholder="Nama bank"
                                bind:value={$form.bank_name}
                                error={$form.errors.bank_name}
                            />
                            <TextInput
                                id="bank_account_name"
                                name="bank_account_name"
                                label="Nama Pemilik Rekening"
                                placeholder="Nama pemilik"
                                bind:value={$form.bank_account_name}
                                error={$form.errors.bank_account_name}
                            />
                            <TextInput
                                id="bank_account_number"
                                name="bank_account_number"
                                label="Nomor Rekening"
                                placeholder="1234567890"
                                bind:value={$form.bank_account_number}
                                error={$form.errors.bank_account_number}
                            />
                        </div>
                    {/snippet}
                </Card>
            </div>
        </div>
    </form>
</section>
