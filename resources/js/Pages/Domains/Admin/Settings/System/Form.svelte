<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import TextArea from "@/Lib/Admin/Components/Ui/TextArea.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import TimeInput from "@/Lib/Admin/Components/Ui/TimeInput.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    type SettingsData = {
        name: string;
        email: string | null;
        phone: string | null;
        whatsapp: string | null;
        address: string | null;
        instagram: string | null;
        facebook: string | null;
        tiktok: string | null;

        order_cutoff_time: string | null;
        order_min_days_ahead: string | null;

        delivery_fee_mode: string | null;
        delivery_fee_flat: string | null;
        free_courier_min_order: string | null;

        admin_fee_enabled: boolean;
        admin_fee_type: string | null;
        admin_fee_value: string | null;

        payment_expired_duration: string | null;

        telegram_enabled: boolean;
        telegram_bot_token: string | null;
        telegram_admin_chat_id: string | null;
        telegram_notify_order_created: boolean;
        telegram_notify_order_paid: boolean;
        telegram_notify_order_cancelled: boolean;

        whatsapp_enabled: boolean;
        whatsapp_access_token: string | null;
        whatsapp_phone_id: string | null;
        whatsapp_notify_order_created: boolean;
        whatsapp_notify_order_confirmed: boolean;
        whatsapp_notify_order_delivered: boolean;
    };

    let settings = $derived(
        ($page.props.settings as SettingsData | null) ?? null,
    );

    const form = useForm(
        untrack(() => ({
            name: settings?.name ?? "",
            email: settings?.email ?? "",
            phone: settings?.phone ?? "",
            whatsapp: settings?.whatsapp ?? "",
            address: settings?.address ?? "",
            instagram: settings?.instagram ?? "",
            facebook: settings?.facebook ?? "",
            tiktok: settings?.tiktok ?? "",

            order_cutoff_time: settings?.order_cutoff_time ?? "20:00",
            order_min_days_ahead:
                settings?.order_min_days_ahead?.toString() ?? "1",

            delivery_fee_mode: settings?.delivery_fee_mode ?? "per_drop_point",
            delivery_fee_flat: settings?.delivery_fee_flat?.toString() ?? "0",
            free_courier_min_order:
                settings?.free_courier_min_order?.toString() ?? "0",

            admin_fee_enabled: settings?.admin_fee_enabled ?? false,
            admin_fee_type: settings?.admin_fee_type ?? "fixed",
            admin_fee_value: settings?.admin_fee_value?.toString() ?? "0",

            payment_expired_duration:
                settings?.payment_expired_duration?.toString() ?? "60",

            telegram_enabled: settings?.telegram_enabled ?? false,
            telegram_bot_token: settings?.telegram_bot_token ?? "",
            telegram_admin_chat_id: settings?.telegram_admin_chat_id ?? "",
            telegram_notify_order_created:
                settings?.telegram_notify_order_created ?? false,
            telegram_notify_order_paid:
                settings?.telegram_notify_order_paid ?? false,
            telegram_notify_order_cancelled:
                settings?.telegram_notify_order_cancelled ?? false,

            whatsapp_enabled: settings?.whatsapp_enabled ?? false,
            whatsapp_access_token: settings?.whatsapp_access_token ?? "",
            whatsapp_phone_id: settings?.whatsapp_phone_id ?? "",
            whatsapp_notify_order_created:
                settings?.whatsapp_notify_order_created ?? false,
            whatsapp_notify_order_confirmed:
                settings?.whatsapp_notify_order_confirmed ?? false,
            whatsapp_notify_order_delivered:
                settings?.whatsapp_notify_order_delivered ?? false,
        })),
    );

    function backToDashboard() {
        router.visit("/admin/dashboard");
    }

    function submitForm(e: SubmitEvent) {
        e.preventDefault();
        $form.put("/admin/settings", {
            onSuccess: () => {
                // Refresh is automatic if redirect back
            },
            preserveScroll: true,
        });
    }
</script>

