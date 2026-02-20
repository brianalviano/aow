<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->uuid('rotation_even_shift_id')->nullable();
            $table->uuid('rotation_odd_shift_id')->nullable();
            $table->unsignedTinyInteger('rotation_off_day')->nullable();
            $table->timestamps();

            $table->index(['rotation_even_shift_id', 'rotation_odd_shift_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_rules');
    }
};
