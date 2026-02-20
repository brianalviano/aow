<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{StockDocumentType, StockDocumentReason, StockBucket, StockDocumentStatus};

/**
 * Tabel header surat stok IN/OUT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('stock_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->date('document_date');
            $table->enum('type', StockDocumentType::values());
            $table->enum('reason', StockDocumentReason::values());
            $table->enum('status', StockDocumentStatus::values())->default('draft');
            $table->enum('bucket', StockBucket::values())->nullable();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->foreignUuid('user_id')->constrained();
            $table->nullableUuidMorphs('sourceable');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['warehouse_id', 'number'], 'stock_documents_warehouse_number_idx');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_documents');
    }
};
