<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'stock_transfer_id',
        'medicine_id',
        'quantity',
    ];

    /* ================= Relationships ================= */

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }
}
