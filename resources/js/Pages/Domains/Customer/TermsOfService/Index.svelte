<script lang="ts">
    import { page, Link } from "@inertiajs/svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
</script>

<svelte:head>
    <title>Kebijakan Privasi | {name($page.props.settings)}</title>
</svelte:head>

<div>
    <!-- Header -->
    <header
        class="flex items-center justify-between p-4 bg-white sticky top-0 z-10 border-b border-gray-100"
    >
        <Link
            href="/menu"
            class="text-gray-800 focus:outline-none p-1"
            aria-label="Kembali"
        >
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </Link>
        <h1 class="font-bold text-lg leading-tight text-gray-900">
            Kebijakan Privasi
        </h1>
    </header>

    <!-- Main Content -->
    <main class="w-full bg-white text-gray-800 font-sans">
        <div class="max-w-3xl mx-auto p-6">
            <h1 class="text-3xl font-bold mb-2">Kebijakan Privasi</h1>
            <p class="text-sm text-gray-500 mb-8">
                Terakhir diperbarui: 23 Februari 2026
            </p>

            <p class="mb-6 leading-relaxed">
                Kebijakan Privasi ini menjelaskan bagaimana
                <strong>AOW Mentai</strong> ("kami") mengumpulkan, menggunakan,
                menyimpan, dan melindungi informasi pribadi Anda saat
                menggunakan layanan pemesanan makanan kami melalui website
                <strong>aow-mentai.com</strong>.
            </p>

            <p class="mb-10 leading-relaxed">
                Dengan menggunakan layanan kami, Anda menyetujui pengumpulan dan
                penggunaan informasi sesuai dengan kebijakan ini.
            </p>

            <!-- 1 -->
            <h2 class="text-xl font-semibold mb-3">
                1. Informasi yang Kami Kumpulkan
            </h2>
            <p class="mb-3 leading-relaxed">
                Kami mengumpulkan informasi berikut saat Anda mendaftar dan
                menggunakan layanan kami:
            </p>
            <ul class="list-disc pl-6 mb-4 space-y-1 text-gray-700">
                <li>Nama lengkap</li>
                <li>Nomor telepon</li>
                <li>Alamat email</li>
                <li>
                    Alamat pengiriman (kelas, gedung, ruangan, atau detail
                    lokasi lainnya)
                </li>
                <li>
                    Drop point yang dipilih (nama sekolah, kampus, atau kantor)
                </li>
                <li>Riwayat pesanan</li>
                <li>
                    Informasi pembayaran (hanya status transaksi, kami tidak
                    menyimpan data kartu atau rekening Anda)
                </li>
            </ul>
            <p class="mb-10 leading-relaxed">
                Kami juga dapat mengumpulkan data teknis seperti alamat IP,
                jenis perangkat, dan browser yang digunakan secara otomatis saat
                Anda mengakses website kami.
            </p>

            <!-- 2 -->
            <h2 class="text-xl font-semibold mb-3">2. Penggunaan Informasi</h2>
            <p class="mb-3 leading-relaxed">
                Informasi yang kami kumpulkan digunakan untuk:
            </p>
            <ul class="list-disc pl-6 mb-10 space-y-1 text-gray-700">
                <li>Memproses dan mengkonfirmasi pesanan Anda</li>
                <li>Mengirimkan notifikasi status pesanan melalui WhatsApp</li>
                <li>Mengantarkan pesanan ke drop point yang Anda pilih</li>
                <li>Meningkatkan layanan dan pengalaman pengguna</li>
                <li>Menghubungi Anda jika ada kendala terkait pesanan</li>
                <li>Keperluan administrasi dan pelaporan internal</li>
            </ul>

            <!-- 3 -->
            <h2 class="text-xl font-semibold mb-3">
                3. Berbagi Informasi dengan Pihak Ketiga
            </h2>
            <p class="mb-3 leading-relaxed">
                Kami tidak menjual atau menyewakan informasi pribadi Anda kepada
                pihak manapun. Informasi Anda hanya dibagikan kepada pihak
                ketiga berikut dalam batas yang diperlukan:
            </p>
            <ul class="list-disc pl-6 mb-10 space-y-1 text-gray-700">
                <li>
                    <strong>Midtrans</strong> — sebagai penyedia layanan pembayaran
                    untuk memproses transaksi Anda
                </li>
                <li>
                    <strong>Fonnte</strong> — sebagai penyedia layanan notifikasi
                    WhatsApp
                </li>
                <li>
                    <strong>Pihak berwenang</strong> — jika diwajibkan oleh hukum
                    yang berlaku
                </li>
            </ul>

            <!-- 4 -->
            <h2 class="text-xl font-semibold mb-3">4. Keamanan Data</h2>
            <p class="mb-10 leading-relaxed">
                Kami menerapkan langkah-langkah keamanan yang wajar untuk
                melindungi informasi pribadi Anda dari akses, pengungkapan, atau
                pengubahan yang tidak sah. Seluruh komunikasi antara browser
                Anda dan website kami dilindungi dengan enkripsi SSL. Namun,
                tidak ada metode transmisi data melalui internet yang sepenuhnya
                aman, sehingga kami tidak dapat menjamin keamanan mutlak.
            </p>

            <!-- 5 -->
            <h2 class="text-xl font-semibold mb-3">5. Penyimpanan Data</h2>
            <p class="mb-10 leading-relaxed">
                Data pribadi Anda disimpan selama akun Anda aktif atau selama
                diperlukan untuk keperluan layanan. Jika Anda menghapus akun,
                kami akan menghapus data pribadi Anda dalam waktu 30 hari kerja,
                kecuali data yang wajib disimpan berdasarkan ketentuan hukum
                yang berlaku.
            </p>

            <!-- 6 -->
            <h2 class="text-xl font-semibold mb-3">6. Hak Anda</h2>
            <p class="mb-3 leading-relaxed">Anda memiliki hak untuk:</p>
            <ul class="list-disc pl-6 mb-10 space-y-1 text-gray-700">
                <li>
                    Mengakses dan memperbarui informasi pribadi Anda melalui
                    halaman profil
                </li>
                <li>Meminta penghapusan data pribadi Anda</li>
                <li>Menolak penggunaan data untuk keperluan tertentu</li>
                <li>
                    Mengajukan pertanyaan atau keluhan terkait data pribadi Anda
                </li>
            </ul>

            <!-- 7 -->
            <h2 class="text-xl font-semibold mb-3">7. Cookie</h2>
            <p class="mb-10 leading-relaxed">
                Website kami menggunakan cookie untuk menjaga sesi login Anda
                dan meningkatkan pengalaman penggunaan. Anda dapat menonaktifkan
                cookie melalui pengaturan browser, namun beberapa fitur website
                mungkin tidak berfungsi dengan baik.
            </p>

            <!-- 8 -->
            <h2 class="text-xl font-semibold mb-3">8. Perubahan Kebijakan</h2>
            <p class="mb-10 leading-relaxed">
                Kami berhak memperbarui Kebijakan Privasi ini sewaktu-waktu.
                Perubahan akan diberitahukan melalui website atau notifikasi
                WhatsApp. Penggunaan layanan kami setelah perubahan berlaku
                dianggap sebagai persetujuan Anda terhadap kebijakan yang
                diperbarui.
            </p>

            <!-- 9 -->
            <h2 class="text-xl font-semibold mb-3">9. Hubungi Kami</h2>
            <p class="mb-2 leading-relaxed">
                Jika Anda memiliki pertanyaan mengenai Kebijakan Privasi ini,
                silakan hubungi kami melalui:
            </p>
            <ul class="list-disc pl-6 mb-10 space-y-1 text-gray-700">
                <li>WhatsApp: <strong>[Nomor WhatsApp AOW Mentai]</strong></li>
                <li>Email: <strong>[Email AOW Mentai]</strong></li>
                <li>Instagram: <strong>[Instagram AOW Mentai]</strong></li>
            </ul>

            <hr class="border-gray-200 mt-10" />
            <p class="text-sm text-gray-400 mt-6 text-center">
                © 2026 AOW Mentai. Seluruh hak dilindungi.
            </p>
        </div>
    </main>
</div>
