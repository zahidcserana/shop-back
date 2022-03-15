<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\This;
use App\Models\Sale;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    protected $guarded = [];
    use SoftDeletes;

    public function addItem($orderId, $cartId)
    {
        $cartItemModel = new CartItem();
        $cartItems = $cartItemModel->where('cart_id', $cartId)->get();
        foreach ($cartItems as $cartItem) {
            $itemInput = array(
                'medicine_id' => $cartItem->medicine_id,
                'company_id' => $cartItem->company_id,
                'quantity' => $cartItem->quantity,
                'sale_id' => $orderId,
                'exp_date' => $cartItem->exp_date,
                'mfg_date' => $cartItem->mfg_date,
                'batch_no' => $cartItem->batch_no,
                'dar_no' => $cartItem->dar_no,
                'unit_price' => $cartItem->unit_price,
                'unit_type' => $cartItem->unit_type,
                'sub_total' => $cartItem->sub_total,
                'tp' => $cartItem->tp,
                // 'total_payble_amount' => $cartItem->total_payble_amount,
                'discount' => $cartItem->discount ?? 0,
                'product_type' => $cartItem->product_type,
            );
            $this::create($itemInput);
            $this->updateInventoryQuantity($cartItem, $cartItem->quantity, 'sub');
        }
        return;
    }

    public function _getPieces($medicineInfo, $unit_type, $quantity)
    {
        $piece = 0;
        if ($unit_type == 'BOX') {
            $piece = $medicineInfo->strip_per_box * $medicineInfo->pieces_per_strip * $quantity;
        } else if ($unit_type == 'STRIP') {
            $piece = $medicineInfo->pieces_per_strip * $quantity;
        } else if ($unit_type == 'PCS') {
            $piece = $quantity;
        }
        return $piece;
    }

    public function deleteItem($data)
    {
        $orderModel = new Sale();
        $item = $this::find($data['item_id']);
        $item->return_status = 'RETURN';
        $orderId = $item->sale_id;
        $item->update();
        if ($this::where('sale_id', $orderId)->where('return_status', '<>', 'RETURN')->first()) {
            $this->updateInventoryQuantity($item, $item->quantity);
            $orderModel->updateOrder($orderId);
            $orderDetails = $orderModel->getOrderDetails($orderId);
        } else {
            $this->updateInventoryQuantity($item, $item->quantity);
            $order = new Sale();
            $order = $order::find($orderId);
            $order->delete();
            $orderDetails = [];
        }
        return ['success' => true, 'data' => $orderDetails];
    }
    public function updateInventoryQuantity($item, $quantity, $status = 'add') {
      $inventory = DB::table('products')->where('medicine_id', $item->medicine_id)->first();
      if($inventory) {
        $aQty = $status == 'add' ? $inventory->quantity + $quantity : $inventory->quantity - $quantity;
        $data = array(
          'quantity' => $aQty < 0 ? 0 : $aQty
        );

        DB::table('products')->where('id', $inventory->id)->update($data);
      }
      // else {
      //   $cart = DB::table('carts')->where('id', $item->cart_id)->first();
      //   $data = array(
      //     'medicine_id' => $item->medicine_id,
      //     'sale_quantity' => $quantity,
      //     'mrp' => $item->unit_price,
      //     'company_id' => $item->company_id,
      //     'pharmacy_branch_id' => $cart->pharmacy_branch_id,
      //     'pharmacy_id' => $cart->pharmacy_id,
      //   );
      //   DB::table('products')->insert($data);
      // }
    }

    public function manualOrderIem($orderId, $data)
    {
        $items = $data['items'];
        for ($i = 0; $i < count($items['medicines']); $i++) {
            if (!empty($items['medicines'][$i])) {
                $medicineStr = explode(' (', $items['medicines'][$i]);
                $medicine = new Medicine();

                $medicineData = $medicine->where('brand_name', 'like', trim($medicineStr[0]))->first();

                if (!empty($medicineData)) {
                    $itemInput = array(
                        'medicine_id' => $medicineData->id,
                        'company_id' => $data['company_id'],
                        'quantity' => $items['quantities'][$i],
                        'order_id' => $orderId,
                        //'exp_date' => $cartItem->exp_date,
                        // 'mfg_date' => $cartItem->mfg_date,
                        'batch_no' => $items['batches'][$i],
                        // 'dar_no' => $cartItem->dar_no,
                        //'unit_price' => $cartItem->unit_price,
                        // 'sub_total' => $cartItem->sub_total,
                        'total' => empty($items['totals'][$i]) ? 0 : $items['totals'][$i],
                        'mfg_date' => date("Y-m-d", strtotime($items['mfgs'][$i])),
                        'exp_date' => date("Y-m-d", strtotime($items['exps'][$i])),
                        // 'discount' => $cartItem->discount,
                    );
                    //var_dump($itemInput);exit;
                    $this::create($itemInput);
                }
            }
        }
        return true;
    }

    public function manualPurchaseItem($orderId, $data)
    {
        $items = $data['items'];

        foreach ($items as $item) {

            if (!empty($item['medicine'])) {
                $medicineStr = explode(' (', $item['medicine']);
                $medicine = new Medicine();

                $medicineData = $medicine->where('brand_name', 'like', trim($medicineStr[0]))->first();

                if (!empty($medicineData)) {
                    $itemInput = array(
                        'medicine_id' => $medicineData->id,
                        'company_id' => $data['company_id'],
                        'quantity' => $item['quantity'],
                        'order_id' => $orderId,
                        // 'batch_no' => $item['batch_no'],
                        // 'dar_no' => $cartItem->dar_no,
                        //'unit_price' => $cartItem->unit_price,
                        // 'sub_total' => $cartItem->sub_total,
                        //'total' => empty($item['total']) ? 0 : $item['total'],
                        //'mfg_date' => empty($item['mfg_date'])?null: date("Y-m-d", strtotime($item['mfg_date'])),
                        'exp_date' => !empty($item['exp_date']) ? date("Y-m-d", strtotime($item['exp_date'])) : null,
                        // 'discount' => $cartItem->discount,
                    );
                    // dd($itemInput);
                    $this::create($itemInput);
                }
            }
        }
        return true;
    }


    public function medicine()
    {
        return $this->belongsTo('App\Models\Medicine');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\MedicineCompany');
    }
}
