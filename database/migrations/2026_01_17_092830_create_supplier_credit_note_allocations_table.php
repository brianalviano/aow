<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('supplier_credit_note_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_credit_note_id')->constrained();
            $table->foreignUuid('supplier_bill_id')->constrained();
            $table->bigInteger('amount')->default(0);
            $table->timestamp('allocated_at')->nullable();
            $table->foreignUuid('allocated_by_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_credit_note_allocations');
    }
};
