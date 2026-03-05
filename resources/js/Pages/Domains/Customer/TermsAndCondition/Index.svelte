<script lang="ts">
    import { page, Link } from "@inertiajs/svelte";
    import { name } from "@/Lib/Admin/Utils/settings";
    export let appUrl: string;
    export let orderMinDaysAhead: number = 1;
    export let paymentMethodsStr: string = "";
    export let dropPointCategoriesStr: string = "";

    const displayUrl = appUrl.replace(/^https?:\/\//, "");
    $: appName = name($page.props.settings);
</script>

<svelte:head>
    <title>Syarat & Ketentuan | {name($page.props.settings)}</title>
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
            Syarat & Ketentuan
        </h1>
    </header>

    <!-- Main Content -->
    <main class="w-full bg-white text-gray-800 font-sans">
        <div class="max-w-3xl mx-auto p-6">
            <h1 class="text-3xl font-bold mb-2">Syarat & Ketentuan</h1>
            <p class="text-sm text-gray-500 mb-8">
                Terakhir diperbarui: 05 Maret 2026
            </p>

            <p class="mb-10 leading-relaxed">
                Syarat dan Ketentuan ini mengatur penggunaan layanan pemesanan
                makanan <strong>{appName}</strong> melalui website
                <strong>{displayUrl}</strong>. Dengan mendaftar dan menggunakan
                layanan kami, Anda dianggap telah membaca, memahami, dan
                menyetujui seluruh ketentuan di bawah ini.
            </p>

            <!-- 1 -->
            <h2 class="text-xl font-semibold mb-3">1. Definisi</h2>
            <ul class="list-disc pl-6 mb-10 space-y-2 text-gray-700">
                <li>
                    <strong>{appName} / Kami</strong> — penyedia layanan
                    pemesanan makanan melalui website {displayUrl}
                </li>
                <li>
                    <strong>Pengguna / Anda</strong> — individu yang mendaftar dan
                    menggunakan layanan kami
                </li>
                <li>
                    <strong>Drop Point</strong> — lokasi pengiriman yang telah
                    ditentukan ({dropPointCategoriesStr})
                </li>
                <li>
                    <strong>Pre-Order (PO)</strong> — sistem pemesanan makanan
                    yang dilakukan minimal {orderMinDaysAhead} hari sebelum tanggal
                    pengiriman (H-{orderMinDaysAhead})
                </li>
            </ul>

            <!-- 2 -->
            <h2 class="text-xl font-semibold mb-3">2. Pendaftaran Akun</h2>
            <ul class="list-disc pl-6 mb-10 space-y-2 text-gray-700">
                <li>
                    Pengguna wajib mendaftar menggunakan data yang benar,
                    lengkap, dan akurat
                </li>
                <li>
                    Satu nomor telepon dan satu email hanya dapat digunakan
                    untuk satu akun
                </li>
                <li>
                    Anda bertanggung jawab penuh atas kerahasiaan kata sandi dan
                    seluruh aktivitas yang terjadi pada akun Anda
                </li>
                <li>
                    Kami berhak menonaktifkan akun yang terbukti menggunakan
                    data palsu atau melanggar ketentuan ini
                </li>
            </ul>

            <!-- 3 -->
            <h2 class="text-xl font-semibold mb-3">3. Sistem Pre-Order</h2>
            <ul class="list-disc pl-6 mb-10 space-y-2 text-gray-700">
                <li>
                    Pemesanan dilakukan minimal {orderMinDaysAhead} hari sebelum
                    tanggal pengiriman (H-{orderMinDaysAhead})
                </li>
                <li>
                    Terdapat batas waktu yang telah ditentukan untuk pemesanan
                    setiap harinya
                </li>
                <li>
                    Pesanan yang masuk setelah batas waktu akan diproses untuk
                    keesokan harinya
                </li>
                <li>
                    Kami berhak membatasi jumlah stok produk tertentu per
                    harinya
                </li>
                <li>
                    Jika produk habis sebelum Anda menyelesaikan pembayaran,
                    pesanan untuk produk tersebut akan dibatalkan secara
                    otomatis
                </li>
            </ul>

            <!-- 4 -->
            <h2 class="text-xl font-semibold mb-3">4. Pembayaran</h2>
            <ul class="list-disc pl-6 mb-10 space-y-2 text-gray-700">
                <li>
                    Pembayaran dapat dilakukan melalui {paymentMethodsStr}
                </li>
                <li>
                    Pesanan dianggap valid setelah pembayaran berhasil
                    dikonfirmasi
                </li>
                <li>
                    Pembayaran online memiliki batas waktu. Pesanan yang tidak
                    dibayar hingga batas waktu akan dibatalkan secara otomatis
                </li>
                <li>
                    Kami tidak bertanggung jawab atas kegagalan transaksi yang
                    disebabkan oleh pihak penyedia layanan pembayaran
                </li>
            </ul>

            <!-- 5 -->
            <h2 class="text-xl font-semibold mb-3">5. Pengiriman</h2>
            <ul class="list-disc pl-6 mb-10 space-y-2 text-gray-700">
                <li>
                    Pengiriman pesanan pre-order (PO) harus memenuhi kuota
                    minimum order untuk mendapatkan gratis ongkos kirim
                </li>
                <li>
                    Pengiriman pesanan instan tanpa minimum order hanya
                    menggunakan kurir online berbayar sesuai tarif di aplikasi
                </li>
                <li>
                    Pengguna wajib memastikan drop point yang dipilih sesuai
                    dengan lokasi Anda
                </li>
                <li>
                    Kami tidak bertanggung jawab atas keterlambatan yang
                    disebabkan oleh kondisi di luar kendali kami (cuaca,
                    kemacetan, dll)
                </li>
                <li>
                    Pengguna wajib mengambil pesanan di drop point dengan segera
                    setelah kami kirimkan notifikasi pesanan datang
                </li>
                <li>
                    Pesanan yang tidak diambil dalam waktu yang ditentukan
                    menjadi tanggung jawab pengguna sepenuhnya
                </li>
            </ul>

            <!-- 6 -->
            <h2 class="text-xl font-semibold mb-3">
                6. Pembatalan & Pengembalian Dana
            </h2>
            <ul class="list-disc pl-6 mb-10 space-y-2 text-gray-700">
                <li>
                    Pembatalan pesanan hanya dapat dilakukan sebelum pesanan
                    dikonfirmasi oleh admin
                </li>
                <li>Pesanan yang sudah dikonfirmasi tidak dapat dibatalkan</li>
                <li>
                    Pengembalian dana hanya dilakukan jika pembatalan disebabkan
                    oleh kesalahan dari pihak kami
                </li>
                <li>
                    Proses pengembalian dana membutuhkan waktu 3–7 hari kerja
                    tergantung metode pembayaran yang digunakan
                </li>
            </ul>

            <!-- 7 -->
            <h2 class="text-xl font-semibold mb-3">7. Produk & Kualitas</h2>
            <ul class="list-disc pl-6 mb-10 space-y-2 text-gray-700">
                <li>
                    Kami berkomitmen untuk menyajikan makanan dengan kualitas
                    terbaik dan higienis
                </li>
                <li>
                    Foto produk yang ditampilkan di website bersifat ilustrasi,
                    tampilan aktual dapat sedikit berbeda
                </li>
                <li>
                    Jika terdapat keluhan mengenai kualitas produk, harap
                    hubungi kami maksimal 1 jam setelah pesanan diterima
                </li>
                <li>
                    Kami berhak mengubah menu, harga, dan ketersediaan produk
                    sewaktu-waktu tanpa pemberitahuan sebelumnya
                </li>
            </ul>

            <!-- 8 -->
            <h2 class="text-xl font-semibold mb-3">8. Diskon & Promosi</h2>
            <ul class="list-disc pl-6 mb-10 space-y-2 text-gray-700">
                <li>
                    Diskon dan kode voucher hanya berlaku sesuai syarat dan
                    periode yang telah ditentukan
                </li>
                <li>
                    Satu pesanan hanya dapat menggunakan maksimal dua kode
                    voucher (satu untuk produk, satu untuk ongkir)
                </li>
                <li>
                    Diskon tidak dapat digabungkan kecuali dinyatakan sebaliknya
                </li>
                <li>
                    Kami berhak membatalkan pesanan yang terindikasi
                    menyalahgunakan sistem diskon atau promosi
                </li>
            </ul>

            <!-- 9 -->
            <h2 class="text-xl font-semibold mb-3">9. Larangan Penggunaan</h2>
            <p class="mb-3 leading-relaxed">Pengguna dilarang:</p>
            <ul class="list-disc pl-6 mb-10 space-y-2 text-gray-700">
                <li>Menggunakan layanan untuk tujuan yang melanggar hukum</li>
                <li>
                    Mendaftarkan akun menggunakan identitas orang lain dengan
                    maksud memanipulasi data untuk tindakan kriminal
                </li>
                <li>
                    Melakukan pemesanan fiktif atau pesanan yang tidak berniat
                    untuk dibayar
                </li>
                <li>Menyalahgunakan sistem promosi atau diskon</li>
                <li>
                    Melakukan tindakan yang mengganggu operasional layanan kami
                </li>
            </ul>

            <!-- 10 -->
            <h2 class="text-xl font-semibold mb-3">10. Perubahan Layanan</h2>
            <p class="mb-10 leading-relaxed">
                Kami berhak mengubah, menangguhkan, atau menghentikan sebagian
                maupun seluruh layanan sewaktu-waktu. Kami akan berusaha
                memberikan pemberitahuan sebelumnya jika terjadi perubahan besar
                yang berdampak pada pengguna.
            </p>

            <!-- 11 -->
            <h2 class="text-xl font-semibold mb-3">
                11. Perubahan Syarat & Ketentuan
            </h2>
            <p class="mb-10 leading-relaxed">
                Kami berhak memperbarui Syarat & Ketentuan ini sewaktu-waktu.
                Perubahan akan diberitahukan melalui website atau notifikasi
                WhatsApp. Penggunaan layanan setelah perubahan berlaku dianggap
                sebagai persetujuan Anda terhadap ketentuan yang diperbarui.
            </p>

            <!-- 12 -->
            <h2 class="text-xl font-semibold mb-3">12. Hubungi Kami</h2>
            <p class="mb-2 leading-relaxed">
                Jika Anda memiliki pertanyaan mengenai Syarat & Ketentuan ini,
                silakan hubungi kami melalui:
            </p>
            <ul class="list-disc pl-6 mb-10 space-y-1 text-gray-700">
                <li>
                    WhatsApp: <strong
                        >{$page.props.settings?.whatsapp || "-"}</strong
                    >
                </li>
                <li>
                    Email: <strong>{$page.props.settings?.email || "-"}</strong>
                </li>
                <li>
                    Instagram: <strong
                        >{$page.props.settings?.instagram || "-"}</strong
                    >
                </li>
            </ul>

            <hr class="border-gray-200 mt-10" />
            <p class="text-sm text-gray-400 mt-6 text-center">
                © {new Date().getFullYear()}
                {name($page.props.settings)}. Seluruh hak dilindungi.
            </p>
        </div>
    </main>
</div>
