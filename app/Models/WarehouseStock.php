<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    protected $table = 'warehouse_stocks';

    protected $fillable = [
        'warehouse_id',
        'medicine_id',
        'quantity',
    ];

    public $timestamps = true;

    /* ================= Relationships ================= */

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }
}
