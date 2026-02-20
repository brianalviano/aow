<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\CustomerSource;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->text('address')->nullable();
            $table->text('latitude')->nullable();
            $table->text('longitude')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('source', CustomerSource::values())
                ->default(CustomerSource::Marketing->value)
                ->index();
            $table->foreignUuid('created_by_id')
                ->nullable()
                ->constrained('users');
            $table->boolean('is_visible_in_pos')->default(true)->index();
            $table->boolean('is_visible_in_marketing')->default(true)->index();
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_marketers', function (Blueprint $table) {
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'user_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_marketers');
        Schema::dropIfExists('customers');
    }
};
