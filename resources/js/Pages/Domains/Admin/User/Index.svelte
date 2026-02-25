<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import Badge from "@/Lib/Admin/Components/Ui/Badge.svelte";
    import Pagination from "@/Lib/Admin/Components/Ui/Pagination.svelte";
    import Dialog from "@/Lib/Admin/Components/Ui/Dialog.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";
    import debounce from "lodash-es/debounce";

    interface Role {
        id: string;
        name: string;
    }

    interface User {
        id: string;
        name: string;
        email: string;
        username: string;
        phone: string | null;
        role: Role;
        created_at: string;
    }

    let users = $derived(
        $page.props.users as {
            data: User[];
            meta?: any;
        },
    );

    let roles = $derived(($page.props.roles as { data: Role[] })?.data ?? []);

    let roleOptions = $derived([
        { value: "", label: "Semua Role" },
        ...roles.map((r) => ({ value: r.id, label: r.name })),
    ]);

    let filters = $derived(
        $page.props.filters as
            | { search?: string; role_id?: string }
            | undefined,
    );

    let searchQuery = $state(untrack(() => filters?.search || ""));
    let roleFilter = $state(untrack(() => filters?.role_id || ""));

    let meta = $derived(
        users?.meta ?? {
            total: 0,
            per_page: 15,
            current_page: 1,
            last_page: 1,
        },
    );

    let items = $derived(users?.data ?? []);

    let deleteModalOpen = $state(false);
    let userToDelete = $state<User | null>(null);

    function confirmDelete(user: User) {
        userToDelete = user;
        deleteModalOpen = true;
    }

    function executeDelete() {
        if (!userToDelete) return;

        router.delete(`/admin/users/${userToDelete.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteModalOpen = false;
                userToDelete = null;
            },
        });
    }

    function goToPage(pageNumber: number) {
        const params = new URLSearchParams();
        const limit = meta.per_page || 15;
        params.set("page", String(pageNumber));
        params.set("limit", String(limit));

        if (searchQuery) {
            params.set("search", searchQuery);
        }
        if (roleFilter) {
            params.set("role_id", roleFilter);
        }

        router.get(
            "/admin/users?" + params.toString(),
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    const handleSearch = debounce(() => {
        goToPage(1);
    }, 500);

    // Watch for search query or role filter changes to trigger debounced search
    $effect(() => {
        if (
            searchQuery !== (filters?.search || "") ||
            roleFilter !== (filters?.role_id || "")
        ) {
            handleSearch();
        }
    });
</script>

<svelte:head>
    <title>User Admin | {getSettingName($page.props.settings)}</title>
</svelte:head>

<section class="space-y-6">
    <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                User Admin
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Kelola daftar pengguna administratif
            </p>
        </div>
        <div class="flex flex-wrap gap-2 sm:justify-end">
            <Button
                variant="primary"
                icon="fa-solid fa-plus"
                href="/admin/users/create"
            >
                Tambah User
            </Button>
        </div>
    </header>

    <Card title="Daftar User" bodyWithoutPadding={true}>
        {#snippet actions()}
            <div
                class="flex flex-col sm:flex-row items-center w-full max-w-2xl gap-2"
            >
                <div class="w-full sm:w-48">
                    <Select
                        id="role_filter"
                        options={roleOptions}
                        bind:value={roleFilter}
                    />
                </div>
                <div class="flex-1 w-full">
                    <TextInput
                        id="search"
                        name="search"
                        placeholder="Cari nama, email, atau username..."
                        bind:value={searchQuery}
                        class="mb-0!"
                    />
                </div>
                <div class="flex gap-2">
                    {#if searchQuery || roleFilter}
                        <Button
                            variant="secondary"
                            size="sm"
                            onclick={() => {
                                searchQuery = "";
                                roleFilter = "";
                                handleSearch();
                            }}
                        >
                            Reset
                        </Button>
                    {/if}
                </div>
            </div>
        {/snippet}
        {#snippet children()}
            <div class="overflow-x-auto">
                <table class="custom-table min-w-full">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>No. Telp</th>
                            <th class="w-32 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#if items.length > 0}
                            {#each items as item}
                                <tr>
                                    <td>
                                        <div
                                            class="text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            {item.name}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {item.username}
                                        </div>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {item.email}
                                        </div>
                                    </td>
                                    <td>
                                        <Badge
                                            size="sm"
                                            rounded="pill"
                                            variant="primary"
                                        >
                                            {#snippet children()}{item.role
                                                    ?.name ?? "-"}{/snippet}
                                        </Badge>
                                    </td>
                                    <td>
                                        <div
                                            class="text-sm text-gray-900 dark:text-white"
                                        >
                                            {item.phone ?? "-"}
                                        </div>
                                    </td>

                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-center"
                                    >
                                        <div
                                            class="flex gap-2 items-center justify-center"
                                        >
                                            <Button
                                                variant="warning"
                                                size="sm"
                                                icon="fa-solid fa-edit"
                                                href={`/admin/users/${item.id}/edit`}
                                            >
                                                Edit
                                            </Button>
                                            {#if item.id !== $page.props.auth.user.id}
                                                <Button
                                                    variant="danger"
                                                    size="sm"
                                                    icon="fa-solid fa-trash"
                                                    onclick={() =>
                                                        confirmDelete(item)}
                                                >
                                                    Hapus
                                                </Button>
                                            {/if}
                                        </div>
                                    </td>
                                </tr>
                            {/each}
                        {:else}
                            <tr>
                                <td
                                    colspan="6"
                                    class="py-6 text-sm text-center text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {/snippet}
        {#snippet footer()}
            <Pagination
                currentPage={meta.current_page}
                totalPages={meta.last_page}
                totalItems={meta.total}
                itemsPerPage={meta.per_page}
                onPageChange={goToPage}
                showItemsPerPage={false}
            />
        {/snippet}
    </Card>
</section>

<!-- Delete Confirmation Dialog -->
<Dialog
    bind:isOpen={deleteModalOpen}
    title="Konfirmasi Hapus"
    type="danger"
    message={`Apakah Anda yakin ingin menghapus user ${userToDelete?.name}? Tindakan ini tidak dapat dibatalkan.`}
    confirmText="Hapus"
    cancelText="Batal"
    onConfirm={executeDelete}
    onCancel={() => (deleteModalOpen = false)}
/>
