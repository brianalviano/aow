<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{SalesDeliveryType, SalesStatus, SalesPaymentStatus};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->foreignUuid('cashier_session_id')->nullable();
            $table->string('receipt_number')->unique();
            $table->string('invoice_number')->unique();
            $table->dateTime('sale_datetime');
            $table->foreignUuid('customer_id')->nullable()->constrained();
            $table->enum('delivery_type', SalesDeliveryType::values())->default(SalesDeliveryType::WalkIn->value);
            $table->boolean('requires_delivery')->default(false);
            $table->bigInteger('subtotal')->default(0);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('item_discount_total')->default(0);
            $table->bigInteger('extra_discount_total')->default(0);
            $table->string('voucher_code', 20)->nullable();
            $table->bigInteger('voucher_amount')->default(0);
            $table->bigInteger('total_after_discount')->default(0);
            $table->boolean('is_value_added_tax_enabled')->default(false);
            $table->decimal('value_added_tax_percentage', 5, 2)->nullable();
            $table->bigInteger('value_added_tax_amount')->default(0);
            $table->foreignUuid('value_added_tax_id')->nullable()->constrained();
            $table->bigInteger('grand_total')->default(0);
            $table->bigInteger('outstanding_amount')->default(0);
            $table->bigInteger('change_amount')->default(0);
            $table->enum('status', SalesStatus::values())->default(SalesStatus::Draft->value);
            $table->foreignUuid('created_by_id')->nullable()->constrained('users');
            $table->foreignUuid('canceled_by_id')->nullable()->constrained('users');
            $table->timestamp('canceled_at')->nullable();
            $table->text('canceled_reason')->nullable();
            $table->boolean('canceled_via_pin')->default(false);
            $table->foreignUuid('cancel_approval_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->enum('payment_status', SalesPaymentStatus::values())->default(SalesPaymentStatus::Unpaid->value);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
