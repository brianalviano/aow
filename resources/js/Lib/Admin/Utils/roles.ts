/**
 * Utility helper untuk evaluasi role pengguna di aplikasi Admin.
 * Menyediakan fungsi untuk mendeteksi role tertinggi (Super Admin, Director).
 *
 * @remarks
 * Gunakan fungsi ini di halaman manapun yang membutuhkan pengecekan otorisasi berbasis role.
 */
export type AppRole = string;

export type RoleConfig = {
    highest: string[];
};

let roleConfig: RoleConfig = {
    highest: ["Super Admin", "Director"],
};

export function setRoleConfig(config: RoleConfig): void {
    roleConfig = {
        highest: (config?.highest ?? []).map((r) => normalizeRole(r)),
    };
}

/**
 * Normalisasi nama role menjadi string yang bersih untuk perbandingan.
 *
 * @param role Nama role mentah dari props/auth (bisa null/undefined)
 * @return Role yang telah di-trim; string kosong jika tidak ada
 */
export function normalizeRole(role: string | null | undefined): string {
    const r = String(role ?? "").trim();
    return r;
}

/**
 * Periksa apakah role termasuk role tertinggi yang berhak melakukan approval kritikal.
 * Role tertinggi: Super Admin, Director.
 *
 * @param role Nama role pengguna saat ini
 * @return true jika role adalah Super Admin atau Director; selain itu false
 */
export function isHighestRole(role: string | null | undefined): boolean {
    const r = normalizeRole(role);
    return roleConfig.highest.includes(r);
}

/**
 * Alias untuk kebijakan approval HO pada Purchase Order.
 *
 * @param role Nama role pengguna saat ini
 * @return true jika role berhak approve HO
 */
export function canApproveHo(role: string | null | undefined): boolean {
    return isHighestRole(role);
}
