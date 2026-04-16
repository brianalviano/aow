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
            $this->chefRole(),
            $this->picRole(),
            $this->adminRole(),
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
                'title' => 'Pilih Lokasi & Menu',
                'items' => [
                    'Buka halaman utama / aowenak.com',
                    'Pilih "Drop Point" (titik pengambilan) atau "Alamat Lain" (masukkan alamat lengkap & titik lokasi).',
                    'Pilih produk makanan/minuman yang Anda inginkan.',
                ],
            ],
            [
                'title' => 'Checkout & Jadwal',
                'items' => [
                    'Klik Checkout. Anda wajib mengisi Tanggal & Jam pengiriman.',
                    'Tambahkan catatan jika ada permintaan khusus.',
                ],
            ],
            [
                'title' => 'Akun & Pembayaran',
                'items' => [
                    'Isi data pribadi Anda di halaman pembayaran.',
                    'Akun akan dibuat otomatis dan informasi login dikirim ke Email Anda.',
                    'Lakukan pembayaran ke rekening BCA yang tertera.',
                    'Input/Upload bukti bayar agar dapat diverifikasi Admin.',
                ],
            ],
            [
                'title' => 'Verifikasi & Notifikasi',
                'items' => [
                    'Tunggu Admin memverifikasi dana yang masuk.',
                    'Anda akan menerima notifikasi (WA/Telegram/Email) saat Admin menyetujui pesanan.',
                    'Pesanan akan diteruskan ke Chef. Anda bisa memantau status di menu "Pesanan" pada beranda.',
                ],
            ],
            [
                'title' => 'Proses Masak & Pengiriman',
                'items' => [
                    'Chef akan memulai memasak setelah menyetujui pesanan Anda.',
                    'Setelah selesai, masakan dikirim ke Pickup Point.',
                    'PIC (Petugas Pickup) akan memastikan semua pesanan Anda lengkap sebelum dikirim ke lokasi Anda.',
                    'Anda mendapatkan notifikasi saat pesanan mulai dikirim (Status: Dikirim).',
                ],
            ],
            [
                'title' => 'Pesanan Tiba & Selesai',
                'items' => [
                    'Saat pesanan tiba, petugas akan upload bukti foto serah terima.',
                    'Klik "Selesaikan Pesanan" di halaman detail pesanan.',
                    'Jika dalam 6 jam Anda tidak klik selesai, sistem akan menganggap pesanan sukses (Selesai Otomatis).',
                    'Jangan lupa berikan testimoni dan bintang untuk masakan Chef kami!',
                ],
            ],
        ];
    }
}
