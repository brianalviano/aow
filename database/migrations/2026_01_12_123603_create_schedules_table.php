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
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index();
            $table->foreignUuid('shift_id')->index();
            $table->date('date')->index();
            $table->boolean('is_manual')->default(false)->index();
            $table->timestamps();

            $table->foreign('user_id', 'schedules_user_id_foreign')
                ->references('id')->on('users')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreign('shift_id', 'schedules_shift_id_foreign')
                ->references('id')->on('shifts')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
