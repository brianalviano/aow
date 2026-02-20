<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{AverageCostTransactionType, StockBucket};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('average_cost_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('average_cost_id')->constrained();
            $table->bigInteger('cost')->default(0);
            $table->bigInteger('quantity_affected')->default(0);
            $table->enum('transaction_type', AverageCostTransactionType::values());
            $table->enum('bucket', StockBucket::values())->nullable()->index();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('average_cost_histories');
    }
};
