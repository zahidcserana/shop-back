<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\This;
use App\Models\Sale;
use App\Models\Product;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    protected $guarded = [];
    use SoftDeletes;

    protected $casts = [
        'exp_date' => 'date',
        'serial_no' => 'array',
    ];

    public function addItem($orderId, $cartId)
    {
        $cartItemModel = new CartItem();
        $cartItems = $cartItemModel->where('cart_id', $cartId)->get();
        foreach ($cartItems as $cartItem) {
            $itemInput = array(
                'medicine_id' => $cartItem->medicine_id,
                'company_id' => $cartItem->company_id,
                'quantity' => $cartItem->quantity,
                'free_quantity' => $cartItem->free_quantity,
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
            $this->updateInventoryQuantity($cartItem, $cartItem->free_quantity, 'sub');
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
        $item->updated_by = $data['updated_by'];
        $item->updated_at = $data['updated_at'];
        $orderId = $item->sale_id;
        $item->update();
        if ($this::where('sale_id', $orderId)->where('return_status', '<>', 'RETURN')->first()) {
            $this->returnUpdateInventoryQuantity($item, $item->quantity);
            $orderModel->updateOrder($orderId);
            $orderDetails = $orderModel->getOrderDetails($orderId);
        } else {
            $this->returnUpdateInventoryQuantity($item, $item->quantity);
            $order = new Sale();
            $order = $order::find($orderId);
            $order->delete();
            $orderDetails = [];
        }
        return ['success' => true, 'data' => $orderDetails];
    }

    public function returnUpdateInventoryQuantity($item, $quantity, $status = 'add') 
    {
        $sale = Sale::find($item->sale_id);
        $inventory = Product::where('medicine_id', $item->medicine_id)->where('pharmacy_branch_id', $sale->pharmacy_branch_id)->first();
        
        if($inventory) {
            $aQty = $status == 'add' ? $inventory->quantity + $quantity : $inventory->quantity - $quantity;

            $inventory->quantity = $aQty < 0 ? 0 : $aQty;
            $inventory->save();
      }
    }

    public function updateInventoryQuantity($item, $quantity, $status = 'add') 
    {
        $cart = Cart::find($item->cart_id);
        $inventory = Product::where('medicine_id', $item->medicine_id)->where('pharmacy_branch_id', $cart->pharmacy_branch_id)->first();
        
        if($inventory) {
            $aQty = $status == 'add' ? $inventory->quantity + $quantity : $inventory->quantity - $quantity;

            $inventory->quantity = $aQty < 0 ? 0 : $aQty;
            $inventory->save();
      }
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
                        'free_quantity' => $item['free_quantity'] ?? 0,
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

    public function product()
    {
        return $this->belongsTo(Product::class, 'medicine_id', 'medicine_id');
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