<svelte:head>
    <title>Pengaturan Sistem | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Pengaturan Sistem
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola informasi perusahaan dan pengaturan order aplikasi.
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
                class="w-full sm:w-auto"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form id="settings-form" onsubmit={submitForm} class="space-y-10">
        <!-- Bagian Profil Perusahaan -->
        <section>
            <div
                class="mb-5 border-b border-gray-200 pb-3 dark:border-gray-700"
            >
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Pengaturan Perusahaan
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Kelola profil usaha, kontak, dan tautan media sosial.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
                <Card title="Profil Perusahaan" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <TextInput
                                    id="name"
                                    name="name"
                                    label="Nama Usaha"
                                    placeholder="Nama aplikasi/usaha"
                                    bind:value={$form.name}
                                    error={$form.errors.name}
                                    required
                                />
                            </div>
                            <TextInput
                                id="email"
                                name="email"
                                label="Email"
                                type="email"
                                placeholder="email@domain.com"
                                bind:value={$form.email}
                                error={$form.errors.email}
                            />
                            <TextInput
                                id="phone"
                                name="phone"
                                label="No. Telepon"
                                placeholder="021xxxxxxx"
                                bind:value={$form.phone}
                                error={$form.errors.phone}
                            />
                            <TextInput
                                id="whatsapp"
                                name="whatsapp"
                                label="No. WhatsApp"
                                placeholder="628xxxxxxx"
                                bind:value={$form.whatsapp}
                                error={$form.errors.whatsapp}
                            />
                            <div class="md:col-span-2">
                                <TextArea
                                    id="address"
                                    name="address"
                                    label="Alamat"
                                    placeholder="Alamat lengkap"
                                    error={$form.errors.address}
                                    bind:value={$form.address}
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>

                <Card title="Media Sosial" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <TextInput
                                id="instagram"
                                name="instagram"
                                label="URL / Username Instagram"
                                placeholder="https://instagram.com/..."
                                bind:value={$form.instagram}
                                error={$form.errors.instagram}
                            />
                            <TextInput
                                id="facebook"
                                name="facebook"
                                label="URL / Username Facebook"
                                placeholder="https://facebook.com/..."
                                bind:value={$form.facebook}
                                error={$form.errors.facebook}
                            />
                            <TextInput
                                id="tiktok"
                                name="tiktok"
                                label="URL / Username TikTok"
                                placeholder="https://tiktok.com/@..."
                                bind:value={$form.tiktok}
                                error={$form.errors.tiktok}
                            />
                        </div>
                    {/snippet}
                </Card>
            </div>
        </section>

        <!-- Bagian Sistem -->
        <section>
            <div
                class="mb-5 border-b border-gray-200 pb-3 dark:border-gray-700"
            >
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Pengaturan Sistem
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Konfigurasi operasional aplikasi seperti order, biaya
                    tambahan, dan opsi notifikasi.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
                <Card title="Pengaturan Order & Waktu" collapsible={false}>
                    {#snippet children()}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <TimeInput
                                id="order_cutoff_time"
                                name="order_cutoff_time"
                                label="Batas Order (Cut-off)"
                                bind:value={$form.order_cutoff_time}
                                error={$form.errors.order_cutoff_time}
                            />
                            <TextInput
                                id="order_min_days_ahead"
                                name="order_min_days_ahead"
                                type="number"
                                label="Minimal Hari Pemesanan Sebelumnya"
                                bind:value={$form.order_min_days_ahead}
                                error={$form.errors.order_min_days_ahead}
                            />
                            <div class="md:col-span-2">
                                <TextInput
                                    id="payment_expired_duration"
                                    name="payment_expired_duration"
                                    type="number"
                                    label="Batas Waktu Pembayaran (Menit)"
                                    bind:value={$form.payment_expired_duration}
                                    error={$form.errors
                                        .payment_expired_duration}
                                />
                            </div>
                        </div>
                    {/snippet}
                </Card>

                <Card title="Ongkos Kirim" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <Select
                                id="delivery_fee_mode"
                                value={$form.delivery_fee_mode}
                                onchange={(val) =>
                                    ($form.delivery_fee_mode = val.toString())}
                                label="Mode Ongkir"
                                options={[
                                    {
                                        label: "Hitung Per Titik Antar",
                                        value: "per_drop_point",
                                    },
                                    {
                                        label: "Flat (Pukul Rata)",
                                        value: "flat",
                                    },
                                    { label: "Gratis Semua", value: "free" },
                                ]}
                                error={$form.errors.delivery_fee_mode}
                            />

                            {#if $form.delivery_fee_mode === "flat"}
                                <TextInput
                                    id="delivery_fee_flat"
                                    name="delivery_fee_flat"
                                    type="number"
                                    label="Nominal Ongkir Flat (Rp)"
                                    bind:value={$form.delivery_fee_flat}
                                    error={$form.errors.delivery_fee_flat}
                                />
                            {/if}

                            <TextInput
                                id="free_courier_min_order"
                                name="free_courier_min_order"
                                type="number"
                                label="Minimal Belanja untuk Gratis Ongkir (Rp)"
                                bind:value={$form.free_courier_min_order}
                                error={$form.errors.free_courier_min_order}
                            />
                        </div>
                    {/snippet}
                </Card>

                <Card title="Biaya Admin App" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <Checkbox
                                id="admin_fee_enabled"
                                bind:checked={$form.admin_fee_enabled}
                                label="Aktifkan Biaya Layanan Aplikasi"
                            />
                            {#if $form.admin_fee_enabled}
                                <Select
                                    id="admin_fee_type"
                                    value={$form.admin_fee_type}
                                    onchange={(val) =>
                                        ($form.admin_fee_type = val.toString())}
                                    label="Tipe Biaya"
                                    options={[
                                        {
                                            label: "Nominal Tetap",
                                            value: "fixed",
                                        },
                                        {
                                            label: "Persentase (%)",
                                            value: "percentage",
                                        },
                                    ]}
                                />
                                <TextInput
                                    id="admin_fee_value"
                                    name="admin_fee_value"
                                    type="number"
                                    label="Nilai Biaya Admin"
                                    bind:value={$form.admin_fee_value}
                                    error={$form.errors.admin_fee_value}
                                />
                            {/if}
                        </div>
                    {/snippet}
                </Card>
            </div>
        </section>

        <!-- Bagian Notifikasi -->
        <section>
            <div
                class="mb-5 border-b border-gray-200 pb-3 dark:border-gray-700"
            >
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Pengaturan Notifikasi
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Atur koneksi notifikasi otomatis via Telegram dan WhatsApp.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
                <Card title="Notifikasi Telegram" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <Checkbox
                                id="telegram_enabled"
                                bind:checked={$form.telegram_enabled}
                                label="Aktifkan Notifikasi Telegram (Admin)"
                            />

                            {#if $form.telegram_enabled}
                                <TextInput
                                    id="telegram_bot_token"
                                    name="telegram_bot_token"
                                    label="Bot Token (@BotFather)"
                                    type="password"
                                    bind:value={$form.telegram_bot_token}
                                    error={$form.errors.telegram_bot_token}
                                />
                                <TextInput
                                    id="telegram_admin_chat_id"
                                    name="telegram_admin_chat_id"
                                    label="Chat ID Admin"
                                    bind:value={$form.telegram_admin_chat_id}
                                    error={$form.errors.telegram_admin_chat_id}
                                />

                                <div class="space-y-2 pt-2">
                                    <p
                                        class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        Pemicu Kirim:
                                    </p>
                                    <Checkbox
                                        id="tl_create"
                                        bind:checked={
                                            $form.telegram_notify_order_created
                                        }
                                        label="Ada Order Masuk"
                                    />
                                    <Checkbox
                                        id="tl_paid"
                                        bind:checked={
                                            $form.telegram_notify_order_paid
                                        }
                                        label="Order Berhasil Dibayar"
                                    />
                                    <Checkbox
                                        id="tl_cancel"
                                        bind:checked={
                                            $form.telegram_notify_order_cancelled
                                        }
                                        label="Order Dibatalkan"
                                    />
                                </div>
                            {/if}
                        </div>
                    {/snippet}
                </Card>

                <Card title="Notifikasi WhatsApp" collapsible={false}>
                    {#snippet children()}
                        <div class="space-y-4">
                            <Checkbox
                                id="whatsapp_enabled"
                                bind:checked={$form.whatsapp_enabled}
                                label="Aktifkan Notifikasi WhatsApp (Customer)"
                            />

                            {#if $form.whatsapp_enabled}
                                <TextInput
                                    id="whatsapp_access_token"
                                    name="whatsapp_access_token"
                                    type="password"
                                    label="Access Token WHATSAPP"
                                    bind:value={$form.whatsapp_access_token}
                                    error={$form.errors.whatsapp_access_token}
                                />
                                <TextInput
                                    id="whatsapp_phone_id"
                                    name="whatsapp_phone_id"
                                    label="Phone Number ID"
                                    bind:value={$form.whatsapp_phone_id}
                                    error={$form.errors.whatsapp_phone_id}
                                />

                                <div class="space-y-2 pt-2">
                                    <p
                                        class="text-sm font-semibold text-gray-700 dark:text-gray-300"
                                    >
                                        Pemicu Kirim:
                                    </p>
                                    <Checkbox
                                        id="wa_create"
                                        bind:checked={
                                            $form.whatsapp_notify_order_created
                                        }
                                        label="Saat Order Dibuat"
                                    />
                                    <Checkbox
                                        id="wa_confirm"
                                        bind:checked={
                                            $form.whatsapp_notify_order_confirmed
                                        }
                                        label="Saat Order Dikonfirmasi Admin"
                                    />
                                    <Checkbox
                                        id="wa_deliver"
                                        bind:checked={
                                            $form.whatsapp_notify_order_delivered
                                        }
                                        label="Saat Order Dikirim"
                                    />
                                </div>
                            {/if}
                        </div>
                    {/snippet}
                </Card>
            </div>
        </section>
    </form>
</section>
