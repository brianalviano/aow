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

        Schema::create('goods_receipt_accruals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('goods_come_id')->constrained();
            $table->foreignUuid('supplier_id')->nullable()->constrained();
            $table->bigInteger('amount')->default(0);
            $table->string('period');
            $table->boolean('is_settled')->default(false);
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_accruals');
    }
};
