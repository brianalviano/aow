<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{LeaveRequestStatus, LeaveRequestType};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->index();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->enum('type', LeaveRequestType::values());
            $table->text('reason');
            $table->enum('status', LeaveRequestStatus::values())->default(LeaveRequestStatus::Pending->value)->index();
            $table->uuid('approved_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
