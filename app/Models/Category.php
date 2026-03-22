<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['branch_id', 'name'];

    /**
     * Relasi ke Cabang (Satu kategori milik satu cabang)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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

    /**
     * Relasi ke Menu (Satu kategori punya banyak menu)
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }
}
