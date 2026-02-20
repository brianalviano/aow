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
        Schema::create('discount_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('discount_id')->constrained()->cascadeOnDelete();

            // POLYMORPHIC RELATION
            // Bisa mengarah ke tabel 'products' atau 'categories'
            // item_type: 'App\Models\Product' atau 'App\Models\Category'
            // item_id: UUID dari produk atau kategori
            $table->uuidMorphs('itemable');

            // SYARAT KUANTITAS (Trigger)
            // Misal: Beli minimal 2 (item ini), baru dapat diskon
            $table->integer('min_qty_buy')->default(1);
            $table->boolean('is_multiple')->default(false);

            // KHUSUS TIPE BOGO (Hadiahnya apa?)
            // Jika NULL, berarti diskon potongan harga biasa.
            // Jika Terisi, berarti dapat barang gratis ini.
            $table->foreignUuid('free_product_id')->nullable()->constrained('products');
            $table->integer('free_qty_get')->default(0); // Dapat gratis berapa?

            // OVERRIDE VALUE (Opsional)
            // Hanya diisi jika nilai diskon item ini BEDA dari nilai default di tabel parent
            $table->decimal('custom_value', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_items');
    }
};
