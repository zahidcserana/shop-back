<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Medicine;
use App\Models\Product;

class CartItem extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'serial_no' => 'array',
    ];

    public function addItem($data)
    {
        $medicine = new Medicine();
        $medicineData = $medicine->where('id', $data['medicine_id'])->first();
        if (empty($medicineData)) {
            return false;
        }
        $medicineInfo = Product::where('medicine_id', $data['medicine_id'])
            ->when(!empty($data['batch_no']), function ($query) use ($data) {
                $query->where('products.batch_no', $data['batch_no']);
            })
            ->first();

        $data['unit_type'] = $data['unit_type'] ?? 'PCS';

        $item = array(
            'medicine_id' => $medicineData->id,
            'product_type' => $medicineData->medicine_type_id,
            // 'company_id' => $medicineData->company_id,
            'quantity' => $data['quantity'],
            'free_quantity' => $data['free_quantity'] ?? 0,
            'batch_no' => empty($data['batch_no']) ? Medicine::$DEFAULT_BATCH: $data['batch_no'],
            'serial_no' => isset($data['serial_no']) ? json_encode($data['serial_no']): null,
            // 'batch_no' => $medicineInfo ? $medicineInfo->batch_no : null,
            // 'exp_date' => $medicineInfo? $medicineInfo->exp_date : null,
            'cart_id' => $data['cart_id'],
            'unit_type' => $data['unit_type'] ?? 'PCS',
            'unit_price' => $medicineInfo ? $medicineInfo->mrp : 0,
            'tp' => $medicineInfo ? $medicineInfo->tp : 0,
            'sub_total' => $medicineInfo ? $medicineInfo->mrp * $data['quantity'] : null,
        );

        $cartItem = CartItem::insertGetId($item);

        if ($cartItem) {
            return true;
        }
        return false;
    }

    public function _getMedicineUnitPrice($medicineInfo, $unit_type)
    {
        $unitPrice = 0;
        if ($unit_type == 'BOX') {
            $unitPrice = $medicineInfo->mrp;
        } else if ($unit_type == 'STRIP') {
            $unitPrice = $medicineInfo->mrp / $medicineInfo->strip_per_box;
        } else if ($unit_type == 'PCS') {
            $unitPrice = $medicineInfo->mrp / ($medicineInfo->strip_per_box * $medicineInfo->pieces_per_strip);
        }
        return $unitPrice;
    }

    public function updatePrice($data, $item) {
      $updateData = array(
          'unit_price' => $data['item_price'],
          'sub_total' => $item->quantity * $data['item_price'],
          'updated_at' => date('Y-m-d H:i:s'),
      );
      $this::where('id', $item->id)->update($updateData);
      return true;
    }

    public function updateQuantity($data)
    {
        $cartItem = $this::where('id', $data['id'])->first();
        if ($cartItem) {
            if ($data['increment'] == 1) {
                $updateData = array(
                    'quantity' => $cartItem->quantity + 1,
                    'sub_total' => ($cartItem->quantity + 1) * $cartItem->unit_price,
                    'updated_at' => date('Y-m-d H:i:s'),
                );
                $this::where('id', $cartItem->id)->update($updateData);
                return true;
            } else {
                $updateData = array(
                    'quantity' => $cartItem->quantity - 1,
                    'sub_total' => ($cartItem->quantity - 1) * $cartItem->unit_price,
                    'updated_at' => date('Y-m-d H:i:s'),
                );
                $this::where('id', $cartItem->id)->update($updateData);
                return true;
            }
        }
        return false;
    }

    public function medicine()
    {
        return $this->belongsTo('App\Models\Medicine');
    }

    public function deleteItem($data)
    {
        $cartModel = new Cart();
        $cart = $cartModel::where('token', $data['token'])->first();
        $this::where('id', $data['item_id'])->delete();

        $cartModel->updateCart($cart->id);
        $cartDetails = $cartModel->getCartDetails($cart->id);

        return ['success' => true, 'data' => $cartDetails];
    }

  public function company()
  {
      return $this->belongsTo('App\Models\MedicineCompany');
  }
}
