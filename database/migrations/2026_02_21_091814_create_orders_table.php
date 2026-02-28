<?php

use App\Enums\{OrderStatus, PaymentStatus, ShippingMethod};
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
            $table->foreignUuid('drop_point_id')->nullable()->constrained('drop_points');
            $table->foreignUuid('customer_address_id')->nullable();
            $table->foreignUuid('customer_id')->constrained();
            $table->date('delivery_date');
            $table->foreignUuid('payment_method_id')->constrained();
            $table->string('barcode')->unique()->nullable();
            $table->string('tracking_number')->unique()->nullable();
            $table->enum('shipping_method', ShippingMethod::values())->default(ShippingMethod::FREE->value);
            $table->enum('payment_status', PaymentStatus::values());
            $table->enum('order_status', OrderStatus::values());
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
            $table->json('payment_details')->nullable();
            $table->integer('tax_amount')->default(0);
            $table->time('delivery_time')->nullable();
            $table->string('payment_proof')->nullable();
            $table->text('cancellation_note')->nullable();
            $table->string('delivery_photo')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->integer('service_fee')->default(0);
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
