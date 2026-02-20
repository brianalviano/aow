<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{JournalTemplateLinePosition, JournalTemplateMappingKey, JournalAmountSource};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('journal_template_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('journal_template_id')->constrained();
            $table->enum('position', JournalTemplateLinePosition::values());
            $table->foreignUuid('account_id')->nullable()->constrained();
            $table->enum('mapping_key', JournalTemplateMappingKey::values());
            $table->enum('amount_source', JournalAmountSource::values());
            $table->string('custom_formula')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_template_lines');
    }
};
