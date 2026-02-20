<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SupplierCreditNoteStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('supplier_credit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained();
            $table->foreignUuid('purchase_return_id')->nullable()->constrained();
            $table->string('number')->unique();
            $table->date('credit_date');
            $table->bigInteger('amount')->default(0);
            $table->bigInteger('remaining_amount')->default(0);
            $table->enum('status', SupplierCreditNoteStatus::values())->default(SupplierCreditNoteStatus::Draft->value);
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by_id')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->foreignUuid('posted_by_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_credit_notes');
    }
};
