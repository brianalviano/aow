<script lang="ts">
    import { page, router, useForm } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import TextInput from "@/Lib/Admin/Components/Ui/TextInput.svelte";
    import { name as getSettingName } from "@/Lib/Admin/Utils/settings";
    import { untrack } from "svelte";

    interface Section {
        title: string;
        items: string[];
    }

    interface PaymentGuide {
        id: string;
        name: string;
        content: Section[];
    }

    const { paymentGuide = null } = $page.props as any;
    const isEdit = !!paymentGuide;

    const form = useForm(
        untrack(() => ({
            name: paymentGuide?.name || "",
            content:
                paymentGuide?.content ||
                ([
                    {
                        title: "Instruksi Umum",
                        items: [""],
                    },
                ] as Section[]),
        })),
    );

    function addSection() {
        $form.content = [...$form.content, { title: "", items: [""] }];
    }

    function removeSection(index: number) {
        $form.content = $form.content.filter(
            (_: Section, i: number) => i !== index,
        );
    }

    function addItem(sectionIndex: number) {
        $form.content[sectionIndex].items = [
            ...$form.content[sectionIndex].items,
            "",
        ];
    }

    function removeItem(sectionIndex: number, itemIndex: number) {
        $form.content[sectionIndex].items = $form.content[
            sectionIndex
        ].items.filter((_: string, i: number) => i !== itemIndex);
        if ($form.content[sectionIndex].items.length === 0) {
            $form.content[sectionIndex].items = [""];
        }
    }

    function backToIndex() {
        router.visit("/admin/payment-guides");
    }

    function submit() {
        if (isEdit) {
            $form.put(`/admin/payment-guides/${paymentGuide.id}`);
        } else {
            $form.post("/admin/payment-guides");
        }
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Tambah"} Panduan Pembayaran | {getSettingName(
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
                {isEdit ? "Edit " : "Tambah "} Panduan Pembayaran
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Lengkapi form di bawah ini untuk mengelola panduan instruksi
                pembayaran
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
                form="payment-guide-form"
            >
                Simpan
            </Button>
        </div>
    </header>

    <form
        id="payment-guide-form"
        onsubmit={(e) => {
            e.preventDefault();
            submit();
        }}
        class="space-y-6"
    >
        <Card title="Informasi Dasar">
            <div class="space-y-4">
                <TextInput
                    id="name"
                    name="name"
                    label="Nama Panduan"
                    placeholder="Contoh: Panduan Transfer Bank BCA"
                    bind:value={$form.name}
                    error={$form.errors.name}
                    required
                />
            </div>
        </Card>

        <Card title="Konten Panduan">
            {#snippet actions()}
                <Button
                    variant="info"
                    size="sm"
                    icon="fa-solid fa-plus"
                    onclick={addSection}
                >
                    Tambah Seksi
                </Button>
            {/snippet}

            <div class="space-y-8">
                {#each $form.content as section, sIndex}
                    <div class="relative group">
                        <div class="space-y-4">
                            <span
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {`Judul Seksi ${sIndex + 1}`}
                            </span>
                            <div class="flex gap-2 items-start">
                                <div class="flex-1">
                                    <TextInput
                                        id={`section-title-${sIndex}`}
                                        name={`section-title-${sIndex}`}
                                        placeholder="Contoh: Transfer via ATM"
                                        bind:value={section.title}
                                        required
                                        class="mb-0!"
                                    />
                                </div>
                                <Button
                                    variant="danger"
                                    size="sm"
                                    icon="fa-solid fa-minus"
                                    onclick={() => removeSection(sIndex)}
                                    class="mt-0.5"
                                />
                            </div>

                            <div class="space-y-3 pl-4">
                                <span
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Langkah-langkah
                                </span>
                                {#each section.items as item, iIndex}
                                    <div class="flex gap-2 items-start">
                                        <div class="flex-1">
                                            <TextInput
                                                id={`item-${sIndex}-${iIndex}`}
                                                name={`item-${sIndex}-${iIndex}`}
                                                placeholder={`Langkah ${iIndex + 1}`}
                                                bind:value={
                                                    section.items[iIndex]
                                                }
                                                class="mb-0!"
                                                required
                                            />
                                        </div>
                                        <Button
                                            variant="danger"
                                            size="sm"
                                            icon="fa-solid fa-minus"
                                            onclick={() =>
                                                removeItem(sIndex, iIndex)}
                                            disabled={section.items.length ===
                                                1}
                                            class="mt-0.5"
                                        />
                                    </div>
                                {/each}
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    icon="fa-solid fa-plus"
                                    onclick={() => addItem(sIndex)}
                                    class="mt-1"
                                >
                                    Tambah Langkah
                                </Button>
                            </div>
                        </div>
                    </div>
                {/each}

                {#if $form.content.length === 0}
                    <div
                        class="text-center py-6 border-2 border-dashed rounded-lg dark:border-gray-700"
                    >
                        <p class="text-gray-500 dark:text-gray-400">
                            Belum ada konten. Tambahkan seksi pertama Anda.
                        </p>
                        <Button
                            variant="info"
                            size="sm"
                            icon="fa-solid fa-plus"
                            onclick={addSection}
                            class="mt-4"
                        >
                            Tambah Seksi
                        </Button>
                    </div>
                {/if}
            </div>

            {#if $form.errors.content}
                <p class="mt-2 text-sm text-red-500">{$form.errors.content}</p>
            {/if}
        </Card>
    </form>
</section>
