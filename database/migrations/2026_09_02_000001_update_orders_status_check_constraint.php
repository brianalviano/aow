<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old check constraint on PostgreSQL
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_order_status_check');

        // Recreate check constraint matching simplified OrderStatus enum
        $statuses = array_map(fn ($s) => "'{$s}'", OrderStatus::values());
        $statusList = implode(', ', $statuses);
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_order_status_check CHECK (order_status IN ({$statusList}))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_order_status_check');
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_order_status_check CHECK (order_status IN ('pending', 'confirmed', 'shipped', 'at_pickup_point', 'on_delivery', 'arrived', 'delivered', 'cancelled'))");
    }
};
