<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use SoftDeletes;

    protected $table = 'stock_transfers';

    const STATUS_PENDING  = 'PENDING';
    const STATUS_SENT     = 'SENT';
    const STATUS_RECEIVED = 'RECEIVED';
    const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'pharmacy_id',
        'warehouse_id',
        'pharmacy_branch_id',
        'reference_no',
        'status',
        'remarks',
        'created_by',
    ];

    /* ================= Relationships ================= */

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function branch()
    {
        return $this->belongsTo(PharmacyBranch::class, 'pharmacy_branch_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id');
    }
}
