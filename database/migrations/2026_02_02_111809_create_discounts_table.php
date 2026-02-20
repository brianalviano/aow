<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Contoh: "Promo Gajian"

            // Tipe Promo:
            // basic: Potongan harga biasa (persen/nominal)
            // bogo: Buy X Get Y
            $table->enum('type', ['basic', 'bogo'])->default('basic');

            // Lingkup Promo:
            // global: Potong total struk
            // specific: Potong per item (berdasarkan relasi di tabel discount_items)
            $table->enum('scope', ['global', 'specific'])->default('specific');

            // LOGIKA NILAI DEFAULT (Jika scope global, atau default value utk item)
            $table->enum('value_type', ['percentage', 'nominal'])->nullable();
            $table->decimal('value', 15, 2)->default(0); // Bisa simpan persen (10.00) atau rupiah (10000.00)

            // Waktu (Pakai DateTime agar support Happy Hour)
            $table->dateTime('start_at');
            $table->dateTime('end_at');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
