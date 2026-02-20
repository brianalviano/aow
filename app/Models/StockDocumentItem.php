<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockBucket;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item surat stok IN/OUT.
 *
 * @property string      $id
 * @property string      $stock_document_id
 * @property string      $product_id
 * @property string|null $product_division_id
 * @property string|null $product_rack_id
 * @property string|null $owner_user_id
 * @property int         $quantity
 * @property int         $unit_price
 * @property int         $subtotal
 * @property string|null $bucket
 * @property string|null $notes
 */
class StockDocumentItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'stock_document_id',
        'product_id',
        'product_division_id',
        'product_rack_id',
        'owner_user_id',
        'quantity',
        'unit_price',
        'subtotal',
        'bucket',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'unit_price' => 'integer',
            'subtotal'   => 'integer',
            'bucket'     => StockBucket::class,
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(StockDocument::class, 'stock_document_id');
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function productDivision(): BelongsTo
    {
        return $this->belongsTo(ProductDivision::class);
    }
    public function productRack(): BelongsTo
    {
        return $this->belongsTo(ProductRack::class);
    }
    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
