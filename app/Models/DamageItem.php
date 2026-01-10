<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DamageItem extends Model
{
  use SoftDeletes;

  protected $fillable = [
    'damage_id',
    'company_id',
    'medicine_id',
    'batch_no',
    'unit',
    'quantity',
    'price',
    'remarks'
  ];

  public function damage()
  {
    return $this->belongsTo(Damage::class);
  }
}
