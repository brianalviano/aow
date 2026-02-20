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

        Schema::create('warehouse_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_id')->constrained();
            $table->foreignUuid('inventory_account_id')->constrained('accounts');
            $table->foreignUuid('cogs_account_id')->constrained('accounts');
            $table->foreignUuid('adjustment_increase_account_id')->constrained('accounts');
            $table->foreignUuid('adjustment_decrease_account_id')->constrained('accounts');
            $table->foreignUuid('account_id')->constrained('accounts');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_accounts');
    }
};
