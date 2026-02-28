<?php

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
        Schema::create('testimonial_templates', function (Blueprint $blueprint) {
            $blueprint->uuid('id')->primary();
            $blueprint->string('customer_name');
            $blueprint->unsignedTinyInteger('rating');
            $blueprint->text('content');
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonial_templates');
    }
};
