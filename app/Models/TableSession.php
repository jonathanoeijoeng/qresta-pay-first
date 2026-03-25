<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableSession extends Model
{

    protected $table = 'table_sessions';
    protected $fillable = [
        'table_id',
        'token',
        'status',
        'started_at',
        'ended_at',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    // Akses cabang lewat meja (Relationship Shortcut)
    public function branch()
    {
        return $this->hasOneThrough(Branch::class, Table::class, 'id', 'id', 'table_id', 'branch_id');
    }
}
