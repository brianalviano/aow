<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";
    import {
        formatDateDisplay,
        formatDateTimeDisplay,
    } from "@/Lib/Admin/Utils/date";

    type EmployeeDetail = {
        id: string;
        name: string;
        username: string;
        email: string;
        phone_number: string | null;
        address: string | null;
        birth_date: string | null;
        join_date: string | null;
        gender: string | null;
        role: { id: string | null; name: string | null };
        photo_url: string | null;
        created_at: string | null;
        updated_at: string | null;
    };

    let employee = $derived($page.props.employee as EmployeeDetail);

    function backToList() {
        router.visit("/employees");
    }

    function editEmployee() {
        router.visit(`/employees/${employee.id}/edit`);
    }
</script>

<svelte:head>
    <title>Detail Karyawan | {siteName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Detail Karyawan
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {employee.name} - {employee.username} - {employee.email}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 items-center sm:justify-end">
            <Button
                variant="secondary"
                icon="fa-solid fa-arrow-left"
                onclick={backToList}>Kembali</Button
            >
            <Button
                variant="warning"
                icon="fa-solid fa-edit"
                onclick={editEmployee}>Edit</Button
            >
        </div>
    </header>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <Card title="Informasi Karyawan" collapsible={false}>
                {#snippet children()}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Nama
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {employee.name}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Email
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {employee.email}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Username
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {employee.username}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Role
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {employee.role?.name || "-"}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Tanggal Bergabung
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateDisplay(employee.join_date)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Tanggal Lahir
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateDisplay(employee.birth_date)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Telepon
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {employee.phone_number || "-"}
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Alamat
                            </p>
                            {#if employee.address}
                                <p
                                    class="mt-1 text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {employee.address}
                                </p>
                            {:else}
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada alamat.
                                </p>
                            {/if}
                        </div>
                    </div>
                {/snippet}
            </Card>

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
                                {formatDateTimeDisplay(employee.created_at)}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Terakhir Diperbarui
                            </p>
                            <p
                                class="text-sm font-medium text-gray-900 dark:text-white"
                            >
                                {formatDateTimeDisplay(employee.updated_at)}
                            </p>
                        </div>
                    </div>
                {/snippet}
            </Card>
        </div>

        <div class="space-y-4">
            <Card title="Foto KTP" collapsible={false}>
                {#snippet children()}
                    {#if employee.photo_url}
                        <img
                            class="rounded-lg border border-gray-200 dark:border-gray-700"
                            src={employee.photo_url}
                            alt="Foto KTP"
                        />
                        <a
                            class="text-xs text-blue-600 underline dark:text-blue-400"
                            href={employee.photo_url}
                            target="_blank">Buka di tab baru</a
                        >
                    {:else}
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Tidak ada foto KTP.
                        </p>
                    {/if}
                {/snippet}
            </Card>
        </div>
    </div>
</section>
