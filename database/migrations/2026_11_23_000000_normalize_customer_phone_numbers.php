<?php

use App\Traits\NormalizationTrait;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Normalize existing customer phone numbers to the local "08..." format.
     *
     * Phone numbers are unique, so a row is skipped when its normalized value
     * would collide with a phone number that is already stored (including
     * soft-deleted customers). Those cases need manual review.
     */
    public function up(): void
    {
        $normalizer = new class
        {
            use NormalizationTrait;
        };

        DB::table('customers')
            ->select(['id', 'phone'])
            ->orderBy('id')
            ->each(function (object $customer) use ($normalizer): void {
                $normalized = $normalizer->normalizePhone($customer->phone);

                if ($normalized === '' || $normalized === $customer->phone) {
                    return;
                }

                $alreadyUsed = DB::table('customers')
                    ->where('phone', $normalized)
                    ->where('id', '!=', $customer->id)
                    ->exists();

                if ($alreadyUsed) {
                    Log::warning('Skipped customer phone normalization due to duplicate', [
                        'customer_id' => $customer->id,
                        'current_phone' => $customer->phone,
                        'normalized_phone' => $normalized,
                    ]);

                    return;
                }

                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update([
                        'phone' => $normalized,
                        'updated_at' => now(),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     *
     * This is a one-way data normalization; the original formatting cannot be
     * restored reliably.
     */
    public function down(): void
    {
        //
    }
};
