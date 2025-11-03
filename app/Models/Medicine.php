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

  public function products()
  {
    return $this->hasMany(Product::class, 'medicine_id');
  }

  public function scopeExistsInBranch($query, $productName, $auth, $ignoreId = null)
  {
      return $query->whereRaw('LOWER(brand_name) = ?', [strtolower($productName)])
          ->when($ignoreId, function ($q) use ($ignoreId) {
              $q->where('id', '!=', $ignoreId); // ✅ ignore current record
          })
          ->whereHas('products', function ($query) use ($auth) {
              $query->where(function ($sub) use ($auth) {
                  $sub->where('pharmacy_branch_id', $auth->pharmacy_branch_id)
                      ->orWhere('pharmacy_id', $auth->pharmacy_id);
              });
          });
  }
}
