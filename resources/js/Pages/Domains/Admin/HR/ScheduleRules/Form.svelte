<script lang="ts">
    import { page, router } from "@inertiajs/svelte";
    import Card from "@/Lib/Admin/Components/Ui/Card.svelte";
    import Button from "@/Lib/Admin/Components/Ui/Button.svelte";
    import Select from "@/Lib/Admin/Components/Ui/Select.svelte";
    import DateInput from "@/Lib/Admin/Components/Ui/DateInput.svelte";
    import Checkbox from "@/Lib/Admin/Components/Ui/Checkbox.svelte";
    import { siteName } from "@/Lib/Admin/Utils/settings";

    type Option = { value: string; label: string };
    type Shift = {
        id: string;
        name: string;
        start_time: string | null;
        end_time: string | null;
        is_off: boolean;
    };
    type User = { id: string; name: string };
    type Rule = {
        id: string;
        user_id: string;
        start_date: string | null;
        end_date: string | null;
        is_active: boolean;
        details: Array<{ day_of_week: number; shift_id: string | null }>;
        rotation_even_shift_id?: string | null;
        rotation_odd_shift_id?: string | null;
        rotation_off_day?: number | null;
    } | null;

    let rule = $derived(($page.props.rule as Rule) ?? null);
    let users = $derived(($page.props.users as User[]) ?? []);
    let shifts = $derived(($page.props.shifts as Shift[]) ?? []);
    let usedUserIds = $derived(($page.props.used_user_ids as string[]) ?? []);
    let offShiftId = $derived(shifts.find((s) => s.is_off)?.id ?? null);

    const dayNames = [
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu",
        "Minggu",
    ];
    let isEdit = $derived(Boolean(rule && rule.id));

    type FormState = {
        user_id: string;
        start_date: string;
        end_date: string;
        is_active: boolean;
        details: Array<{ day_of_week: number; shift_id: string | null }>;
        rotation_even_shift_id: string;
        rotation_odd_shift_id: string;
        rotation_off_day: number | null;
    };

    let form = $state<FormState>({
        user_id: "",
        start_date: new Date().toISOString().slice(0, 10),
        end_date: "",
        is_active: true,
        details: [0, 1, 2, 3, 4, 5, 6].map((d) => ({
            day_of_week: d,
            shift_id: null,
        })),
        rotation_even_shift_id: "",
        rotation_odd_shift_id: "",
        rotation_off_day: null,
    });
    $effect(() => {
        const r = rule;
        if (r) {
            form.user_id = r.user_id;
            form.start_date =
                r.start_date ?? new Date().toISOString().slice(0, 10);
            form.end_date = r.end_date ?? "";
            form.is_active = r.is_active;
            form.details = [0, 1, 2, 3, 4, 5, 6].map((d) => {
                const found = r.details.find((x) => x.day_of_week === d);
                return {
                    day_of_week: d,
                    shift_id: found?.shift_id ?? null,
                };
            });
            form.rotation_even_shift_id = r.rotation_even_shift_id ?? "";
            form.rotation_odd_shift_id = r.rotation_odd_shift_id ?? "";
            form.rotation_off_day =
                typeof r.rotation_off_day === "number"
                    ? r.rotation_off_day
                    : null;
        }
    });

    let userOptions = $derived<Option[]>(
        users.map((u) => ({ value: u.id, label: u.name })),
    );
    let shiftOptions = $derived<Option[]>([
        ...shifts.map((s) => {
            const time =
                s.start_time && s.end_time
                    ? ` (${s.start_time} - ${s.end_time})`
                    : "";
            return { value: s.id, label: `${s.name}${time}` };
        }),
        ...(offShiftId ? [] : [{ value: "", label: "Kosong" }]),
    ]);

    function submit() {
        const payload = {
            user_id: form.user_id,
            start_date: form.start_date,
            end_date: form.end_date || null,
            is_active: form.is_active,
            rotation_even_shift_id: form.rotation_even_shift_id || null,
            rotation_odd_shift_id: form.rotation_odd_shift_id || null,
            rotation_off_day:
                form.rotation_off_day === null
                    ? null
                    : Number(form.rotation_off_day),
            details: form.details.map((d) => ({
                day_of_week: d.day_of_week,
                shift_id: d.shift_id,
            })),
        };
        if (isEdit && rule?.id) {
            router.put(`/schedule-rules/${rule.id}`, payload, {
                preserveScroll: true,
                onSuccess: () => {
                    router.get("/schedule-rules", {}, { preserveScroll: true });
                },
            });
        } else {
            router.post("/schedule-rules", payload, {
                preserveScroll: true,
            });
        }
    }
