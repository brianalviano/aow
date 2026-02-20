<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{AccountMappingScope, AccountMappingKey};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('account_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('scope', AccountMappingScope::values())->default(AccountMappingScope::Global->value);
            $table->foreignUuid('warehouse_id')->nullable()->constrained();
            $table->enum('key', AccountMappingKey::values());
            $table->foreignUuid('account_id')->constrained();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['scope', 'warehouse_id', 'key']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
    }
};
