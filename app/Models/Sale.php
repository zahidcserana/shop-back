<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    protected $guarded = [];

    public function makeOrder($data)
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
        $order = $this::findOrFail($orderId);

        $orderItems = $order->items()->where('return_status', '<>', 'RETURN')->get();

        $data = array();
        $data['order_id'] = $order->id;
        $data['token'] = $order->token;
        $data['pharmacy_branch_id'] = $order->pharmacy_branch_id;
        $data['sub_total'] = $order->sub_total;
        $data['total_payble_amount'] = $order->total_payble_amount;
        $data['total_due_amount'] = $order->total_due_amount;
        $data['tax'] = $order->tax;
        $data['discount'] = $order->discount;
        $data['invoice'] = $order->invoice;
        $data['created_at'] = date("F j, Y h:i:s A", strtotime($order->created_at));
        $data['remarks'] = $order->remarks;
        $data['customer_name'] = $order->customer_name;
        $data['customer_mobile'] = $order->customer_mobile;
        $data['status'] = $order->status;

        $data['company'] = '';
        $data['mr_name'] = '';

        $createdBy = DB::table('users')->where('id', $order->created_by)->first();
        $data['created_by'] = $createdBy->name ?? '';
        $data['user_email'] = $createdBy->email ?? '';
        $data['salesman_mobile'] = $createdBy->user_mobile ?? '';

        $pharmacy = $order->pharmacy;
        $data['pharmacy'] = $pharmacy->pharmacy_shop_name;

        $pharmacyBranch = $order->PharmacyBranch;
        $data['pharmacy_address'] = $pharmacyBranch->branch_full_address;
        $data['branch_area'] = $pharmacyBranch->branch_area;
        $data['branch_city'] = $pharmacyBranch->branch_city;
        $data['branch_mobile'] = $pharmacyBranch->branch_mobile;

        $items = array();
        $totalProfit = 0;

        foreach ($orderItems as $item) {
            $aData = array();
            $aData['id'] = $item->id;
            $aData['medicine_id'] = $item->medicine_id;
            $aData['power'] = $item->power;
            $aData['quantity'] = $item->quantity;
            $aData['batch_no'] = $item->batch_no;
            $aData['sale_id'] = $item->sale_id;
            $aData['tax'] = $item->tax;
            $aData['dar_no'] = $item->dar_no;
            $aData['unit_price'] = $item->unit_price;
            $aData['sub_total'] = $item->sub_total;
            $aData['discount'] = $item->discount;
            $aData['unit_type'] = $item->unit_type;
            $aData['mrp'] = $item->mrp;
            $aData['exp_date'] = date("M, Y", strtotime($item->exp_date));

            $medicine = $item->medicine;
            $aData['medicine'] = $medicine->brand_name ?? '';
            $aData['medicine_power'] = $medicine->strength ?? '';
            $aData['brand'] = $medicine->brand->name ?? '';
            $aData['medicine_type'] = $medicine->medicineType->name ?? '';
            $aData['company'] = '';

            // Get TP & MRP from Product table
            $product = \App\Models\Product::where('medicine_id', $item->medicine_id)->first();
            $tp = $product->tp ?? 0;
            $mrp = $product->mrp ?? 0;

            // Profit = (MRP - TP) * quantity
            $profit = ($mrp - $tp) * $item->quantity;
            $aData['profit'] = round($profit, 2); // optional rounding

            $totalProfit += $profit;

            $items[] = $aData;
        }

        $data['order_items'] = $items;
        $data['total_profit'] = round($totalProfit, 2); // ✅ Final total profit

        return $data;
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
    /** **** **** **** **** **** **** */
}
