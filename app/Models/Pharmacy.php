<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pharmacy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pharmacy_shop_code',
        'pharmacy_shop_name',
        'pharmacy_shop_owner_name',
        'pharmacy_shop_licence_no',
        'pharmacy_shop_branch_owner_nid',
        'pharmacy_shop_license_exp_date',
        'pharmacy_shop_dgda_verification_status'
    ];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class, 'pharmacy_id');
    }

    public function transfers()
    {
        return $this->hasMany(StockTransfer::class, 'pharmacy_id');
    }

}
