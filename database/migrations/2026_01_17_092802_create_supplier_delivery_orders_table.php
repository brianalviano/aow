<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SupplierDeliveryOrderStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('supplier_delivery_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('sourceable');
            $table->foreignUuid('supplier_id')->constrained();
            $table->date('delivery_date');
            $table->string('number');
            $table->enum('status', SupplierDeliveryOrderStatus::values())->default(SupplierDeliveryOrderStatus::Draft->value);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_delivery_orders');
    }
};
