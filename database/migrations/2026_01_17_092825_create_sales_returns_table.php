<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{SalesReturnReason, SalesReturnResolution, SalesReturnStatus};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('sales_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_id')->constrained('sales');
            $table->foreignUuid('warehouse_id')->constrained();
            $table->string('number')->unique();
            $table->dateTime('return_datetime');
            $table->enum('reason', SalesReturnReason::values());
            $table->enum('resolution', SalesReturnResolution::values())->nullable();
            $table->bigInteger('refund_amount')->default(0);
            $table->enum('status', SalesReturnStatus::values())->default(SalesReturnStatus::Draft->value);
            $table->foreignUuid('created_by_id')->nullable()->constrained('users');
            $table->foreignUuid('canceled_by_id')->nullable()->constrained('users');
            $table->timestamp('canceled_at')->nullable();
            $table->text('canceled_reason')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_stock_returned')->default(false);
            $table->foreignUuid('customer_store_credit_id')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
