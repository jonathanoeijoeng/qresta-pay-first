<?php

namespace App\Concerns; // Harus sesuai folder app/Concerns

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    /**
     * Booting Trait: Nama method harus boot + NamaTrait
     */
    public static function bootBelongsToBranch(): void
    {
        static::creating(function ($model) {
            // Isi branch_id otomatis jika user login punya branch
            if (Auth::check() && Auth::user()->branch_id && !$model->branch_id) {
                $model->branch_id = Auth::user()->branch_id;
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            // Filter otomatis: Hanya tampilkan data milik branch user
            if (Auth::check() && Auth::user()->branch_id) {
                // Gunakan nama tabel untuk menghindari "Column branch_id is ambiguous" saat join
                $builder->where($builder->getQuery()->from . '.branch_id', Auth::user()->branch_id);
            }
        });
    }
}
