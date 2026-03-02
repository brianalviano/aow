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
        Schema::create('order_shippings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('chef_id')->nullable();
            $table->string('courier_company');
            $table->string('courier_type');
            $table->string('courier_name');
            $table->integer('shipping_fee')->default(0);
            $table->text('origin_address')->nullable();
            $table->decimal('origin_latitude', 10, 7)->nullable();
            $table->decimal('origin_longitude', 10, 7)->nullable();
            $table->decimal('destination_latitude', 10, 7)->nullable();
            $table->decimal('destination_longitude', 10, 7)->nullable();
            $table->string('biteship_order_id')->nullable();
            $table->string('biteship_tracking_id')->nullable();
            $table->string('biteship_waybill_id')->nullable();
            $table->string('biteship_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shippings');
    }
};
