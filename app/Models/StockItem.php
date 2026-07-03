<?php

namespace App\Models;

use Database\Factories\StockItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'available_quantity', 'incoming_quantity', 'reserved_quantity'])]
class StockItem extends Model
{
    /** @use HasFactory<StockItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'available_quantity' => 'integer',
            'incoming_quantity' => 'integer',
            'reserved_quantity' => 'integer',
        ];
    }
}
