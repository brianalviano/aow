<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{ProductType, ProductVariantType};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('image')->nullable();
            $table->text('description');
            $table->string('sku', length: 13)->unique();
            $table->double('weight')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('product_category_id')->nullable()->constrained();
            $table->foreignUuid('product_sub_category_id')->nullable()->constrained();
            $table->foreignUuid('product_unit_id')->nullable()->constrained();
            $table->foreignUuid('product_factory_id')->nullable()->constrained();
            $table->foreignUuid('product_sub_factory_id')->nullable()->constrained();
            $table->foreignUuid('product_condition_id')->nullable()->constrained();
            $table->enum('product_type', ProductType::values())->nullable();
            $table->enum('product_variant_type', ProductVariantType::values())->nullable();
            $table->foreignUuid('parent_product_id')->nullable();
            $table->bigInteger('min_stock')->default(0);
            $table->bigInteger('max_stock')->default(0);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
