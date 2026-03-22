<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\Category;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['category_id', 'name', 'description', 'price', 'is_available', 'image'];

    // Accessor untuk format IDR dengan koma sebagai pemisah ribuan
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn($value) => 'IDR ' . number_format($this->price, 0, '.', ','),
        );
    }

    // Di dalam class Menu extends Model
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = \Illuminate\Support\Str::slug($model->name) . '-' . \Illuminate\Support\Str::random(5);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
