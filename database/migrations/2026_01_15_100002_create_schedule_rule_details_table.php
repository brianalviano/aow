<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_rule_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_rule_id')->index();
            $table->unsignedTinyInteger('day_of_week')->index();
            $table->foreignUuid('shift_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['schedule_rule_id', 'day_of_week'], 'schedule_rule_details_unique_day');

            $table->foreign('schedule_rule_id', 'rule_details_rule_id_foreign')
                ->references('id')->on('schedule_rules')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreign('shift_id', 'rule_details_shift_id_foreign')
                ->references('id')->on('shifts')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_rule_details');
    }
};

