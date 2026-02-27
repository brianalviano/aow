<?php

use App\Enums\{DiscountScope, DiscountType};
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

        Schema::create('discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->enum('type', DiscountType::values());
            $table->integer('value')->default(0);
            $table->integer('min_order')->default(0);
            $table->integer('max_discount')->nullable();
            $table->integer('quota')->nullable();
            $table->integer('used_count')->default(0);
            $table->enum('scope', DiscountScope::values());
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('discounts');
    }
};
