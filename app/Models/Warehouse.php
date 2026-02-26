<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $table = 'warehouses';
    public static $DEFAULT_WAREHOUSE = 'Primary';

    protected $fillable = [
        'pharmacy_id',
        'name',
        'location',
    ];

    /* ================= Relationships ================= */

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id');
    }

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class, 'warehouse_id');
    }

    public function transfers()
    {
        return $this->hasMany(StockTransfer::class, 'warehouse_id');
    }
}
