<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SupplierBillStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('supplier_bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained();
            $table->foreignUuid('purchase_order_id')->nullable()->constrained();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->string('number')->unique();
            $table->string('supplier_invoice_number')->nullable();
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->boolean('is_value_added_tax_enabled')->default(false);
            $table->foreignUuid('value_added_tax_id')->nullable()->constrained();
            $table->decimal('value_added_tax_percentage', 5, 2)->nullable();
            $table->bigInteger('value_added_tax_amount')->default(0);
            $table->bigInteger('grand_total')->default(0);
            $table->bigInteger('outstanding_amount')->default(0);
            $table->boolean('is_income_tax_enabled')->default(false);
            $table->foreignUuid('income_tax_id')->nullable()->constrained();
            $table->decimal('income_tax_percentage', 5, 2)->nullable();
            $table->bigInteger('income_tax_amount')->default(0);
            $table->bigInteger('net_payable_amount')->default(0);
            $table->enum('status', SupplierBillStatus::values())->default(SupplierBillStatus::Draft->value);
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
        Schema::dropIfExists('supplier_bills');
    }
};
