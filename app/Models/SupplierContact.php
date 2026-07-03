<?php

namespace App\Models;

use Database\Factories\SupplierContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierContact extends Model
{
    /** @use HasFactory<SupplierContactFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'supplier_id',
        'name',
        'email',
        'phone',
        'role',
        'receives_orders',
        'receives_transport_requests',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'receives_orders' => 'boolean',
            'receives_transport_requests' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
