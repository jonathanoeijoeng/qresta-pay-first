<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Table extends Model
{
    protected $fillable = ['branch_id', 'number', 'capacity', 'status', 'qr_token'];

    protected static function boot()
    {
        parent::boot();
        // Otomatis buat token unik saat meja di-input
        static::creating(function ($table) {
            $table->qr_token = Str::random(32);
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
