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
                'desc'   => 'Order dibuat oleh customer melalui proses checkout. Pembayaran dilakukan secara cash atau transfer manual yang kemudian divalidasi oleh admin.',
            ],
            [
                'status' => 'CONFIRMED',
                'actor'  => 'Admin',
                'color'  => 'yellow',
                'desc'   => 'Admin mengkonfirmasi order setelah pembayaran divalidasi. Item pesanan diteruskan ke chef masing-masing.',
            ],
            [
                'status' => 'ACCEPTED',
                'actor'  => 'Chef',
                'color'  => 'orange',
                'desc'   => 'Chef menerima item pesanan dan mulai mempersiapkan makanan sesuai jadwal delivery.',
            ],
            [
                'status' => 'SHIPPED',
                'actor'  => 'Chef',
                'color'  => 'orange',
                'desc'   => 'Makanan telah dikemas dan dikirim oleh Chef menuju Pickup Point tujuan.',
            ],
            [
                'status' => 'AT_PICKUP_POINT',
                'actor'  => 'PIC',
                'color'  => 'purple',
                'desc'   => 'PIC mengkonfirmasi bahwa makanan telah tiba di Pickup Point dan siap didistribusikan ke customer.',
            ],
            [
                'status' => 'ON_DELIVERY',
                'actor'  => 'PIC',
                'color'  => 'indigo',
                'desc'   => 'Pesanan sedang dalam proses pengiriman dari Pickup Point menuju lokasi customer.',
            ],
            [
                'status' => 'DELIVERED',
                'actor'  => 'System / Customer',
                'color'  => 'green',
                'desc'   => 'Order telah diterima. Di titik ini saldo chef akan bertambah dan customer bisa memberikan rating.',
            ],
            [
                'status' => 'CANCELLED',
                'actor'  => 'Admin / Chef',
                'color'  => 'red',
                'desc'   => 'Order dibatalkan. Uang akan dikembalikan sesuai kebijakan jika pembatalan dipicu oleh sistem/mitra.',
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
            'desc'     => 'Pengguna akhir yang melakukan pemesanan makanan. Customer melakukan pemesanan produk yang nantinya akan dikirimkan ke Drop Point pilihan.',
            'sections' => [
                [
                    'id'    => 'cust-profile',
                    'title' => 'Autentikasi & Alamat',
                    'desc'  => 'Customer dapat mendaftar dan mengelola profil mereka untuk kemudahan pelacakan pesanan.',
                    'routes' => [
                        ['method' => 'POST',   'path' => '/register',               'desc' => 'Registrasi customer baru'],
                        ['method' => 'POST',   'path' => '/login',                  'desc' => 'Autentikasi customer'],
                        ['method' => 'GET',    'path' => '/profile',                'desc' => 'Manajemen info pribadi'],
                    ],
                ],
                [
                    'id'    => 'cust-checkout',
                    'title' => 'Proses Pemesanan',
                    'desc'  => 'Proses belanja mulai dari pemilihan produk hingga konfirmasi pembayaran manual.',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/checkout',                'desc' => 'Halaman ringkasan belanja & pilihan Drop Point'],
                        ['method' => 'POST', 'path' => '/payment/{order}/proof',   'desc' => 'Upload bukti transfer untuk divalidasi Admin'],
                    ],
                    'flow' => [
                        'Tambahkan Produk ke Keranjang',
                        'Pilih Drop Point Lokasi Pengambilan',
                        'Pilih Metode Pembayaran (Cash/Transfer)',
                        'Upload Bukti Bayar & Lacak Status',
                    ],
                ],
                [
                    'id'    => 'cust-tracking',
                    'title' => 'Pelacakan & Testimoni',
                    'desc'  => 'Lacak status pesanan secara real-time dan berikan ulasan setelah makanan diterima.',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/orders/{order}',          'desc' => 'Detail pesanan dan timeline status'],
                        ['method' => 'POST', 'path' => '/orders/{order}/complete', 'desc' => 'Konfirmasi pesanan diterima secara manual'],
                        ['method' => 'POST', 'path' => '/order-items/{item}/testimonial', 'desc' => 'Berikan rating dan ulasan per item'],
                    ],
                    'notes' => [
                        'Testimoni hanya bisa diberikan untuk item dengan status DELIVERED.',
                        'Testimoni baru akan tampil publik setelah disetujui Admin.',
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
            'desc'     => 'Mitra dapur independen yang bertanggung jawab atas kualitas masakan. Chef bekerja berdasarkan daftar item yang telah dikonfirmasi oleh Admin.',
            'sections' => [
                [
                    'id'    => 'chef-ops',
                    'title' => 'Operasi Dapur',
                    'desc'  => 'Chef menerima notifikasi untuk setiap item baru dan harus mengkonfirmasi kesediaan mereka.',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/chef',                'desc' => 'Dashboard ringkasan item pending'],
                        ['method' => 'POST', 'path' => '/chef/orders/approve',  'desc' => 'Terima pesanan (Bulk disarankan)'],
                        ['method' => 'POST', 'path' => '/chef/orders/reject',   'desc' => 'Tolak pesanan (Membutuhkan alasan)'],
                        ['method' => 'POST', 'path' => '/chef/orders/ship',     'desc' => 'Kirim item ke Pickup Point'],
                    ],
                    'flow' => [
                        'Lihat Dashboard Item Baru',
                        'Klik "Approve" untuk memulai persiapan',
                        'Siapkan masakan sesuai pesanan',
                        'Klik "Ship" saat makanan dikirim ke kurir/pickup hub',
                    ],
                ],
                [
                    'id'    => 'chef-finance',
                    'title' => 'Laporan Pendapatan',
                    'desc'  => 'Pantau pendapatan kotor, potongan fee platform, dan saldo yang siap ditarik.',
                    'routes' => [
                        ['method' => 'GET', 'path' => '/chef/report', 'desc' => 'Detail keuangan per periode'],
                    ],
                    'notes' => [
                        'Saldo chef hanya akan bertambah SETELAH status order menjadi DELIVERED.',
                        'Potongan fee platform bersifat otomatis sesuai kesepakatan mitra.',
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
            'desc'     => 'Petugas lapangan di titik distribusi hub. PIC memastikan makanan dari berbagai Chef terkumpul dan sampai ke tangan Customer dengan tepat.',
            'sections' => [
                [
                    'id'    => 'pic-ops',
                    'title' => 'Manajemen Hub',
                    'desc'  => 'Menerima makanan dari Chef dan melakukan distribusi tahap akhir.',
                    'routes' => [
                        ['method' => 'POST', 'path' => '/pic/orders/{order}/approve',  'desc' => 'Konfirmasi kedatangan makanan di Hub'],
                        ['method' => 'POST', 'path' => '/pic/orders/{order}/send',     'desc' => 'Update status sedang dikirim ke customer'],
                        ['method' => 'POST', 'path' => '/pic/orders/{order}/complete', 'desc' => 'Selesaikan pesanan jika sudah diterima'],
                    ],
                    'flow' => [
                        'Terima paket dari Chef/Kurir pengirim',
                        'Validasi item & update status ke AT_PICKUP_POINT',
                        'Lakukan pengiriman atau koordinasi pengambilan',
                        'Update status ke DELIVERED jika sudah diterima',
                    ],
                    'notes' => [
                        'Pastikan titik koordinat Pickup Point sudah akurat untuk memudahkan operasional.',
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
            'desc'     => 'Pengelola platform utama. Admin memegang kendali atas seluruh flow bisnis, validasi keuangan, dan manajemen mitra.',
            'sections' => [
                [
                    'id'    => 'admin-orders',
                    'title' => 'Kontrol Pesanan',
                    'desc'  => 'Admin menjembatani Customer dan Chef. Pesanan baru tidak akan diteruskan ke Chef sebelum Admin melakukan validasi pembayaran.',
                    'routes' => [
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/confirm',      'desc' => 'Konfirmasi pesanan & Notifikasi Chef'],
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/cancel',       'desc' => 'Batalkan order (refund manual jika ada)'],
                        ['method' => 'PATCH', 'path' => '/admin/order-items/{item}/reassign', 'desc' => 'Pindah item ke Chef lain jika Chef awal berhalangan'],
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
                    'notes' => [
                        'Perubahan "Order Window" akan langsung mempengaruhi ketersediaan menu di sisi Customer.',
                    ],
                ],
            ],
        ];
    }
}
