<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\StockOpnameItemStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_opname_id')->constrained();
            $table->foreignUuid('stock_opname_assignment_id')->constrained();
            $table->foreignUuid('product_id')->constrained();
            $table->bigInteger('system_quantity')->default(0);
            $table->bigInteger('actual_quantity')->default(0);
            $table->bigInteger('difference')->default(0);
            $table->bigInteger('hpp')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', StockOpnameItemStatus::values())->default(StockOpnameItemStatus::Pending->value);
            $table->dateTime('counted_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};
