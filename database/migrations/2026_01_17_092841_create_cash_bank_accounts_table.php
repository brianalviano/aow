<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{CashBankAccountType, Currency};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('cash_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', CashBankAccountType::values());
            $table->string('name');
            $table->string('code')->unique();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->enum('currency', Currency::values())->default(Currency::Idr->value);
            $table->foreignUuid('account_id')->constrained();
            $table->bigInteger('opening_balance')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_bank_accounts');
    }
};
