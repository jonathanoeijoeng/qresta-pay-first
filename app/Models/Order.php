<?php

namespace App\Models;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected static function booted()
    {
        static::creating(function ($order) {
            // Format: QRS-YYYYMMDD-RANDOM
            $order->order_number = 'QRS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
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
}
