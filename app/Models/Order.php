<?php

namespace App\Models;

use App\Concerns\BelongsToBranch;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use BelongsToBranch;
    protected $fillable = [
        'order_number',
        'table_id',
        'status',
        'payment_status',
        'payment_method',
        'notes',
        'branch_id',
        'total_amount',
        'tax_amount',
        'tax_percentage',
        'confirmed_at',
        'paid_at',
        'payment_type',
    ];

    protected static function booted()
    {
        static::creating(function ($order) {
            // Format: QRS-YYYYMMDD-RANDOM
            $order->order_number = 'QPF-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        });
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
