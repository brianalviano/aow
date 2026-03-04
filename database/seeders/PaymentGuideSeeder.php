<?php

namespace Database\Seeders;

use App\Models\PaymentGuide;
use Illuminate\Database\Seeder;

class PaymentGuideSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guides = [
            [
                'name' => 'Transfer Bank (Manual)',
                'content' => [
                    [
                        'title' => 'ATM',
                        'items' => [
                            'Masukkan kartu ATM dan PIN Anda.',
                            'Pilih menu Transaksi Lainnya > Transfer > Ke Rek BCA.',
                            'Masukkan nomor rekening tujuan yang tertera di halaman pembayaran.',
                            'Masukkan jumlah pembayaran sesuai dengan total tagihan (hingga digit terakhir).',
                            'Ikuti instruksi selanjutnya untuk menyelesaikan pembayaran.',
                        ],
                    ],
                    [
                        'title' => 'm-BCA (Mobile Banking)',
                        'items' => [
                            'Buka aplikasi BCA Mobile dan pilih m-BCA.',
                            'Masukkan Kode Akses Anda.',
                            'Pilih m-Transfer > Antar Rekening.',
                            'Pilih/Daftarkan nomor rekening tujuan yang tertera di halaman pembayaran.',
                            'Masukkan jumlah pembayaran sesuai dengan total tagihan.',
                            'Klik OK dan masukkan PIN m-BCA Anda.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'E-Wallet (OVO/ShopeePay/Dana/GoPay)',
                'content' => [
                    [
                        'title' => 'Cara Pembayaran',
                        'items' => [
                            'Pastikan Anda memiliki aplikasi E-Wallet yang dipilih.',
                            'Klik tombol "Bayar" untuk diproses.',
                            'Anda akan diarahkan ke aplikasi E-Wallet atau melihat kode QR.',
                            'Lakukan scan QR atau konfirmasi pembayaran di aplikasi.',
                            'Setelah pembayaran berhasil, status pesanan Anda akan otomatis diperbarui.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'QRIS',
                'content' => [
                    [
                        'title' => 'Cara Pembayaran (Scan QR)',
                        'items' => [
                            'Pilih metode pembayaran QRIS saat checkout.',
                            'Klik tombol "Bayar" untuk menampilkan Kode QR.',
                            'Buka aplikasi e-wallet atau perbankan Anda (Gopay, OVO, Dana, ShopeePay, BCA Mobile, dll).',
                            'Pilih fitur "Scan" atau "Bayar".',
                            'Arahkan kamera ke Kode QR yang muncul pada layar.',
                            'Periksa nominal yang muncul, pastikan sesuai dengan total tagihan.',
                            'Masukkan PIN Anda dan tunggu hingga konfirmasi pembayaran berhasil.',
                        ],
                    ],
                    [
                        'title' => 'Cara Pembayaran (Simpan / Screenshot)',
                        'items' => [
                            'Pilih metode pembayaran QRIS saat checkout.',
                            'Klik tombol "Bayar" untuk menampilkan Kode QR.',
                            'Simpan (Download) atau Screenshot Kode QR yang muncul di layar HP Anda.',
                            'Buka aplikasi e-wallet atau perbankan Anda (Gopay, OVO, Dana, ShopeePay, BCA Mobile, dll).',
                            'Pilih fitur "Scan" atau "Bayar".',
                            'Pilih ikon gambar/galeri untuk mengunggah gambar Kode QR yang sudah disimpan/discreenshot.',
                            'Periksa nominal yang muncul, pastikan sesuai dengan total tagihan.',
                            'Masukkan PIN Anda dan tunggu hingga konfirmasi pembayaran berhasil.',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($guides as $guide) {
            PaymentGuide::updateOrCreate(
                ['name' => $guide['name']],
                $guide
            );
        }
    }
}
