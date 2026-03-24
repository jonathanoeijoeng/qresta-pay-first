<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'menu_id',
        'quantity',
        'price_at_order',
        'subtotal',
        'notes'
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menu()
    {
        // Menghubungkan item kembali ke master menu untuk ambil gambar/nama
        return $this->belongsTo(Menu::class);
    }
}
