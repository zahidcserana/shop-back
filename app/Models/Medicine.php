<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  public function medicineType()
  {
    return $this->belongsTo('App\Models\MedicineType');
  }

  public function company()
  {
    return $this->belongsTo('App\Models\MedicineCompany');
  }

  public function brand()
  {
    return $this->belongsTo(Brand::class);
  }

  public function stockBalanceItems()
  {
    return $this->hasMany(StockBalanceItem::class, 'product_id');
  }
}
