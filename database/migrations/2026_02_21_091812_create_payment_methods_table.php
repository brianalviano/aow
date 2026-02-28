<?php

use App\Enums\{PaymentMethodCategory, PaymentMethodType};
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

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('category', PaymentMethodCategory::values())->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->foreignUuid('payment_guide_id')->nullable();
            $table->enum('type', PaymentMethodType::values())->default(PaymentMethodType::MANUAL->value);
            $table->decimal('service_fee_rate', 5, 2)->default(0);
            $table->integer('service_fee_fixed')->default(0);
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
        Schema::dropIfExists('payment_methods');
    }
};
