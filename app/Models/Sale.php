<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    public const STATUS_COMPLETE = 'COMPLETE';
    public const STATUS_DUE = 'DUE';

    public const RETURN_STATUS_CHANGE = 'CHANGE';
    public const RETURN_STATUS_FULL_RETURN = 'RETURN';

    protected $guarded = [];

    public function makeOrder($data)
    {
        try {
            return DB::transaction(function () use ($data) {

                $cart = Cart::where('token', $data['token'])->first();
                if (!$cart) {
                    throw new \Exception('Invalid cart token');
                }

                $data['discount'] = $data['discount'] ?? 0;

                $saleInput = [
                    'customer_name' => $data['customer_name'] ?? '',
                    'customer_mobile' => $data['customer_mobile'] ?? '',
                    'pharmacy_id' => $cart->pharmacy_id,
                    'pharmacy_branch_id' => $cart->pharmacy_branch_id,
                    'created_by' => $data['created_by'] ?: $cart->created_by,
                    'quantity' => $cart->quantity,
                    'payment_type' => $data['payment_type'],
                    'sub_total' => $data['sub_total'],
                    'vat_amount' => $cart->tax,
                    'discount' => $data['discount'],
                    'is_sync' => $data['is_delivery_order'] ?? 0,
                    'total_advance_amount' => $data['total_advance_amount'],
                    'total_due_amount' => $data['total_due_amount'],
                    'total_payble_amount' => $data['sub_total'] - $data['discount'],
                    'remarks' => $cart->remarks,
                    'sale_date' => Carbon::now()->toDateString(),
                    'created_at' => Carbon::now(),
                    'status' => $data['total_due_amount'] > 0 ? 'DUE' : 'COMPLETE',
                ];

                $saleId = self::insertGetId($saleInput);
                $invoice = 'INV-' . Carbon::now()->timestamp . $saleId;

                self::where('id', $saleId)->update(['invoice' => $invoice]);

                // Fetch items from cart
                $cartItems = CartItem::where('cart_id', $cart->id)->get();

                if ($cartItems->isEmpty()) {
                    throw new \Exception('Cart items not found.');
                }

                $saleItems = [];
                foreach ($cartItems as $cartItem) {
                    $saleItems[] = [
                        'medicine_id' => $cartItem->medicine_id,
                        'company_id' => $cartItem->company_id,
                        'quantity' => $cartItem->quantity,
                        'free_quantity' => $cartItem->free_quantity,
                        'sale_id' => $saleId,
                        'exp_date' => $cartItem->exp_date,
                        'mfg_date' => $cartItem->mfg_date,
                        'batch_no' => $cartItem->batch_no,
                        'serial_no' => !empty($cartItem->serial_no) ? json_encode($cartItem->serial_no): null,
                        'dar_no' => $cartItem->dar_no,
                        'unit_price' => $cartItem->unit_price,
                        'unit_type' => $cartItem->unit_type,
                        'sub_total' => $cartItem->sub_total,
                        'tp' => $cartItem->tp,
                        'discount' => $cartItem->discount ?? 0,
                        'product_type' => $cartItem->product_type,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];

                    // Update inventory
                    $this->updateInventoryQuantity($cartItem, $cartItem->quantity + $cartItem->free_quantity, 'sub');
                }

                // Bulk insert for efficiency
                DB::table('sale_items')->insert($saleItems);

                // Clean up cart
                CartItem::where('cart_id', $cart->id)->delete();
                $cart->delete();

                return [
                    'success' => true,
                    'message' => 'Sale created successfully.',
                    'data' => $this->getOrderDetails($saleId),
                ];
            });

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Sale creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Sale creation failed.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function updateInventoryQuantity($item, $quantity, $status = 'add') 
    {
        $cart = Cart::find($item->cart_id);

        $inventory = Product::where('medicine_id', $item->medicine_id)
                    ->where('pharmacy_branch_id', $cart->pharmacy_branch_id)
                    ->where('batch_no', $item->batch_no)
                    ->first();
        
        if($inventory) {
            $aQty = $status == 'add' ? $inventory->quantity + $quantity : $inventory->quantity - $quantity;

            $inventory->quantity = $aQty < 0 ? 0 : $aQty;
            $inventory->save();
      }
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function makeOrderOld($data)
    {
        $cartModel = new Cart();
        $cartData = $cartModel->where('token', $data['token'])->first();
        if (empty($cartData)) {
            return ['success' => false, 'error' => 'Something went wrong!'];
        }
        $data['discount'] = empty($data['discount']) ? 0 : $data['discount'];
        $input = array(
            'customer_name' => $data['customer_name'] ?? '',
            'customer_mobile' => $data['customer_mobile'] ?? '',
            'pharmacy_id' => $cartData->pharmacy_id,
            'created_by' => $data['created_by'] == 0 ? $cartData->created_by : $data['created_by'],
            // 'file' => $cartData->file??'',
            // 'file_name' => $cartData->file_name??'',
            'pharmacy_branch_id' => $cartData->pharmacy_branch_id,
            'quantity' => $cartData->quantity,
            'payment_type' => $data['payment_type'],
            'sub_total' => $data['sub_total'],
            'vat_amount' => $cartData->tax,
            'discount' => $data['discount'],
            'total_advance_amount' => $data['total_advance_amount'],
            'total_due_amount' => $data['total_due_amount'],
            'total_payble_amount' => $data['sub_total'] - $data['discount'],
            'remarks' => $cartData->remarks,
            'sale_date' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
            'status' => $data['total_due_amount'] > 0 ? 'DUE' : 'COMPLETE'
        );

        $orderId = $this::insertGetId($input);


        $this->_createOrderInvoice($orderId, $cartData->pharmacy_branch_id);

        $orderItemModel = new SaleItem();
        $orderItemModel->addItem($orderId, $cartData->id);

        $cartItemModel = new CartItem();
        $cartItemModel->where('cart_id', $cartData->id)->delete();
        $cartModel->where('token', $data['token'])->delete();

        return ['success' => true, 'message' => 'Data successfully submitted.', 'data' => $this->getOrderDetails($orderId)];
    }

    private function _createOrderInvoice($orderId, $pharmacy_branch_id)
    {
        // $pharmacyBranchModel = new PharmacyBranch();
        // $pharmacyBranch = $pharmacyBranchModel->where('id', $pharmacy_branch_id)->first();
        // $invoice = $orderId . substr($pharmacyBranch->branch_mobile??'0000', -4) . Carbon::now()->timestamp;
        $invoice = 'INV-' . Carbon::now()->timestamp . $orderId;

        $this->where('id', $orderId)->update(['invoice' => $invoice]);
    }

    public function getAllOrder($where, $offset, $limit)
    {
        $query = $this::where($where);

        $total = $query->count();
        $orders = $query
            ->orderBy('orders.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        foreach ($orders as $order) {
            $items = $order->items()->get();
            $orderData = array();

            foreach ($items as $item) {
                $aData = array();
                $aData['id'] = $item->id;
                $aData['order_id'] = $item->order_id;

                $company = MedicineCompany::findOrFail($item->company_id);
                $aData['company'] = ['id' => $company->id, 'name' => $company->company_name];

                $aData['company_invoice'] = $order->company_invoice;
                $aData['is_sync'] = $order->is_sync;

                $medicine = Medicine::findOrFail($item->medicine_id);
                $aData['medicine'] = ['id' => $medicine->id, 'brand_name' => $medicine->brand_name];

                $aData['purchase_date'] = date("F, Y", strtotime($order->purchase_date));
                $aData['exp_date'] = date("F, Y", strtotime($item->exp_date));
                $aData['exp_status'] = $this->_getExpStatus($item->exp_date);
                $aData['mfg_date'] = date("F, Y", strtotime($item->mfg_date));

                $aData['batch_no'] = $item->batch_no;
                $aData['quantity'] = $item->quantity;
                $aData['status'] = $item->status;

                $orderData[] = $aData;

            }
            $order->items = $orderData;

            $company = $order->company()->first();
            $order->company = $company['company_name'];
        }

        $data['success'] = true;
        $data['total'] = $total;
        $data['data'] = $orders;
        return $data;
    }

    private function _getExpStatus($date)
    {
        $expDate = date("F, Y", strtotime($date));

        $today = date('Y-m-d');
        $exp1M = date('Y-m-d', strtotime("+1 months", strtotime(date('Y-m-d'))));
        $exp3M = date('Y-m-d', strtotime("+3 months", strtotime(date('Y-m-d'))));
        if ($date < $today) {
            return 'EXP';
        } else if ($date >= $today && $date <= $exp1M) {
            return '1M';
        } else if ($date > $exp1M && $date <= $exp3M) {
            return '3M';
        } else {
            return 'OK';
        }
    }

    public function getOrderDetails($orderId)
    {
        try {

            $order = $this->with([
                'creator',
                'pharmacy',
                'PharmacyBranch',
                'items' => function ($q) {
                    $q->where('return_status', '<>', 'RETURN')
                    ->with(['medicine.brand', 'medicine.medicineType']);
                }
            ])->findOrFail($orderId);
            
            $orderItems = $order->items;        

            $productMap = Product::whereIn(
                'medicine_id',
                $orderItems->pluck('medicine_id')
            )->get()->keyBy('medicine_id');        

            $customer = Customer::where('pharmacy_branch_id', $order->pharmacy_branch_id)
                    ->where('mobile', $order->customer_mobile)
                    ->first();

            $data = [];
            $data['order_id'] = $order->id;
            $data['is_sync'] = $order->is_sync;
            $data['token'] = $order->token;
            $data['pharmacy_branch_id'] = $order->pharmacy_branch_id;
            $data['sub_total'] = $order->sub_total;
            $data['total_payble_amount'] = $order->total_payble_amount;
            $data['total_due_amount'] = $order->total_due_amount;
            $data['tax'] = $order->tax;
            $data['discount'] = $order->discount;
            $data['invoice'] = $order->invoice;
            $data['created_at'] = $order->created_at->format('d/m/Y');
            // $data['created_at'] = date("F j, Y h:i:s A", strtotime($order->created_at));
            $data['remarks'] = $order->remarks;
            $data['customer_name'] = $order->customer_name;
            $data['customer_mobile'] = $order->customer_mobile;
            $data['customer_balance'] = $customer->balance ?? '';
            $data['status'] = $order->status;

            $data['company'] = '';
            $data['mr_name'] = '';

            $data['created_by'] = $order->creator->name ?? '';
            $data['user_email'] = $order->creator->email ?? '';
            $data['salesman_mobile'] = $order->creator->user_mobile ?? '';

            $pharmacy = $order->pharmacy;
            $data['pharmacy'] = $pharmacy->pharmacy_shop_name;

            $pharmacyBranch = $order->PharmacyBranch;
            $data['pharmacy_address'] = $pharmacyBranch->branch_full_address;
            $data['branch_area'] = $pharmacyBranch->branch_area;
            $data['branch_city'] = $pharmacyBranch->branch_city;
            $data['branch_name'] = $pharmacyBranch->branch_name;
            $data['branch_mobile'] = $pharmacyBranch->branch_mobile;
            $data['branch_contact_person_mobile'] = $pharmacyBranch->branch_contact_person_mobile;

            $items = [];
            $totalProfit = 0;

            foreach ($orderItems as $item) {
                $aData = [];
                $aData['id'] = $item->id;
                $aData['medicine_id'] = $item->medicine_id;
                $aData['power'] = $item->power;
                $aData['quantity'] = $item->quantity;
                $aData['free_quantity'] = $item->free_quantity;
                $aData['batch_no'] = $item->batch_no;
                $aData['sale_id'] = $item->sale_id;
                $aData['tax'] = $item->tax;
                $aData['dar_no'] = $item->dar_no;
                $aData['unit_price'] = $item->unit_price;
                $aData['sub_total'] = $item->sub_total;
                $aData['discount'] = $item->discount;
                $aData['unit_type'] = $item->unit_type;
                $aData['mrp'] = $item->mrp;
                $aData['exp_date'] = $item->exp_date?->format('M, Y') ?? '';

                $medicine = $item->medicine;
                $aData['medicine'] = $medicine->brand_name ?? '';
                $aData['medicine_power'] = $medicine->strength ?? '';
                $aData['brand'] = $medicine->brand->name ?? '';
                $aData['medicine_type'] = $medicine->medicineType->name ?? '';
                $aData['company'] = '';

                // Get TP & MRP from Product table
                $product = $productMap[$item->medicine_id] ?? null;
                $tp = $product->tp ?? 0;
                $mrp = $product->mrp ?? 0;
                $aData['tp'] = $tp;
                // Profit = (MRP - TP) * quantity
                $profit = ($mrp - $tp) * $item->quantity;
                $aData['profit'] = round($profit, 2); // optional rounding
                $totalProfit += round($profit, 2);

                $items[] = $aData;
            }

            $totalQty = $orderItems->sum('quantity');
            $totalFreeQty = $orderItems->sum('free_quantity');


            $data['order_items'] = $items;
            $data['total_qty'] = $totalQty;
            $data['total_free_qty'] = $totalFreeQty;
            $data['total_profit'] = $totalProfit - $order->discount;

            return $data;
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Sale failed.',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /** Manual Order */

    public function makeManualOrder($data, $user)
    {
        $medicineCompany = new MedicineCompany();
        $companyData = $medicineCompany->where('company_name', 'like', $data['company'])->first();
        $data['company_id'] = $companyData->id;
        $order = $this::where('company_invoice', $data['company_invoice'])
            ->where('pharmacy_branch_id', $user->pharmacy_branch_id)
            ->where('company_id', $data['company_id'])
            ->first();

        if ($order) {
            $orderId = $order->id;
        } else {
            $input = array(
                'pharmacy_id' => $user->pharmacy_id,
                'company_id' => $data['company_id'],
                'pharmacy_branch_id' => $user->pharmacy_branch_id,
                'created_by' => $user->id,
                'is_manual' => true,
                'purchase_date' => empty($data['purchase_date']) ? date('Y-m-d') : $data['purchase_date'],
                'company_invoice' => $data['company_invoice'],
                'discount' => empty($data['discount']) ? 0 : $data['discount'],
            );

            $orderId = $this::insertGetId($input);
        }

        $this->_createOrderInvoice($orderId, $user->pharmacy_branch_id);

        $orderItemModel = new OrderItem();
        if ($orderItemModel->manualOrderIem($orderId, $data)) {
            $this->updateOrder($orderId);

            return ['success' => true, 'data' => $this->getOrderDetails($orderId)];
        }
        return ['success' => false, 'error' => 'Something went wrong!'];
    }

    /** Manual Order */

    public function makeManualPurchase($data, $user)
    {
        $medicineCompany = new MedicineCompany();
        $companyData = $medicineCompany->where('company_name', 'like', $data['company'])->first();
        if (empty($companyData)) {
            return ['success' => false, 'error' => 'Invalid company!', 'message' => 'Invalid company!'];
        }
        $data['company_id'] = $companyData->id;
        $order = $this::where('company_invoice', $data['company_invoice'])
            ->where('pharmacy_branch_id', $user->pharmacy_branch_id)
            ->where('company_id', $data['company_id'])
            ->first();

        if ($order) {
            $orderId = $order->id;
        } else {
            $input = array(
                'pharmacy_id' => $user->pharmacy_id,
                'company_id' => $data['company_id'],
                'pharmacy_branch_id' => $user->pharmacy_branch_id,
                'created_by' => $user->id,
                'is_manual' => true,
                'purchase_date' => empty($data['purchase_date']) ? date('Y-m-d') : $data['purchase_date'],
                'company_invoice' => $data['company_invoice'],
                'mr_id' => $data['mr_id'] ?? 0,
                'discount' => empty($data['discount']) ? 0 : $data['discount'],
                'created_at' => date('Y-m-d H:i:s'),
            );

            $orderId = $this::insertGetId($input);
        }

        $this->_createOrderInvoice($orderId, $user->pharmacy_branch_id);

        $orderItemModel = new OrderItem();
        if ($orderItemModel->manualPurchaseItem($orderId, $data)) {
            $this->updateOrder($orderId);

            return ['success' => true, 'message' => 'Data successfully submitted.', 'data' => $this->getOrderDetails($orderId)];
        }
        return ['success' => false, 'error' => 'Something went wrong!'];
    }


    public function updateOrder($orderId)
    {
        $orderItem = new SaleItem();
        $orderItem = $orderItem
            ->select(DB::raw('
            SUM(sub_total) as total_sub_total,
            SUM(total_payble_amount) as total_amount
            '))
            ->where('sale_id', $orderId)
            ->where('return_status', '<>', 'RETURN')
            ->first();
        $order = $this::findOrFail($orderId);
        $data = array(
            'sub_total' => $orderItem->total_sub_total,
            'total_payble_amount' => $orderItem->total_sub_total - $order->discount,
        );
        $order->update($data);
        return true;
    }

    /** ************* */

    /** Relationship */
    public function items()
    {
        return $this->hasMany('App\Models\SaleItem');
    }

    public function PharmacyBranch()
    {
        return $this->belongsTo('App\Models\PharmacyBranch');
    }

    public function pharmacy()
    {
        return $this->belongsTo('App\Models\Pharmacy');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\MedicineCompany');
    }

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->timezone('Asia/Dhaka');
    }
    /** **** **** **** **** **** **** */
}
