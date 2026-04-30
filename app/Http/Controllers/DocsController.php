<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DocsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Docs/Index', [
            'orderFlow' => $this->orderFlow(),
            'roles'     => $this->roles(),
            'userGuide' => $this->userGuide(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Order Flow
    // -------------------------------------------------------------------------

    private function orderFlow(): array
    {
        return [
            [
                'status' => 'PENDING',
                'actor'  => 'Customer',
                'color'  => 'blue',
                'desc'   => 'Pesanan dibuat. Customer mengisi alamat/drop point, memilih produk, serta menentukan tanggal & jam pengiriman. Akun dibuat otomatis dan info dikirim via Email.',
            ],
            [
                'status' => 'CONFIRMED',
                'actor'  => 'Admin',
                'color'  => 'yellow',
                'desc'   => 'Admin memverifikasi bukti bayar manual (BCA) dengan mutasi bank/deposit. Jika valid, pesanan dikirim ke Chef via Telegram/Email/App.',
            ],
            [
                'status' => 'ACCEPTED',
                'actor'  => 'Chef',
                'color'  => 'orange',
                'desc'   => 'Chef mnyetujui pesanan. Jika ditolak, Admin akan memindahkan (reassign) pesanan ke Chef lain.',
            ],
            [
                'status' => 'SHIPPED',
                'actor'  => 'Chef',
                'color'  => 'orange',
                'desc'   => 'Chef menyelesaikan masakan dan mengirimkannya ke Pickup Point. Admin & PIC mendapatkan notifikasi kedatangan.',
            ],
            [
                'status' => 'AT_PICKUP_POINT',
                'actor'  => 'PIC',
                'color'  => 'purple',
                'desc'   => 'PIC menerima kiriman Chef. Jika pesanan berasal dari beberapa Chef, PIC menunggu hingga lengkap sebelum mengirim ke Customer.',
            ],
            [
                'status' => 'ON_DELIVERY',
                'actor'  => 'PIC',
                'color'  => 'indigo',
                'desc'   => 'PIC mengirim pesanan ke Customer. Status berubah menjadi "Dikirim" dan Customer mendapat notifikasi WA/Telegram/Email.',
            ],
            [
                'status' => 'ARRIVED',
                'actor'  => 'PIC',
                'color'  => 'info',
                'desc'   => 'PIC sampai di lokasi, menyerahakan pesanan, dan upload bukti foto. Customer mendapat notifikasi bukti serah terima.',
            ],
            [
                'status' => 'DELIVERED',
                'actor'  => 'Customer / System',
                'color'  => 'green',
                'desc'   => 'Customer menyelesaikan pesanan atau sistem menutup otomatis setelah 6 jam. Saldo Chef bertambah dan testimoni dapat diberikan.',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Roles
    // -------------------------------------------------------------------------

    private function roles(): array
    {
        return [
            $this->customerRole(),
            $this->adminRole(),
            $this->chefRole(),
            $this->picRole(),
        ];
    }

    // -------------------------------------------------------------------------
    // Customer Role
    // -------------------------------------------------------------------------

    private function customerRole(): array
    {
        return [
            'id'       => 'customer',
            'name'     => 'Customer',
            'color'    => 'blue',
            'desc'     => 'Pengguna akhir yang melakukan pemesanan. Akun dibuat otomatis saat checkout pertama kali.',
            'sections' => [
                [
                    'id'    => 'cust-profile',
                    'title' => 'Akses & Akun',
                    'desc'  => 'Customer masuk ke halaman utama, memilih drop point atau alamat manual (titik lokasi), lalu memilih produk.',
                    'routes' => [
                        ['method' => 'GET',     'path' => '/',                       'desc' => 'Landing page & Product selection'],
                        ['method' => 'EMAIL',   'path' => 'Auto-Generated',          'desc' => 'Info akun dikirim otomatis ke email setelah checkout'],
                        ['method' => 'GET',    'path' => '/orders',                 'desc' => 'Menu Pesanan di Beranda untuk lacak status'],
                    ],
                ],
                [
                    'id'    => 'cust-checkout',
                    'title' => 'Checkout & Pembayaran',
                    'desc'  => 'Mengisi tanggal, jam pengiriman, catatan, dan data pribadi.',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/checkout',                'desc' => 'Halaman ringkasan belanja & input jadwal'],
                        ['method' => 'POST', 'path' => '/payment/{order}/proof',   'desc' => 'Bayar ke BCA & Upload bukti transfer'],
                    ],
                    'flow' => [
                        'Pilih Drop Point atau Input Alamat (+ Titik Lokasi)',
                        'Pilih Produk & Checkout (Isi Tanggal, Jam, Catatan)',
                        'Isi Data Pribadi di Halaman Pembayaran',
                        'Transfer ke BCA & Upload Bukti Bayar',
                    ],
                ],
                [
                    'id'    => 'cust-tracking',
                    'title' => 'Penerimaan & Testimoni',
                    'desc'  => 'Menerima notifikasi di setiap tahap (WA/Telegram/Email).',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/orders/{order}',          'desc' => 'Detail pesanan, bukti foto, dan status real-time'],
                        ['method' => 'POST', 'path' => '/orders/{order}/complete', 'desc' => 'Selesaikan pesanan secara manual'],
                    ],
                    'notes' => [
                        'Jika > 6 jam tidak diklik Selesai, sistem akan menyelesaikan otomatis.',
                        'Testimoni dapat diberikan kapan saja setelah pesanan selesai.',
                        'Semua pihak (Customer, Chef, PIC, Admin) mendapat notifikasi saat pesanan Selesai.',
                    ],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Chef Role
    // -------------------------------------------------------------------------

    private function chefRole(): array
    {
        return [
            'id'       => 'chef',
            'name'     => 'Chef',
            'color'    => 'orange',
            'desc'     => 'Mitra dapur yang menerima pesanan setelah Admin Approve pembayaran.',
            'sections' => [
                [
                    'id'    => 'chef-ops',
                    'title' => 'Pekerjaan Dapur',
                    'desc'  => 'Mendapat notifikasi via Telegram, Email, dan App untuk Approval.',
                    'routes' => [
                        ['method' => 'POST', 'path' => '/chef/orders/approve',  'desc' => 'Terima pesanan untuk mulai masak'],
                        ['method' => 'POST', 'path' => '/chef/orders/reject',   'desc' => 'Tolak pesanan (Alasan wajib, dikembalikan ke Admin)'],
                        ['method' => 'POST', 'path' => '/chef/orders/ship',     'desc' => 'Kirim masakan ke Pickup Point'],
                    ],
                    'flow' => [
                        'Notifikasi Approval (Telegram/Email)',
                        'Approve untuk eksekusi pesanan',
                        'Masak & Kemas sesuai pesanan',
                        'Klik Kirim ke Pickup Point (Admin mendapat notif)',
                    ],
                ],
                [
                    'id'    => 'chef-finance',
                    'title' => 'Status & Pendapatan',
                    'desc'  => 'Semua pihak terkait mendapat notifikasi saat pesanan selesai.',
                    'notes' => [
                        'Penolakan Chef akan memicu Admin untuk reassign ke Chef lain.',
                        'Saldo bertambah saat Customer/Sistem mengkonfirmasi Selesai.',
                    ],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // PIC Pickup Point Role
    // -------------------------------------------------------------------------

    private function picRole(): array
    {
        return [
            'id'       => 'pic',
            'name'     => 'PIC Pickup Point',
            'color'    => 'green',
            'desc'     => 'Petugas hub yang bertugas mengumpulkan masakan dari berbagai Chef dan mendistribusikannya.',
            'sections' => [
                [
                    'id'    => 'pic-ops',
                    'title' => 'Penerimaan & Pengiriman',
                    'desc'  => 'PIC mendapat notifikasi (Email/Telegram/App) saat Chef mengirim masakan.',
                    'routes' => [
                        ['method' => 'POST', 'path' => '/pic/orders/{order}/approve',  'desc' => 'Terima masakan dari Chef (Wajib lengkap jika >1 Chef)'],
                        ['method' => 'POST', 'path' => '/pic/orders/{order}/send',     'desc' => 'Kirim ke Customer (Notif WA/Telegram/Email dikirim)'],
                        ['method' => 'POST', 'path' => '/pic/orders/{order}/complete', 'desc' => 'Konfirmasi Tiba & Upload Foto Bukti'],
                    ],
                    'flow' => [
                        'Terima paket & Tunggu lengkap jika dari banyak Chef',
                        'Klik Kirim ke Customer (Status: Dikirim)',
                        'Serahkan pesanan ke lokasi & Upload bukti foto',
                        'Customer menerima notifikasi bukti serah terima',
                    ],
                    'notes' => [
                        'Notifikasi serah terima menyertakan bukti foto yang diupload PIC.',
                    ],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Admin Role
    // -------------------------------------------------------------------------

    private function adminRole(): array
    {
        return [
            'id'       => 'admin',
            'name'     => 'Admin',
            'color'    => 'purple',
            'desc'     => 'Pengelola platform yang memverifikasi transaksi dan koordinasi antar mitra.',
            'sections' => [
                [
                    'id'    => 'admin-orders',
                    'title' => 'Verifikasi Pembayaran',
                    'desc'  => 'Admin wajib mengecek mutasi bank/deposit sebelum melakukan Approve.',
                    'routes' => [
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/confirm',      'desc' => 'Approve Pembayaran & Notif Chef (Telegram/Email)'],
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/cancel',       'desc' => 'Reject Pembayaran (Wajib isi alasan penolakan untuk Customer)'],
                        ['method' => 'PATCH', 'path' => '/admin/order-items/{item}/reassign', 'desc' => 'Ganti Chef jika Chef sebelumnya me-reject'],
                    ],
                    'notes' => [
                        'Admin mendapatkan notifikasi setiap kali Chef mengirim masakan ke PIC.',
                    ],
                ],
                [
                    'id'    => 'admin-catalog',
                    'title' => 'Manajemen Katalog & Mitra',
                    'desc'  => 'Mengatur produk, kategori, data Chef, dan lokasi operasional.',
                    'routes' => [
                        ['method' => 'POST',   'path' => '/admin/products',              'desc' => 'Kelola menu & harga'],
                        ['method' => 'POST',   'path' => '/admin/chefs',                 'desc' => 'Onboarding Chef baru'],
                        ['method' => 'POST',   'path' => '/admin/drop-points',           'desc' => 'Kelola titik pengambilan pesanan'],
                        ['method' => 'POST',   'path' => '/admin/payment-methods',       'desc' => 'Kelola metode pembayaran transfer manual'],
                    ],
                ],
                [
                    'id'    => 'admin-settings',
                    'title' => 'Konfigurasi Sistem',
                    'desc'  => 'Pengaturan global yang mempengaruhi seluruh perilaku aplikasi.',
                    'routes' => [
                        ['method' => 'PUT', 'path' => '/admin/settings', 'desc' => 'Update cutoff time & window pemesanan'],
                        ['method' => 'GET', 'path' => '/admin/reports',  'desc' => 'Analitik penjualan & profitabilitas'],
                    ],
                ],
            ],
        ];
    }

    // User Guide (Simple Steps for Normal Users)
    // -------------------------------------------------------------------------

    private function userGuide(): array
    {
        return [
            [
                'title' => 'Akses Platform',
                'items' => [
                    'Buka halaman utama / aowenak.com melalui browser Anda.',
                    'Dapatkan inspirasi menu premium dari Chef mitra terbaik kami langsung di beranda.',
                ],
            ],
            [
                'title' => 'Menentukan Lokasi Pengiriman',
                'items' => [
                    'Pilih "Drop Point" jika Anda ingin mengambil pesanan di titik pengambilan resmi kami (Lebih Hemat).',
                    'Pilih "Alamat Lain" jika ingin pesanan dikirim langsung ke pintu rumah Anda.',
                    'Pastikan titik GPS lokasi Anda akurat untuk memudahkan kurir mencari alamat.',
                ],
            ],
            [
                'title' => 'Memilih Produk & Menu',
                'items' => [
                    'Jelajahi kategori makanan dan minuman yang tersedia.',
                    'Klik pada produk untuk melihat deskripsi detail, bahan-bahan, dan rating dari pembeli lain.',
                    'Kumpulkan menu favorit Anda ke dalam keranjang belanja.',
                ],
            ],
            [
                'title' => 'Atur Jadwal & Catatan khusus',
                'items' => [
                    'Klik icon keranjang dan pilih "Checkout".',
                    'Wajib mengisi Tanggal dan Jam pengiriman karena sistem kami berbasis pre-order.',
                    'Tambahkan "Catatan" jika ada permintaan khusus (misal: "Tanpa pedas", "Sertakan sendok", dll).',
                ],
            ],
            [
                'title' => 'Review Pesanan & Checkout',
                'items' => [
                    'Periksa kembali rincian pesanan, alamat, dan total biaya Anda.',
                    'Pastikan tidak ada yang terlewat sebelum melanjutkan ke tahap pembayaran.',
                ],
            ],
            [
                'title' => 'Registrasi Akun Otomatis',
                'items' => [
                    'Di halaman pembayaran, masukkan data diri (Nama, WA, Email) dengan benar.',
                    'Sistem akan otomatis membuatkan akun untuk Anda.',
                    'Informasi login (Username & Password) akan dikirimkan secara instan ke alamat Email yang Anda daftarkan.',
                ],
            ],
            [
                'title' => 'Lakukan Pembayaran',
                'items' => [
                    'Transfer nominal transaksi ke rekening Bank BCA yang tertera di layar.',
                    'Harap membayar sesuai nominal hingga angka terakhir (jika ada kode unik) untuk mempercepat proses verifikasi.',
                ],
            ],
            [
                'title' => 'Konfirmasi Pembayaran',
                'items' => [
                    'Masuk ke menu "Pesanan" (Login menggunakan akun yang dikirim ke email).',
                    'Klik "Konfirmasi Bayar" dan unggah foto bukti transfer Anda.',
                    'Admin kami akan melakukan verifikasi secara manual dalam hitungan menit.',
                ],
            ],
            [
                'title' => 'Notifikasi & Persetujuan',
                'items' => [
                    'Anda akan menerima notifikasi otomatis via WhatsApp/Telegram saat Admin menyetujui pembayaran.',
                    'Status pesanan akan berubah menjadi "Diproses" dan diteruskan ke dapur Chef.',
                ],
            ],
            [
                'title' => 'Proses Masak & Quality Control',
                'items' => [
                    'Chef mitra kami akan menyiapkan masakan Anda dengan bahan segar sesuai jadwal.',
                    'Setelah matang, pesanan akan dikirim ke Drop Point untuk dilakukan pengecekan akhir (QC) oleh petugas kami guna memastikan kualitas tetap terjaga.',
                ],
            ],
            [
                'title' => 'Pengiriman ke Lokasi',
                'items' => [
                    'Petugas kami akan mengirimkan pesanan dari Drop Point ke lokasi Anda.',
                    'Anda akan menerima notifikasi "Sedang Dikirim" sehingga Anda dapat bersiap menerima paket.',
                ],
            ],
            [
                'title' => 'Penerimaan & Bukti Foto',
                'items' => [
                    'Saat pesanan sampai, petugas akan mengambil satu foto sebagai bukti serah terima yang sah.',
                    'Foto ini akan muncul di halaman detail pesanan Anda untuk transparansi.',
                ],
            ],
            [
                'title' => 'Konfirmasi Selesai & Review',
                'items' => [
                    'Setelah menerima dan memeriksa isi paket, klik tombol "Pesanan Diterima" di halaman detail pesanan.',
                    'Jika lupa, sistem akan menyelesaikan pesanan secara otomatis dalam waktu 6 jam setelah tiba.',
                    'Berikan rating bintang 5 dan ulasan positif untuk membantu Chef mitra kami terus berkembang!',
                ],
            ],
        ];
    }
}
