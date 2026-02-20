<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{PurchaseOrderSupplierSource, PurchaseOrderStatus};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUuid('supplier_id')->nullable()->constrained();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->foreignUuid('created_by_id')->nullable()->constrained('users');
            $table->boolean('is_processed_by_finance')->default(false);
            $table->text('notes')->nullable();
            $table->date('order_date');
            $table->date('due_date')->nullable();
            $table->enum('status', PurchaseOrderStatus::values())->default(PurchaseOrderStatus::Draft->value);
            $table->foreignUuid('director_id')->nullable()->constrained('users');
            $table->timestamp('director_decision_at')->nullable();
            $table->timestamp('supplier_decision_at')->nullable();
            $table->text('rejected_manager_reason')->nullable();
            $table->text('rejected_director_reason')->nullable();
            $table->text('rejected_supplier_reason')->nullable();
            $table->foreignUuid('canceled_by_id')->nullable()->constrained('users');
            $table->timestamp('canceled_at')->nullable();
            $table->text('canceled_reason')->nullable();
            $table->date('payment_date')->nullable();
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('delivery_cost')->default(0);
            $table->bigInteger('total_before_discount')->default(0);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('total_after_discount')->default(0);
            $table->boolean('value_added_tax_included')->default(false);
            $table->boolean('is_value_added_tax_enabled')->default(false);
            $table->foreignUuid('value_added_tax_id')->nullable()->constrained();
            $table->decimal('value_added_tax_percentage', 5, 2)->nullable();
            $table->bigInteger('value_added_tax_amount')->default(0);
            $table->bigInteger('total_after_value_added_tax')->default(0);
            $table->boolean('is_income_tax_enabled')->default(false);
            $table->foreignUuid('income_tax_id')->nullable()->constrained();
            $table->decimal('income_tax_percentage', 5, 2)->nullable();
            $table->bigInteger('income_tax_amount')->default(0);
            $table->bigInteger('total_after_income_tax')->default(0);
            $table->bigInteger('grand_total')->default(0);
            $table->string('supplier_invoice_number')->nullable()->unique();
            $table->string('supplier_invoice_file')->nullable();
            $table->date('supplier_invoice_date')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
