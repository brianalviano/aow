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

        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained();
            $table->foreignUuid('product_id')->constrained();
            $table->foreignUuid('discount_id')->nullable()->constrained();
            $table->integer('quantity')->default(0);
            $table->integer('price')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('final_price')->default(0);
            $table->integer('subtotal')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
