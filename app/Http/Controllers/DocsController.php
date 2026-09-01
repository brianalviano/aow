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
            'roles' => $this->roles(),
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
                'actor' => 'Customer',
                'color' => 'blue',
                'desc' => 'Pesanan dibuat. Customer memilih Drop Point atau Alamat Kustom, memilih produk, serta menentukan jadwal pengiriman. Akun dibuat otomatis jika belum terdaftar.',
            ],
            [
                'status' => 'CONFIRMED',
                'actor' => 'Admin',
                'color' => 'yellow',
                'desc' => 'Admin memverifikasi pembayaran. Pesanan dikonfirmasi dan siap untuk dimasak.',
            ],
            [
                'status' => 'COOKING',
                'actor' => 'Admin / Dapur',
                'color' => 'orange',
                'desc' => 'Dapur Utama AOWenak sedang memasak dan menyiapkan makanan sesuai pesanan.',
            ],
            [
                'status' => 'ON_DELIVERY',
                'actor' => 'Kurir / Admin',
                'color' => 'indigo',
                'desc' => 'Pesanan siap dan sedang dalam proses pengiriman menuju Drop Point atau Alamat Customer.',
            ],
            [
                'status' => 'ARRIVED',
                'actor' => 'Kurir / Admin',
                'color' => 'info',
                'desc' => 'Pesanan tiba di lokasi tujuan (Drop Point / Alamat Customer). Customer menerima notifikasi serah terima.',
            ],
            [
                'status' => 'DELIVERED',
                'actor' => 'Customer / System',
                'color' => 'green',
                'desc' => 'Customer mengonfirmasi penerimaan pesanan atau sistem menyelesaikan otomatis setelah 6 jam. Testimoni produk dapat diberikan.',
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
        ];
    }

    // -------------------------------------------------------------------------
    // Customer Role
    // -------------------------------------------------------------------------

    private function customerRole(): array
    {
        return [
            'id' => 'customer',
            'name' => 'Customer',
            'color' => 'blue',
            'desc' => 'Pengguna akhir yang melakukan pemesanan makanan. Akun dibuat otomatis saat checkout pertama kali.',
            'sections' => [
                [
                    'id' => 'cust-profile',
                    'title' => 'Akses & Akun',
                    'desc' => 'Customer masuk ke halaman utama, memilih titik Drop Point atau alamat tujuan, lalu memilih produk.',
                    'routes' => [
                        ['method' => 'GET',     'path' => '/',                       'desc' => 'Landing page & Product selection'],
                        ['method' => 'EMAIL',   'path' => 'Auto-Generated',          'desc' => 'Info kredensial akun dikirim otomatis ke email setelah checkout'],
                        ['method' => 'GET',    'path' => '/orders',                 'desc' => 'Menu Pesanan di Beranda untuk lacak status pesanan'],
                    ],
                ],
                [
                    'id' => 'cust-checkout',
                    'title' => 'Checkout & Pembayaran',
                    'desc' => 'Menentukan tanggal & jam pengiriman, catatan, dan metode pembayaran.',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/checkout',                'desc' => 'Halaman ringkasan belanja & input jadwal pengiriman'],
                        ['method' => 'POST', 'path' => '/payment/{order}/proof',   'desc' => 'Pembayaran & upload bukti transfer (jika manual)'],
                    ],
                    'flow' => [
                        'Pilih Drop Point atau Alamat Kustom',
                        'Pilih Produk & Checkout (Isi Tanggal, Jam, Catatan)',
                        'Isi Data Diri di Halaman Checkout/Pembayaran',
                        'Pilih Metode Pembayaran & Selesaikan Transaksi',
                    ],
                ],
                [
                    'id' => 'cust-tracking',
                    'title' => 'Penerimaan & Testimoni',
                    'desc' => 'Menerima notifikasi status pengiriman real-time via WhatsApp / Email.',
                    'routes' => [
                        ['method' => 'GET',  'path' => '/orders/{order}',          'desc' => 'Detail pesanan, bukti foto pengiriman, dan status'],
                        ['method' => 'POST', 'path' => '/orders/{order}/complete', 'desc' => 'Konfirmasi pesanan diterima secara manual'],
                    ],
                    'notes' => [
                        'Jika dalam 6 jam tidak diklik Selesai setelah tiba, sistem akan menyelesaikan pesanan otomatis.',
                        'Testimoni dapat diberikan setelah pesanan berstatus Selesai.',
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
            'id' => 'admin',
            'name' => 'Admin',
            'color' => 'purple',
            'desc' => 'Pengelola sistem yang memverifikasi transaksi, mengelola produksi, katalog produk, dan laporan.',
            'sections' => [
                [
                    'id' => 'admin-orders',
                    'title' => 'Manajemen Pesanan',
                    'desc' => 'Admin memverifikasi pesanan, mencetak struk/label pengiriman, dan memperbarui status.',
                    'routes' => [
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/confirm',      'desc' => 'Konfirmasi pesanan & mulai produksi'],
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/deliver',      'desc' => 'Selesaikan pesanan & unggah bukti foto'],
                        ['method' => 'POST',  'path' => '/admin/orders/{order}/cancel',       'desc' => 'Batalkan pesanan dengan menyertakan alasan'],
                    ],
                ],
                [
                    'id' => 'admin-catalog',
                    'title' => 'Manajemen Katalog & Lokasi',
                    'desc' => 'Mengatur produk, varian/opsi, harga, diskon, dan titik Drop Point.',
                    'routes' => [
                        ['method' => 'POST',   'path' => '/admin/products',              'desc' => 'Kelola menu produk, opsi, dan stok'],
                        ['method' => 'POST',   'path' => '/admin/drop-points',           'desc' => 'Kelola titik drop point pengantaran'],
                        ['method' => 'POST',   'path' => '/admin/payment-methods',       'desc' => 'Kelola metode pembayaran'],
                    ],
                ],
                [
                    'id' => 'admin-settings',
                    'title' => 'Konfigurasi Sistem',
                    'desc' => 'Pengaturan jam cut-off pemesanan, ongkir, notifikasi WhatsApp/Telegram, dan laporan penjualan.',
                    'routes' => [
                        ['method' => 'PUT', 'path' => '/admin/settings', 'desc' => 'Update konfigurasi operasional & notifikasi'],
                        ['method' => 'GET', 'path' => '/admin/reports',  'desc' => 'Laporan penjualan & analitik bisnis'],
                    ],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // User Guide
    // -------------------------------------------------------------------------

    private function userGuide(): array
    {
        return [
            [
                'title' => 'Akses Platform',
                'items' => [
                    'Buka halaman utama aowenak.com melalui browser Anda.',
                    'Jelajahi beragam menu kuliner berkualitas yang tersedia di katalog.',
                ],
            ],
            [
                'title' => 'Menentukan Lokasi Pengiriman',
                'items' => [
                    'Pilih "Drop Point" untuk mengambil pesanan di titik terdekat dengan biaya kirim hemat/gratis.',
                    'Pilih "Alamat Kustom" jika ingin pesanan diantar langsung ke lokasi Anda.',
                ],
            ],
            [
                'title' => 'Memilih Menu & Konfigurasi Opsi',
                'items' => [
                    'Pilih produk makanan/minuman yang diinginkan.',
                    'Tentukan varian rasa, tingkat kepedasan, atau ukuran kemasan sesuai selera.',
                    'Masukkan produk ke dalam keranjang belanja.',
                ],
            ],
            [
                'title' => 'Atur Jadwal & Checkout',
                'items' => [
                    'Buka keranjang dan klik "Checkout".',
                    'Tentukan tanggal dan jam pengantaran pesanan.',
                    'Tambahkan catatan khusus untuk dapur jika diperlukan.',
                ],
            ],
            [
                'title' => 'Pembayaran & Notifikasi',
                'items' => [
                    'Pilih metode pembayaran yang tersedia dan selesaikan instruksi pembayaran.',
                    'Sistem akan mengirimkan konfirmasi transaksi dan update status pesanan via WhatsApp & Email.',
                ],
            ],
            [
                'title' => 'Penerimaan & Ulasan',
                'items' => [
                    'Pesanan akan diantar sesuai jadwal yang Anda tentukan.',
                    'Setelah pesanan diterima, konfirmasi penerimaan di halaman detail pesanan.',
                    'Berikan rating bintang dan testimoni untuk menu yang Anda nikmati!',
                ],
            ],
        ];
    }
}
