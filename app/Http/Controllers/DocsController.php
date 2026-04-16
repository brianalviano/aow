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
                'desc'   => 'Order dibuat oleh customer melalui proses checkout. Menunggu konfirmasi dari admin.',
            ],
            [
                'status' => 'CONFIRMED',
                'actor'  => 'Admin',
                'color'  => 'yellow',
                'desc'   => 'Admin mengkonfirmasi order dan meneruskannya ke chef yang bertanggung jawab.',
            ],
            [
                'status' => 'SHIPPED',
                'actor'  => 'Chef',
                'color'  => 'orange',
                'desc'   => 'Chef telah menyiapkan makanan dan mengirimkannya ke pickup point.',
            ],
            [
                'status' => 'AT_PICKUP_POINT',
                'actor'  => 'PIC',
                'color'  => 'purple',
                'desc'   => 'PIC mengkonfirmasi makanan telah tiba di pickup point dan siap didistribusikan.',
            ],
            [
                'status' => 'ON_DELIVERY',
                'actor'  => 'PIC',
                'color'  => 'indigo',
                'desc'   => 'PIC mengirimkan order kepada customer. Untuk instant order: via kurir Biteship.',
            ],
            [
                'status' => 'DELIVERED',
                'actor'  => 'System / Customer',
                'color'  => 'green',
                'desc'   => 'Order telah diterima customer. Customer dapat memberikan testimoni untuk setiap item.',
            ],
            [
                'status' => 'CANCELLED',
                'actor'  => 'Admin / Chef',
                'color'  => 'red',
                'desc'   => 'Order dibatalkan. Dapat terjadi di titik manapun sebelum status DELIVERED.',
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
            'desc'     => 'Pengguna akhir yang melakukan pemesanan makanan. Dapat memesan melalui pre-order (drop point) maupun instant order (alamat langsung).',
            'sections' => [
                [
                    'id'    => 'cust-auth',
                    'title' => 'Autentikasi & Profil',
                    'desc'  => 'Manajemen akun customer — registrasi, login, profil, dan alamat pengiriman.',
                    'routes' => [
                        ['method' => 'GET',    'path' => '/register',               'desc' => 'Halaman formulir registrasi'],
                        ['method' => 'POST',   'path' => '/register',               'desc' => 'Proses registrasi akun baru'],
                        ['method' => 'GET',    'path' => '/login',                  'desc' => 'Halaman formulir login'],
                        ['method' => 'POST',   'path' => '/login',                  'desc' => 'Proses autentikasi customer'],
                        ['method' => 'POST',   'path' => '/logout',                 'desc' => 'Keluar dari sesi'],
                        ['method' => 'GET',    'path' => '/profile',                'desc' => 'Halaman edit profil customer'],
                        ['method' => 'PUT',    'path' => '/profile',                'desc' => 'Simpan perubahan profil'],
                        ['method' => 'GET',    'path' => '/custom-address',         'desc' => 'Daftar alamat pengiriman custom'],
                        ['method' => 'POST',   'path' => '/custom-address',         'desc' => 'Tambah alamat baru'],
                        ['method' => 'PUT',    'path' => '/custom-address/{id}',    'desc' => 'Perbarui alamat'],
                        ['method' => 'DELETE', 'path' => '/custom-address/{id}',    'desc' => 'Hapus alamat'],
                    ],
                ],
                [
                    'id'    => 'cust-browse',
                    'title' => 'Browsing & Menu',
                    'desc'  => 'Eksplorasi produk, drop point, dan menu yang tersedia.',
                    'routes' => [
                        ['method' => 'GET', 'path' => '/',                               'desc' => 'Halaman beranda / home'],
                        ['method' => 'GET', 'path' => '/menu',                           'desc' => 'Halaman menu lengkap'],
                        ['method' => 'GET', 'path' => '/products',                       'desc' => 'Daftar semua produk (general)'],
                        ['method' => 'GET', 'path' => '/drop-points',                    'desc' => 'Daftar drop point yang tersedia'],
                        ['method' => 'GET', 'path' => '/drop-points/{id}',               'desc' => 'Detail drop point'],
                        ['method' => 'GET', 'path' => '/drop-points/{id}/products',      'desc' => 'Produk tersedia di drop point tertentu'],
                        ['method' => 'GET', 'path' => '/products/{id}/testimonials',     'desc' => 'Testimoni pelanggan untuk produk tertentu'],
                        ['method' => 'GET', 'path' => '/privacy-policy',                 'desc' => 'Halaman kebijakan privasi'],
                        ['method' => 'GET', 'path' => '/terms-and-conditions',           'desc' => 'Halaman syarat & ketentuan'],
                    ],
                ],
                [
                    'id'    => 'cust-checkout',
                    'title' => 'Checkout & Pembayaran',
                    'desc'  => 'Alur pemesanan dari pemilihan tipe order hingga pembayaran berhasil.',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/order-type',                    'desc' => 'Pilih tipe order: pre-order atau instant'],
                        ['method' => 'POST', 'path' => '/order-type',                    'desc' => 'Simpan pilihan tipe order ke sesi'],
                        ['method' => 'GET',  'path' => '/checkout',                      'desc' => 'Halaman checkout & ringkasan keranjang'],
                        ['method' => 'POST', 'path' => '/checkout/session',              'desc' => 'Buat sesi order baru'],
                        ['method' => 'POST', 'path' => '/checkout/update-session',       'desc' => 'Perbarui data sesi checkout'],
                        ['method' => 'GET',  'path' => '/payment-summary',               'desc' => 'Ringkasan pembayaran sebelum konfirmasi'],
                        ['method' => 'GET',  'path' => '/payment/{order}',               'desc' => 'Halaman pembayaran untuk order tertentu'],
                        ['method' => 'POST', 'path' => '/payment',                       'desc' => 'Proses pembayaran via Midtrans'],
                        ['method' => 'POST', 'path' => '/payment/{order}/proof',         'desc' => 'Upload bukti transfer manual'],
                        ['method' => 'POST', 'path' => '/payment/{order}/update-method', 'desc' => 'Ganti metode pembayaran untuk order belum bayar'],
                        ['method' => 'GET',  'path' => '/payment/{order}/qris-download', 'desc' => 'Download QR code QRIS'],
                    ],
                ],
                [
                    'id'    => 'cust-orders',
                    'title' => 'Lacak Pesanan & Testimoni',
                    'desc'  => 'Histori pesanan, detail, penyelesaian, dan pemberian testimoni setelah terima pesanan.',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/orders',                        'desc' => 'Daftar semua pesanan customer (dengan filter)'],
                        ['method' => 'GET',  'path' => '/orders/{order}',                'desc' => 'Detail pesanan beserta status dan item'],
                        ['method' => 'POST', 'path' => '/orders/{order}/complete',       'desc' => 'Konfirmasi pesanan sudah diterima'],
                        ['method' => 'POST', 'path' => '/order-items/{item}/testimonial','desc' => 'Submit rating & foto testimoni untuk item pesanan'],
                    ],
                ],
                [
                    'id'    => 'cust-engage',
                    'title' => 'Feedback, Notifikasi & Food Request',
                    'desc'  => 'Kanal komunikasi dua arah — feedback umum, notifikasi pesanan, dan permintaan menu baru.',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/feedback',                      'desc' => 'Halaman formulir feedback'],
                        ['method' => 'POST', 'path' => '/feedback',                      'desc' => 'Kirim feedback ke admin'],
                        ['method' => 'GET',  'path' => '/food-requests',                 'desc' => 'Daftar permintaan makanan yang sudah dikirim'],
                        ['method' => 'POST', 'path' => '/food-requests',                 'desc' => 'Ajukan permintaan menu baru'],
                        ['method' => 'GET',  'path' => '/notifications',                 'desc' => 'Daftar notifikasi personal customer'],
                        ['method' => 'POST', 'path' => '/notifications/mark-as-read',   'desc' => 'Tandai semua notifikasi sebagai sudah dibaca'],
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
            'desc'     => 'Mitra dapur yang menerima dan mempersiapkan item pesanan. Chef hanya melihat item yang diassign kepadanya, bukan keseluruhan order.',
            'sections' => [
                [
                    'id'    => 'chef-dashboard',
                    'title' => 'Dashboard',
                    'desc'  => 'Ringkasan item pesanan yang sedang menunggu konfirmasi chef.',
                    'routes' => [
                        ['method' => 'GET', 'path' => '/chef',     'desc' => 'Dashboard utama — daftar item pending beserta info customer & drop point'],
                        ['method' => 'GET', 'path' => '/chef/report', 'desc' => 'Laporan keuangan — total pendapatan, potongan fee, dan saldo'],
                    ],
                    'notes' => [
                        'Chef hanya melihat item yang telah di-assign admin kepadanya.',
                        'Login via /chef/login menggunakan guard auth:chef yang terpisah dari admin.',
                    ],
                ],
                [
                    'id'    => 'chef-orders',
                    'title' => 'Manajemen Item Pesanan',
                    'desc'  => 'Tindakan utama chef: menyetujui, menolak, atau mengirim item ke pickup point. Mendukung bulk action (pilih banyak item sekaligus).',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/chef/orders',         'desc' => 'Daftar item pesanan dengan filter (tanggal, status, pencarian)'],
                        ['method' => 'POST', 'path' => '/chef/orders/approve', 'desc' => 'Terima item pesanan — ubah status item menjadi ACCEPTED'],
                        ['method' => 'POST', 'path' => '/chef/orders/reject',  'desc' => 'Tolak item pesanan dengan alasan (opsional). Membatalkan seluruh order'],
                        ['method' => 'POST', 'path' => '/chef/orders/ship',    'desc' => 'Tandai item sudah dikirim ke pickup point'],
                    ],
                    'flow' => [
                        'PENDING → ACCEPTED (approve)',
                        'ACCEPTED → SHIPPED (ship)',
                        'PENDING/ACCEPTED → REJECTED (reject)',
                    ],
                ],
                [
                    'id'    => 'chef-report',
                    'title' => 'Laporan Keuangan',
                    'desc'  => 'Ringkasan pendapatan chef setelah dipotong fee platform. Filter tersedia untuk rentang waktu.',
                    'routes' => [
                        ['method' => 'GET', 'path' => '/chef/report', 'desc' => 'Laporan keuangan lengkap dengan rentang waktu'],
                    ],
                    'notes' => [
                        'Filter: 30 hari terakhir, 90 hari terakhir, atau rentang custom.',
                        'Menampilkan: Total Kotor, Potongan Fee (%), Net Income, Total Ditransfer, Saldo Terutang.',
                    ],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // PIC Role
    // -------------------------------------------------------------------------

    private function picRole(): array
    {
        return [
            'id'       => 'pic',
            'name'     => 'PIC Pickup Point',
            'color'    => 'green',
            'desc'     => 'Petugas pickup point yang menerima makanan dari chef dan mendistribusikannya ke customer. Hanya dapat mengelola order yang ditugaskan ke pickup point-nya.',
            'sections' => [
                [
                    'id'    => 'pic-dashboard',
                    'title' => 'Dashboard',
                    'desc'  => 'Tampilan tab berdasarkan status order di pickup point yang bersangkutan.',
                    'routes' => [
                        ['method' => 'GET', 'path' => '/pic', 'desc' => 'Dashboard dengan 4 tab: Incoming, At Pickup, On Delivery, Completed'],
                    ],
                    'notes' => [
                        'Login via /pic/login menggunakan guard auth:pickup_officer.',
                        'Incoming — Makanan sedang dalam perjalanan dari chef.',
                        'At Pickup — Makanan sudah tiba di pickup point.',
                        'On Delivery — Makanan sedang dikirim ke customer.',
                        'Completed — Order telah berhasil diterima customer.',
                    ],
                ],
                [
                    'id'    => 'pic-orders',
                    'title' => 'Operasi Order',
                    'desc'  => 'Tiga aksi utama PIC dalam alur distribusi pesanan.',
                    'routes' => [
                        ['method' => 'POST', 'path' => '/pic/orders/{order}/approve',  'desc' => 'Konfirmasi makanan tiba di pickup point (AT_PICKUP_POINT)'],
                        ['method' => 'POST', 'path' => '/pic/orders/{order}/send',     'desc' => 'Kirim ke customer. Instant order: request kurir via Biteship otomatis'],
                        ['method' => 'POST', 'path' => '/pic/orders/{order}/complete', 'desc' => 'Konfirmasi order sudah diterima customer (pre-order manual)'],
                    ],
                    'flow' => [
                        'SHIPPED → AT_PICKUP_POINT (approve)',
                        'AT_PICKUP_POINT → ON_DELIVERY (send)',
                        'ON_DELIVERY → DELIVERED (complete)',
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
            'desc'     => 'Pengelola sistem secara penuh. Admin dapat mengakses seluruh fitur mulai dari manajemen order, produk, chef, hingga laporan dan pengaturan sistem.',
            'sections' => [
                [
                    'id'    => 'admin-dashboard',
                    'title' => 'Dashboard',
                    'desc'  => 'Ringkasan performa bisnis secara real-time.',
                    'routes' => [
                        ['method' => 'GET', 'path' => '/admin/dashboard', 'desc' => 'Dashboard: total revenue, order, customer, grafik 6 bulan, top produk'],
                    ],
                    'notes' => [
                        'Login via /admin/login menggunakan guard auth (default Laravel).',
                        'Role admin-only dapat diakses hanya oleh admin dengan role tertinggi.',
                    ],
                ],
                [
                    'id'    => 'admin-orders',
                    'title' => 'Manajemen Order',
                    'desc'  => 'Kontrol penuh atas seluruh siklus hidup order — dari konfirmasi hingga pengiriman.',
                    'routes' => [
                        ['method' => 'GET',   'path' => '/admin/orders',                             'desc' => 'Daftar semua order dengan filter & counter status'],
                        ['method' => 'GET',   'path' => '/admin/orders/payments',                    'desc' => 'Daftar order menunggu verifikasi pembayaran'],
                        ['method' => 'GET',   'path' => '/admin/orders/processing',                  'desc' => 'Daftar order sedang diproses (filter: drop point, chef, tanggal)'],
                        ['method' => 'GET',   'path' => '/admin/orders/{order}',                     'desc' => 'Detail order: item, chef, pembayaran, pengiriman, testimoni'],
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/confirm',             'desc' => 'Konfirmasi order PENDING → CONFIRMED, teruskan ke chef'],
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/cancel',              'desc' => 'Batalkan order dengan catatan (opsional)'],
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/ship',                'desc' => 'Ubah status order menjadi SHIPPED'],
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/deliver',             'desc' => 'Ubah status order menjadi DELIVERED'],
                        ['method' => 'PATCH', 'path' => '/admin/orders/{order}/pickup-point',        'desc' => 'Ganti pickup point (jika item belum semua dikirim)'],
                        ['method' => 'POST',  'path' => '/admin/order-items/{item}/reassign-chef',   'desc' => 'Pindahkan item ke chef lain'],
                        ['method' => 'PATCH', 'path' => '/admin/testimonials/{testimonial}/approve', 'desc' => 'Setujui dan publikasikan testimoni customer'],
                        ['method' => 'DELETE','path' => '/admin/testimonials/{testimonial}',         'desc' => 'Tolak dan hapus testimoni customer'],
                    ],
                ],
                [
                    'id'    => 'admin-products',
                    'title' => 'Produk & Kategori',
                    'desc'  => 'Pengelolaan katalog produk beserta kategori dan slider beranda.',
                    'routes' => [
                        ['method' => 'GET',    'path' => '/admin/products',              'desc' => 'Daftar produk dengan filter kategori & pencarian'],
                        ['method' => 'GET',    'path' => '/admin/products/create',       'desc' => 'Formulir tambah produk baru'],
                        ['method' => 'POST',   'path' => '/admin/products',              'desc' => 'Simpan produk baru (dengan opsi/varian)'],
                        ['method' => 'GET',    'path' => '/admin/products/{id}/edit',    'desc' => 'Formulir edit produk'],
                        ['method' => 'PUT',    'path' => '/admin/products/{id}',         'desc' => 'Perbarui data produk'],
                        ['method' => 'DELETE', 'path' => '/admin/products/{id}',         'desc' => 'Hapus produk'],
                        ['method' => 'GET',    'path' => '/admin/product-categories',    'desc' => 'Daftar kategori produk'],
                        ['method' => 'POST',   'path' => '/admin/product-categories',    'desc' => 'Tambah kategori baru'],
                        ['method' => 'PUT',    'path' => '/admin/product-categories/{id}','desc' => 'Perbarui kategori'],
                        ['method' => 'DELETE', 'path' => '/admin/product-categories/{id}','desc' => 'Hapus kategori'],
                        ['method' => 'GET',    'path' => '/admin/sliders',               'desc' => 'Daftar slider / banner beranda'],
                        ['method' => 'POST',   'path' => '/admin/sliders',               'desc' => 'Tambah slider baru'],
                        ['method' => 'PUT',    'path' => '/admin/sliders/{id}',          'desc' => 'Perbarui slider'],
                        ['method' => 'DELETE', 'path' => '/admin/sliders/{id}',          'desc' => 'Hapus slider'],
                    ],
                ],
                [
                    'id'    => 'admin-chefs',
                    'title' => 'Manajemen Chef',
                    'desc'  => 'CRUD chef mitra, assignment produk, dan manajemen transfer/pencairan dana.',
                    'routes' => [
                        ['method' => 'GET',    'path' => '/admin/chefs',                 'desc' => 'Daftar chef dengan info produk & status aktif'],
                        ['method' => 'GET',    'path' => '/admin/chefs/create',          'desc' => 'Formulir tambah chef baru'],
                        ['method' => 'POST',   'path' => '/admin/chefs',                 'desc' => 'Daftarkan chef baru'],
                        ['method' => 'GET',    'path' => '/admin/chefs/{chef}',          'desc' => 'Detail chef: produk, laporan penjualan, riwayat transfer'],
                        ['method' => 'GET',    'path' => '/admin/chefs/{chef}/edit',     'desc' => 'Formulir edit data chef'],
                        ['method' => 'PUT',    'path' => '/admin/chefs/{chef}',          'desc' => 'Perbarui data chef'],
                        ['method' => 'DELETE', 'path' => '/admin/chefs/{chef}',          'desc' => 'Nonaktifkan / hapus chef'],
                        ['method' => 'POST',   'path' => '/admin/chefs/{chef}/transfers','desc' => 'Catat transfer pencairan dana ke chef'],
                    ],
                ],
                [
                    'id'    => 'admin-locations',
                    'title' => 'Drop Point & Pickup Point',
                    'desc'  => 'Manajemen lokasi pengambilan (drop point) dan titik distribusi (pickup point) beserta petugasnya.',
                    'routes' => [
                        ['method' => 'GET',    'path' => '/admin/drop-points',                  'desc' => 'Daftar drop point (sekolah, kantor, dll)'],
                        ['method' => 'POST',   'path' => '/admin/drop-points',                  'desc' => 'Tambah drop point baru'],
                        ['method' => 'PUT',    'path' => '/admin/drop-points/{id}',             'desc' => 'Perbarui drop point'],
                        ['method' => 'DELETE', 'path' => '/admin/drop-points/{id}',             'desc' => 'Hapus drop point'],
                        ['method' => 'GET',    'path' => '/admin/pick-up-points',               'desc' => 'Daftar pickup point'],
                        ['method' => 'POST',   'path' => '/admin/pick-up-points',               'desc' => 'Tambah pickup point baru'],
                        ['method' => 'PUT',    'path' => '/admin/pick-up-points/{id}',          'desc' => 'Perbarui pickup point'],
                        ['method' => 'DELETE', 'path' => '/admin/pick-up-points/{id}',          'desc' => 'Hapus pickup point'],
                        ['method' => 'GET',    'path' => '/admin/pick-up-point-officers',       'desc' => 'Daftar petugas pickup point (PIC)'],
                        ['method' => 'POST',   'path' => '/admin/pick-up-point-officers',       'desc' => 'Tambah PIC baru & tautkan ke pickup point'],
                        ['method' => 'PUT',    'path' => '/admin/pick-up-point-officers/{id}',  'desc' => 'Perbarui data PIC'],
                        ['method' => 'DELETE', 'path' => '/admin/pick-up-point-officers/{id}',  'desc' => 'Hapus PIC'],
                    ],
                ],
                [
                    'id'    => 'admin-payments',
                    'title' => 'Metode Pembayaran',
                    'desc'  => 'Konfigurasi metode bayar yang tersedia untuk customer beserta panduan penggunaannya.',
                    'routes' => [
                        ['method' => 'GET',    'path' => '/admin/payment-methods',            'desc' => 'Daftar metode pembayaran aktif'],
                        ['method' => 'POST',   'path' => '/admin/payment-methods',            'desc' => 'Tambah metode pembayaran (gateway / manual)'],
                        ['method' => 'PUT',    'path' => '/admin/payment-methods/{id}',       'desc' => 'Perbarui metode pembayaran (fee, tipe, dll)'],
                        ['method' => 'DELETE', 'path' => '/admin/payment-methods/{id}',       'desc' => 'Hapus metode pembayaran'],
                        ['method' => 'GET',    'path' => '/admin/payment-guides',             'desc' => 'Daftar panduan pembayaran (langkah-langkah transfer)'],
                        ['method' => 'POST',   'path' => '/admin/payment-guides',             'desc' => 'Tambah panduan pembayaran baru'],
                        ['method' => 'PUT',    'path' => '/admin/payment-guides/{id}',        'desc' => 'Perbarui panduan pembayaran'],
                        ['method' => 'DELETE', 'path' => '/admin/payment-guides/{id}',        'desc' => 'Hapus panduan pembayaran'],
                    ],
                ],
                [
                    'id'    => 'admin-customers',
                    'title' => 'Manajemen Customer',
                    'desc'  => 'Lihat dan cari data customer terdaftar beserta riwayat pesanannya.',
                    'routes' => [
                        ['method' => 'GET', 'path' => '/admin/customers',        'desc' => 'Daftar semua customer dengan pencarian'],
                        ['method' => 'GET', 'path' => '/admin/customers/{id}',   'desc' => 'Profil customer: detail, alamat, histori order'],
                    ],
                ],
                [
                    'id'    => 'admin-requests',
                    'title' => 'Food Request',
                    'desc'  => 'Tinjau dan tindaklanjuti permintaan menu baru dari customer.',
                    'routes' => [
                        ['method' => 'GET',   'path' => '/admin/food-requests',        'desc' => 'Daftar food request dari customer'],
                        ['method' => 'PATCH', 'path' => '/admin/food-requests/{id}',   'desc' => 'Update status request (PENDING → APPROVED / REJECTED / COMPLETED)'],
                    ],
                ],
                [
                    'id'    => 'admin-reports',
                    'title' => 'Laporan',
                    'desc'  => 'Laporan penjualan dan produk dengan kemampuan ekspor ke PDF dan Excel.',
                    'routes' => [
                        ['method' => 'GET', 'path' => '/admin/reports',               'desc' => 'Dashboard laporan penjualan & produk dengan filter tanggal & drop point'],
                        ['method' => 'GET', 'path' => '/admin/reports/export-pdf',    'desc' => 'Ekspor laporan ke format PDF'],
                        ['method' => 'GET', 'path' => '/admin/reports/export-excel',  'desc' => 'Ekspor laporan ke format Excel (.xlsx)'],
                    ],
                ],
                [
                    'id'    => 'admin-settings',
                    'title' => 'Pengaturan & Pengguna',
                    'desc'  => 'Konfigurasi sistem, akun admin, dan manajemen user admin.',
                    'routes' => [
                        ['method' => 'GET', 'path' => '/admin/account/settings',  'desc' => 'Pengaturan akun pribadi admin (nama, password, dll)'],
                        ['method' => 'PUT', 'path' => '/admin/account/settings',  'desc' => 'Simpan perubahan akun'],
                        ['method' => 'GET', 'path' => '/admin/settings',          'desc' => 'Pengaturan sistem (admin-only): order window, cutoff, min days'],
                        ['method' => 'PUT', 'path' => '/admin/settings',          'desc' => 'Simpan perubahan pengaturan sistem'],
                        ['method' => 'GET', 'path' => '/admin/users',             'desc' => 'Daftar akun admin (admin-only)'],
                        ['method' => 'POST','path' => '/admin/users',             'desc' => 'Tambah akun admin baru'],
                        ['method' => 'PUT', 'path' => '/admin/users/{id}',        'desc' => 'Perbarui akun admin'],
                        ['method' => 'DELETE','path' => '/admin/users/{id}',      'desc' => 'Hapus akun admin'],
                        ['method' => 'GET', 'path' => '/admin/notifications',     'desc' => 'Daftar notifikasi sistem'],
                        ['method' => 'GET', 'path' => '/admin/notifications/stats','desc' => 'Statistik notifikasi (jumlah belum dibaca)'],
                        ['method' => 'PATCH','path' => '/admin/notifications/{id}','desc' => 'Tandai notifikasi sebagai sudah dibaca'],
                        ['method' => 'PATCH','path' => '/admin/notifications/mark-all-read','desc' => 'Tandai semua notifikasi sebagai sudah dibaca'],
                    ],
                ],
            ],
        ];
    }
}
