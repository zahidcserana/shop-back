<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Damage extends Model
{
  use SoftDeletes;

  public const STATUS_RETURNED = 'returned';
  public const STATUS_PARTIAL_RETURNED = 'partial_returned';
  public const STATUS_CONFIRMED = 'confirmed';

  protected $fillable = [
    'invoice',
    'pharmacy_id',
    'pharmacy_branch_id',
    'company_id',
    'total_amount',
    'status',
    'remarks'
  ];

  public function damageItems()
  {
    return $this->hasMany(DamageItem::class);
  }

  public function company()
  {
    return $this->belongsTo(MedicineCompany::class);
  }
}
