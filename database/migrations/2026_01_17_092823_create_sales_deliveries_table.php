<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SalesDeliveryStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('sales_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_id')->constrained('sales');
            $table->foreignUuid('warehouse_id')->constrained();
            $table->string('number')->unique();
            $table->date('delivery_date');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->enum('status', SalesDeliveryStatus::values())->default(SalesDeliveryStatus::Draft->value);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_deliveries');
    }
};
