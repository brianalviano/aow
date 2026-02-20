<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{StockType, StockBucket};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->foreignUuid('product_id')->constrained();
            $table->foreignUuid('product_division_id')->nullable()->constrained();
            $table->foreignUuid('product_rack_id')->nullable()->constrained();
            $table->foreignUuid('owner_user_id')->nullable()->constrained('users');
            $table->bigInteger('quantity')->default(0);
            $table->enum('type', StockType::values())->default(StockType::Main->value);
            $table->foreignUuid('locked_by_stock_opname_id')->nullable()->constrained('stock_opnames');
            $table->enum('bucket', StockBucket::values())->nullable()->index();
            $table->index(['warehouse_id', 'product_id', 'bucket', 'owner_user_id'], 'stocks_owner_bucket_idx');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
