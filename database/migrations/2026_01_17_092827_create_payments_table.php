<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{PaymentDirection, PaymentStatus};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('direction', PaymentDirection::values());
            $table->uuidMorphs('party');
            $table->date('payment_date');
            $table->foreignUuid('payment_method_id')->nullable();
            $table->bigInteger('amount')->default(0);
            $table->string('reference_number')->nullable();
            $table->foreignUuid('cash_bank_account_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by_id')->nullable()->constrained('users');
            $table->enum('status', PaymentStatus::values())->default(PaymentStatus::Draft->value);
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignUuid('voided_by_id')->nullable()->constrained('users');
            $table->text('void_reason')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
