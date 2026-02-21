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

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUuid('drop_point_id')->constrained();
            $table->foreignUuid('customer_id')->constrained();
            $table->date('delivery_date');
            $table->foreignUuid('payment_method_id')->constrained();
            $table->enum('payment_status', ["pending", "paid", "failed"]);
            $table->enum('order_status', ["pending", "confirmed", "delivered", "cancelled"]);
            $table->text('note')->nullable();
            $table->string('snap_token')->nullable();
            $table->string('payment_url')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('payment_expired_at')->nullable();
            $table->foreignUuid('product_discount_id')->nullable()->constrained('discounts');
            $table->foreignUuid('shipping_discount_id')->nullable()->constrained('discounts');
            $table->integer('discount_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->integer('delivery_fee')->default(0);
            $table->integer('delivery_discount_amount')->default(0);
            $table->integer('final_delivery_fee')->default(0);
            $table->integer('admin_fee')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
