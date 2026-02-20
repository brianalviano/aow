<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{PurchaseReturnReason, PurchaseReturnResolution, PurchaseReturnStatus};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_order_id')->constrained();
            $table->foreignUuid('supplier_id')->constrained();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->string('number')->unique();
            $table->date('return_date');
            $table->enum('reason', PurchaseReturnReason::values());
            $table->enum('resolution', PurchaseReturnResolution::values())->nullable();
            $table->enum('status', PurchaseReturnStatus::values())->default(PurchaseReturnStatus::Draft->value);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('resolved_by_id')->nullable()->constrained('users');
            $table->bigInteger('credit_amount')->default(0);
            $table->bigInteger('refund_amount')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_processed_by_finance')->default(false);
            $table->foreignUuid('created_by_id')->nullable()->constrained('users');
            $table->foreignUuid('canceled_by_id')->nullable()->constrained('users');
            $table->timestamp('canceled_at')->nullable();
            $table->text('canceled_reason')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
