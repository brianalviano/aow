<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_selling_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('selling_price_level_id')->nullable();
            $table->bigInteger('price')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('selling_price_level_id')->references('id')->on('selling_price_levels');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_selling_prices');
    }
};
