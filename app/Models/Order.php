<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany('App\Models\OrderItem');
    }

    public function orderItems()
    {
        return $this->hasMany('App\Models\OrderItem');
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

    public function makeOrder($data)
    {
        $cartModel = new Cart();
        $cartData = $cartModel->where('token', $data['token'])->first();
        if (empty($cartData)) {
            return ['success' => false, 'error' => 'Something went wrong!'];
        }
        $input = array(
            'pharmacy_id' => $cartData->pharmacy_id,
            'created_by' => $cartData->created_by,
            'pharmacy_branch_id' => $cartData->pharmacy_branch_id,
            'quantity' => $cartData->quantity,
            'sub_total' => $cartData->sub_total,
            'tax' => $cartData->tax,
            'discount' => $cartData->discount,
            'remarks' => $cartData->remarks,
        );

        $orderId = $this::insertGetId($input);

        $this->_createOrderInvoice($orderId, $cartData->pharmacy_branch_id);

        $orderItemModel = new OrderItem();
        $orderItemModel->addItem($orderId, $cartData->id);
        return ['success' => true];
    }

    public function _createOrderInvoice($orderId, $pharmacy_branch_id)
    {
        $pharmacyBranchModel = new PharmacyBranch();
        $pharmacyBranch = $pharmacyBranchModel->where('id', $pharmacy_branch_id)->first();
        $invoice = $orderId . substr($pharmacyBranch->branch_mobile, -4) . Carbon::now()->timestamp;
        $this->where('id', $orderId)->update(['invoice' => $invoice]);
        return;
    }

    public function getAllItem($where, $offset, $limit)
    {
        $query = $this::where($where);
        $total = $query->count();
        $orders = $query
            ->orderBy('orders.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();
        $allData = array();
        foreach ($orders as $order) {
            $items = $order->items()->get();
            $orderData = array();
            $orderData['purchase_date'] = date("Y-m-d H:i:s", strtotime($order->created_at));
            $orderData['invoice'] = $order->invoice;
            $orderData['token'] = $order->token;
            $orderData['status'] = $order->status;
            $orderData['order_id'] = $order->id;
            $orderData['amount'] = $order->total_payble_amount;
            $company = $order->company()->first();
            $orderData['company'] = ['id' => $company['id'], 'name' => $company['company_name']];
            $pharmacy = $order->PharmacyBranch()->first();
            $orderData['pharmacy'] = ['name' => $pharmacy->branch_name, 'mobile' => $pharmacy->branch_contact_person_mobile];

            $itemList = array();
            foreach ($items as $item) {
                $aData = array();
                $aData['id'] = $item->id;
                $medicine = Medicine::findOrFail($item->medicine_id);
                $aData['medicine'] = ['id' => $medicine->id, 'brand_name' => $medicine->brand_name];
                $aData['quantity'] = $item->quantity;
                $itemList[] = $aData;
            }
            $orderData['items'] = $itemList;
            $allData[] = $orderData;
        }
        $data['success'] = true;
        $data['total'] = $total;
        $data['data'] = $allData;
        return $data;
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
        $order = $this::find($orderId);

        $orderItems = $order->items()->get();
        $data = array();
        $data['order_id'] = $order->id;
        $data['token'] = $order->token;
        $data['pharmacy_branch_id'] = $order->pharmacy_branch_id;
        $data['sub_total'] = $order->sub_total;
        $data['tax'] = $order->tax;
        $data['discount'] = $order->discount;
        $data['company_invoice'] = $order->company_invoice;
        $data['invoice'] = $order->invoice;
        $data['created_at'] = date("F j, Y", strtotime($order->created_at));
        $data['remarks'] = $order->remarks;

        $company = MedicineCompany::findOrFail($order->company_id);
        $data['company'] = $company->company_name;

        $mr = DB::table('mrs')->where('id', $order->mr_id)->first();
        $data['mr_name'] = $mr->mr_full_name ?? '';

        $createdBy = DB::table('users')->where('id', $order->created_by)->first();
        $data['created_by'] = $createdBy->name ?? '';
        $data['user_email'] = $createdBy->email ?? '';

        $pharmacy = $order->pharmacy;
        $data['pharmacy'] = $pharmacy->pharmacy_shop_name;

        $pharmacyBranch = $order->PharmacyBranch;
        $data['pharmacy_address'] = $pharmacyBranch->branch_full_address;
        $data['branch_area'] = $pharmacyBranch->branch_area;
        $data['branch_city'] = $pharmacyBranch->branch_city;
        $data['branch_mobile'] = $pharmacyBranch->branch_mobile;

        $items = array();
        foreach ($orderItems as $item) {
            $aData = array();
            $aData['id'] = $item->id;
            $aData['medicine_id'] = $item->medicine_id;
            $aData['power'] = $item->power;
            $aData['quantity'] = $item->quantity;
            $aData['batch_no'] = $item->batch_no;
            $aData['tax'] = $item->tax;
            $aData['dar_no'] = $item->dar_no;
            $aData['unit_price'] = $item->unit_price;
            $aData['sub_total'] = $item->sub_total;
            $aData['discount'] = $item->discount;

            $medicine = $item->medicine;
            $aData['medicine'] = $medicine->brand_name;
            $aData['medicine_power'] = $medicine->strength;
            $aData['medicine_type'] = $medicine->medicineType->name;
            $items[] = $aData;
        }
        $data['order_items'] = $items;

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
        $input = array(
            'pharmacy_id' => $user->pharmacy_id,
            'company_id' => $data['company_id'],
            'pharmacy_branch_id' => $user->pharmacy_branch_id,
            'created_by' => $user->id,
            'is_manual' => true,
            'purchase_date' => empty($data['purchase_date']) ? date('Y-m-d') : $data['purchase_date'],
            'company_invoice' => $data['company_invoice'] ?? '',
            'invoice' => $data['invoice'] ?? '',
            'mr_id' => $data['mr_id'] ?? 0,
            'discount' => empty($data['discount']) ? 0 : $data['discount'],
            'created_at' => date('Y-m-d H:i:s'),
        );

        $orderId = $this::insertGetId($input);

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
        $orderItem = new OrderItem();
        $orderItem = $orderItem
            ->select(DB::raw('
            SUM(sub_total) as total_sub_total,
            SUM(total) as total_amount,
            SUM(quantity) as total_quantity,
            SUM(tax) as total_tax'))
            ->where('order_id', $orderId)
            ->first();

        $order = $this::findOrFail($orderId);

        $data = array(
            'sub_total' => $orderItem->total_sub_total ?? 0,
            'quantity' => $orderItem->total_quantity,
            'total_amount' => $orderItem->total_amount,
            'tax' => $orderItem->total_tax,
            'total_payble_amount' => ($orderItem->total_amount + $orderItem->total_tax) - $order->discount,
        );
        $order->update($data);
        return true;
    }

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->timezone('Asia/Dhaka');
    }
}
