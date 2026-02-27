<script lang="ts">
    import { page } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import { name } from "@/Lib/Admin/Utils/settings";

    interface Customer {
        id: string;
        name: string;
        username: string;
        phone: string | null;
        address: string | null;
        email: string | null;
        school_class: string | null;
        is_active: boolean;
        created_at: string;
        updated_at: string;
    }

    let customer = $derived($page.props.customer as { data: Customer });
    let data = $derived(customer.data);

    function formatDate(dateStr: string) {
        return new Date(dateStr).toLocaleDateString("id-ID", {
            year: "numeric",
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    }
</script>

<svelte:head>
    <title>Detail Customer | {name($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Customer
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Informasi lengkap mengenai customer
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                href="/admin/customers"
            >
                Kembali
            </Button>
        </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card title="Profil Utama">
            {#snippet children()}
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Nama Lengkap
                        </div>
                        <div
                            class="mt-1 text-base text-gray-900 dark:text-white"
                        >
                            {data.name}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Username
                        </div>
                        <div
                            class="mt-1 text-base text-gray-900 dark:text-white"
                        >
                            @{data.username}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Status Akun
                        </div>
                        <div class="mt-1">
                            {#if data.is_active}
                                <Badge
                                    size="sm"
                                    rounded="pill"
                                    variant="success"
                                >
                                    {#snippet children()}Aktif{/snippet}
                                </Badge>
                            {:else}
                                <Badge
                                    size="sm"
                                    rounded="pill"
                                    variant="danger"
                                >
                                    {#snippet children()}Nonaktif{/snippet}
                                </Badge>
                            {/if}
                        </div>
                    </div>
                </div>
            {/snippet}
        </Card>

        <Card title="Kontak & Informasi">
            {#snippet children()}
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Email
                        </div>
                        <div
                            class="mt-1 text-base text-gray-900 dark:text-white"
                        >
                            {data.email || "-"}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Nomor Telepon
                        </div>
                        <div
                            class="mt-1 text-base text-gray-900 dark:text-white"
                        >
                            {data.phone || "-"}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Kelas / Sekolah
                        </div>
                        <div
                            class="mt-1 text-base text-gray-900 dark:text-white"
                        >
                            {data.school_class || "-"}
                        </div>
                    </div>
                </div>
            {/snippet}
        </Card>

        <Card title="Informasi Tambahan" class="md:col-span-2">
            {#snippet children()}
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">
                            Alamat Lengkap
                        </div>
                        <div
                            class="mt-1 text-base text-gray-900 dark:text-white"
                        >
                            {data.address || "-"}
                        </div>
                    </div>
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-500">
                                Tanggal Terdaftar
                            </div>
                            <div
                                class="mt-1 text-sm text-gray-900 dark:text-white"
                            >
                                {formatDate(data.created_at)}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">
                                Terakhir Diperbarui
                            </div>
                            <div
                                class="mt-1 text-sm text-gray-900 dark:text-white"
                            >
                                {formatDate(data.updated_at)}
                            </div>
                        </div>
                    </div>
                </div>
            {/snippet}
        </Card>
    </div>
</section>
