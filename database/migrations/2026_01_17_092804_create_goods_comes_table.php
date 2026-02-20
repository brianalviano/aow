<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\GoodsComeSourceType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('goods_comes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('referencable');
            $table->enum('source_type', GoodsComeSourceType::values());
            $table->foreignUuid('warehouse_id')->constrained();
            $table->foreignUuid('product_division_id')->nullable()->constrained();
            $table->foreignUuid('product_rack_id')->nullable()->constrained();
            $table->foreignUuid('product_id')->constrained();
            $table->bigInteger('quantity')->default(0);
            $table->bigInteger('quantity_return')->default(0);
            $table->bigInteger('unit_cost')->default(0);
            $table->text('notes')->nullable();
            $table->date('expired_date')->nullable();
            $table->bigInteger('previous_stock')->default(0);
            $table->bigInteger('stock_after')->default(0);
            $table->string('batch_numbers')->nullable();
            $table->string('barcode')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('vehicle_plate_number', 64)->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->foreignUuid('created_by_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('vehicle_plate_number', 'idx_goods_comes_vehicle_plate');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_comes');
    }
};
