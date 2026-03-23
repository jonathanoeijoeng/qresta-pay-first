<?php

namespace App\Models;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{

    protected $fillable = ['name', 'location', 'code', 'color'];
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class)
            ->withPivot('price', 'is_available')
            ->withTimestamps();
    }
}
