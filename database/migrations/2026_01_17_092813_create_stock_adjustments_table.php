<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{StockAdjustmentReason, StockAdjustmentStatus};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->enum('reason', StockAdjustmentReason::values());
            $table->enum('status', StockAdjustmentStatus::values());
            $table->foreignUuid('approved_by_id')->nullable()->constrained('users');
            $table->boolean('approved_via_pin')->default(false);
            $table->foreignUuid('supervisor_approval_id')->nullable()->constrained('users');
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
        Schema::dropIfExists('stock_adjustments');
    }
};
