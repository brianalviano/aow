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

        Schema::create('chef_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chef_id')->constrained()->cascadeOnDelete();
            $table->integer('amount')->default(0);
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->integer('fee_amount')->default(0);
            $table->integer('gross_amount')->default(0);
            $table->text('note')->nullable();
            $table->string('transfer_proof')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chef_transfers');
    }
};
