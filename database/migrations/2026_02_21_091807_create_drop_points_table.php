<?php

use App\Enums\DropPointCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('drop_points', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('photo')->nullable();
            $table->enum('category', array_column(DropPointCategory::cases(), 'value'))->default(DropPointCategory::OTHER->value);
            $table->string('address');
            $table->string('phone')->nullable();
            $table->float('latitude');
            $table->float('longitude');
            $table->string('pic_name')->nullable();
            $table->string('pic_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('delivery_fee')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drop_points');
    }
};
