<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\AttendanceStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index();
            $table->foreignUuid('schedule_id')->index();
            $table->dateTime('check_in_at')->nullable()->index();
            $table->dateTime('check_out_at')->nullable()->index();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_long', 10, 7)->nullable();
            $table->string('check_in_photo')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_long', 10, 7)->nullable();
            $table->string('check_out_photo')->nullable();
            $table->enum('status', AttendanceStatus::values())->default(AttendanceStatus::Absent->value)->index();
            $table->integer('late_duration')->default(0);
            $table->string('check_in_notes')->nullable();
            $table->string('check_out_notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'attendances_user_id_foreign')
                ->references('id')->on('users')
                ->cascadeOnUpdate()->cascadeOnDelete();

            $table->foreign('schedule_id', 'attendances_schedule_id_foreign')
                ->references('id')->on('schedules')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
