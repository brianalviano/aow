<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\JournalStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('journals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->date('journal_date');
            $table->text('description')->nullable();
            $table->foreignUuid('warehouse_id')->nullable()->constrained();
            $table->uuidMorphs('source_referencable');
            $table->enum('status', JournalStatus::values())->default(JournalStatus::Draft->value);
            $table->bigInteger('total_debit')->default(0);
            $table->bigInteger('total_credit')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->foreignUuid('posted_by_id')->nullable()->constrained('users');
            $table->foreignUuid('canceled_by_id')->nullable()->constrained('users');
            $table->timestamp('canceled_at')->nullable();
            $table->text('canceled_reason')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
