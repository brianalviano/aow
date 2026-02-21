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

        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('drop_point_id')->constrained();
            $table->date('date');
            $table->integer('total_orders')->default(0);
            $table->integer('total_items')->default(0);
            $table->integer('total_revenue')->default(0);
            $table->integer('total_pending')->default(0);
            $table->integer('total_cancelled')->default(0);
            $table->integer('total_delivery_fee')->default(0);
            $table->integer('total_admin_fee')->default(0);
            $table->integer('total_discount')->default(0);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_summaries');
    }
};
