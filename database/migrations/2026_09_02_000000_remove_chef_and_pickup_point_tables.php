<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update any orders with obsolete statuses
        DB::table('orders')
            ->whereIn('order_status', ['shipped', 'at_pickup_point'])
            ->update(['order_status' => 'confirmed']);

        // 2. Drop foreign keys and columns in orders table
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'pick_up_point_id')) {
                    $table->dropForeign('orders_pick_up_point_id_foreign');
                    $table->dropColumn('pick_up_point_id');
                }
            });
        }

        // 3. Drop columns in order_items table
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('order_items', 'chef_id')) {
                    $columnsToDrop[] = 'chef_id';
                }
                if (Schema::hasColumn('order_items', 'chef_status')) {
                    $columnsToDrop[] = 'chef_status';
                }
                if (Schema::hasColumn('order_items', 'chef_confirmed_at')) {
                    $columnsToDrop[] = 'chef_confirmed_at';
                }
                if (! empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }

        // 4. Drop columns in order_shippings table
        if (Schema::hasTable('order_shippings')) {
            Schema::table('order_shippings', function (Blueprint $table) {
                if (Schema::hasColumn('order_shippings', 'chef_id')) {
                    $table->dropColumn('chef_id');
                }
            });
        }

        // 5. Drop tables in correct dependency order
        Schema::dropIfExists('pick_up_point_officers');
        Schema::dropIfExists('pick_up_points');
        Schema::dropIfExists('chef_transfers');
        Schema::dropIfExists('chef_product');
        Schema::dropIfExists('chefs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible structural removal
    }
};
