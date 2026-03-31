<?

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch()
    {
        static::creating(function ($model) {
            // Otomatis isi branch_id saat create data baru jika user punya branch
            if (Auth::check() && Auth::user()->branch_id && !$model->branch_id) {
                $model->branch_id = Auth::user()->branch_id;
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            // Jika user login DAN punya branch_id, filter datanya
            // Jika branch_id user NULL (Super Admin), filter ini dilewati
            if (Auth::check() && Auth::user()->branch_id) {
                $builder->where('branch_id', Auth::user()->branch_id);
            }
        });
    }
}
