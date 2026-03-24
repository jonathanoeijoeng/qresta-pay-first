<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Menu extends Model
{
    protected $fillable = ['category_id', 'name', 'description', 'price', 'is_available', 'image', 'slug', 'is_active'];

    // Accessor untuk format IDR dengan koma sebagai pemisah ribuan
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => 'IDR '.number_format($this->price, 0, '.', ','),
        );
    }

    // Di dalam class Menu extends Model
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name).'-'.Str::random(5);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class)
            ->withPivot('price', 'is_available')
            ->withTimestamps();
    }
}
