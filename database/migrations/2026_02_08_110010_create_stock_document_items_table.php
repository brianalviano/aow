<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StockBucket;

/**
 * Tabel item surat stok IN/OUT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('stock_document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_document_id')->constrained();
            $table->foreignUuid('product_id')->constrained();
            $table->foreignUuid('product_division_id')->nullable()->constrained();
            $table->foreignUuid('product_rack_id')->nullable()->constrained();
            $table->foreignUuid('owner_user_id')->nullable()->constrained('users');
            $table->bigInteger('quantity')->default(0);
            $table->bigInteger('unit_price')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->enum('bucket', StockBucket::values())->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['stock_document_id', 'product_id'], 'stock_document_items_doc_product_idx');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_document_items');
    }
};