</script>

<svelte:head>
    <title
        >{isEdit ? "Edit" : "Buat"} Aturan Jadwal | {siteName(
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
                {isEdit ? "Edit" : "Buat"} Aturan Jadwal
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                {isEdit ? "Ubah" : "Definisikan"} pola mingguan
            </p>
        </div>
    </header>

    <Card title="Subjek Aturan">
        {#snippet children()}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <Select
                    id="user_id"
                    name="user_id"
                    label="Karyawan"
                    options={[
                        { value: "", label: "Pilih Karyawan" },
                        ...userOptions.map((opt) => ({
                            ...opt,
                            label: usedUserIds.includes(opt.value)
                                ? `${opt.label} (sudah punya aturan)`
                                : opt.label,
                        })),
                    ]}
                    bind:value={form.user_id}
                />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                <DateInput
                    id="start_date"
                    name="start_date"
                    label="Tanggal Mulai"
                    bind:value={form.start_date}
                />
                <DateInput
                    id="end_date"
                    name="end_date"
                    label="Tanggal Selesai"
                    bind:value={form.end_date}
                />
            </div>
            <div class="mt-3">
                <Checkbox
                    id="is_active"
                    label="Aktif"
                    checked={form.is_active}
                    onclick={() => (form.is_active = !form.is_active)}
                />
            </div>
        {/snippet}
    </Card>

    <Card title="Pola Rotasi">
        {#snippet children()}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <Select
                    id="rotation_even_shift_id"
                    name="rotation_even_shift_id"
                    label="Shift Minggu Genap"
                    options={shiftOptions}
                    value={form.rotation_even_shift_id}
                    onchange={(v: string | number) => {
                        form.rotation_even_shift_id =
                            v === null || v === undefined ? "" : String(v);
                    }}
                />
                <Select
                    id="rotation_odd_shift_id"
                    name="rotation_odd_shift_id"
                    label="Shift Minggu Ganjil"
                    options={shiftOptions}
                    value={form.rotation_odd_shift_id}
                    onchange={(v: string | number) => {
                        form.rotation_odd_shift_id =
                            v === null || v === undefined ? "" : String(v);
                    }}
                />
                <Select
                    id="rotation_off_day"
                    name="rotation_off_day"
                    label="Hari Libur Rotasi"
                    options={[
                        { value: "", label: "Tidak ada" },
                        ...dayNames.map((dn, i) => ({
                            value: String(i),
                            label: dn,
                        })),
                    ]}
                    value={form.rotation_off_day === null
                        ? ""
                        : String(form.rotation_off_day)}
                    onchange={(v: string | number) => {
                        const val =
                            v === null || v === undefined ? "" : String(v);
                        form.rotation_off_day = val ? Number(val) : null;
                    }}
                />
            </div>
        {/snippet}
    </Card>

    <Card title="Pola Mingguan">
        {#snippet children()}
            <div class="grid sm:grid-cols-1 md:grid-cols-3 gap-3">
                {#each [0, 1, 2, 3, 4, 5, 6] as d}
                    <Select
                        id={"shift_" + d}
                        name={"shift_" + d}
                        label="Shift {dayNames[d]}"
                        options={shiftOptions}
                        value={form.details.find((x) => x.day_of_week === d)
                            ?.shift_id ?? ""}
                        onchange={(v: string | number) => {
                            const val =
                                v === null || v === undefined ? "" : String(v);
                            const idx = form.details.findIndex(
                                (x) => x.day_of_week === d,
                            );
                            if (idx >= 0 && form.details[idx]) {
                                form.details[idx].shift_id = val ? val : null;
                                if (
                                    offShiftId &&
                                    val &&
                                    String(val) === offShiftId &&
                                    form.rotation_even_shift_id &&
                                    form.rotation_odd_shift_id
                                ) {
                                    form.rotation_off_day = d;
                                } else if (
                                    form.rotation_even_shift_id &&
                                    form.rotation_odd_shift_id &&
                                    form.rotation_off_day === d &&
                                    (!offShiftId ||
                                        (val && String(val) !== offShiftId) ||
                                        !val)
                                ) {
                                    form.rotation_off_day = null;
                                }
                            }
                        }}
                    />
                {/each}
            </div>
        {/snippet}
    </Card>

    <div class="flex gap-3">
        <Button
            variant="secondary"
            icon="fa-solid fa-arrow-left"
            onclick={() => router.get("/schedule-rules")}>Kembali</Button
        >
        <Button variant="primary" icon="fa-solid fa-save" onclick={submit}
            >{isEdit ? "Simpan Perubahan" : "Simpan"}</Button
        >
    </div>
</section>
