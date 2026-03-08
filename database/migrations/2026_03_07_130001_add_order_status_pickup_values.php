<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add new OrderStatus enum values: 'at_pickup_point' and 'on_delivery'.
 *
 * PostgreSQL allows adding values to an existing enum type via ALTER TYPE.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TYPE order_status ADD VALUE IF NOT EXISTS 'at_pickup_point'");
        DB::statement("ALTER TYPE order_status ADD VALUE IF NOT EXISTS 'on_delivery'");
    }

    /**
     * Reverse the migrations.
     *
     * PostgreSQL does not support removing values from enum types easily.
     * This migration is not reversible without recreating the enum.
     */
    public function down(): void
    {
        // Cannot easily remove enum values in PostgreSQL
    }
};
