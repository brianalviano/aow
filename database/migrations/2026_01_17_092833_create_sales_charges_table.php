<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\SalesChargeType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('sales_charges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_id')->constrained('sales');
            $table->foreignUuid('sales_delivery_id')->nullable()->constrained();
            $table->enum('type', SalesChargeType::values());
            $table->string('description')->nullable();
            $table->bigInteger('amount')->default(0);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_charges');
    }
};
