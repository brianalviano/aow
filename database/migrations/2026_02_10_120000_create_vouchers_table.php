<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('value_type', ['percentage', 'amount']);
            $table->decimal('value', 15, 2)->default(0);
            $table->decimal('min_order_amount', 19, 0)->default(0);
            $table->integer('usage_limit')->default(1);
            $table->integer('used_count')->default(0);
            $table->integer('max_uses_per_customer')->default(1);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index(['start_at', 'end_at'], 'idx_vouchers_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
