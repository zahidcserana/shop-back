<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\Medicine;
use App\Models\SaleItem;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\MedicineCompany;
use Illuminate\Support\Facades\DB;

class MedicineController extends Controller
{
    public function medicineWithExpiredDate(Request $request)
    {
        $data = $request->query();
        $pageNo = $request->query('page_no') ?? 1;
        $limit = $request->query('limit') ?? 500;
        $offset = (($pageNo - 1) * $limit);
        $where = array();
        $user = $request->auth;
        $where = array_merge(array(['orders.pharmacy_branch_id', $user->pharmacy_branch_id]), $where);
        $where = array_merge(array(['order_items.exp_date', '<>', '1970-01-01']), $where);

        if (!empty($data['medicine_id'])) {
            $where = array_merge(array(['order_items.medicine_id', $data['medicine_id']]), $where);
        }
        if (!empty($data['company_id'])) {
            $where = array_merge(array(['order_items.company_id', $data['company_id']]), $where);
        }
        if (!empty($data['exp_type'])) {
            $where = $this->_getExpType($where, $data['exp_type']);
        }
        if (!empty($data['expiry_date'])) {
            $dateRange = explode(',', $data['expiry_date']);
            // $query = Sale::where($where)->whereBetween('created_at', $dateRange);
            $where = array_merge(array([DB::raw('DATE(exp_date)'), '>=', $dateRange[0]]), $where);
            $where = array_merge(array([DB::raw('DATE(exp_date)'), '<=', $dateRange[1]]), $where);
        }
        $query = DB::table('orders')
            ->select(
                'medicine_companies.company_name as company',
                'medicines.brand_name',
                'medicines.strength',
                'medicine_types.name as type',
                'order_items.batch_no',
                'order_items.exp_date',
                DB::raw('(order_items.quantity * order_items.pieces_per_box) AS qty'),
                DB::raw('DATE_FORMAT(order_items.created_at, "%Y-%m-%d") as purchase_date')
            )
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('medicines', 'order_items.medicine_id', '=', 'medicines.id')
            ->join('medicine_types', 'medicines.medicine_type_id', '=', 'medicine_types.id')
            ->join('medicine_companies', 'order_items.company_id', '=', 'medicine_companies.id')
            ->where($where);
        $total = $query->count();
        $items = $query
            ->orderBy('order_items.exp_date', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        foreach ($items as $item) {
            $item->exp_status = $this->_getExpStatus($item->exp_date);
            $item->exp_condition = $this->_getExpCondition($item->exp_date);
        }
        $data = array(
            'total' => $total,
            'data' => $items,
            'page_no' => $pageNo,
            'limit' => $limit,
        );
        return response()->json($data);
    }

    public function getExpiryMedicine()
    {
        $today = date('Y-m-d');
        $exp1M = date('Y-m-d', strtotime("+1 months", strtotime(date('Y-m-d'))));
        $exp3M = date('Y-m-d', strtotime("+3 months", strtotime(date('Y-m-d'))));

        $medicine_list = OrderItem::where('exp_date', '<>', '1970-01-01')->get();

        $all_expired = $medicine_list->where('exp_date', '<', date('Y-m-d'))->count();
        $expired_one_month = $medicine_list->where('exp_date', '>', date('Y-m-d'))->where('exp_date', '<', $exp1M)->count();
        $expired_three_month = OrderItem::where('exp_date', '>', $exp1M)->where('exp_date', '<', $exp3M)->get()->count();

        $all_expired_list = OrderItem::select('order_items.exp_date', 'medicine_companies.company_name', 'medicines.brand_name', 'medicines.generic_name', 'medicines.strength')
            ->where('order_items.exp_date', '<>', '1970-01-01')
            ->where('order_items.exp_date', '<', date('Y-m-d'))
            ->leftjoin('medicines', 'medicines.id', '=', 'order_items.medicine_id')
            ->leftjoin('medicine_companies', 'medicine_companies.id', '=', 'order_items.company_id')
            ->get();

        $all_sell_item = SaleItem::select('sale_items.medicine_id', 'sale_items.company_id', 'medicine_companies.company_name', 'medicines.brand_name', 'medicines.generic_name', 'medicines.strength', DB::raw('SUM(sale_items.quantity) as quantity'))
            ->leftjoin('medicines', 'medicines.id', '=', 'sale_items.medicine_id')
            ->leftjoin('medicine_companies', 'medicine_companies.id', '=', 'sale_items.company_id')
            ->groupBy('sale_items.medicine_id')
            ->orderBy('quantity', 'DESC')
            ->get();

        $top_company = SaleItem::select('sale_items.company_id', 'medicine_companies.company_name', DB::raw('SUM(sale_items.sub_total) as amount'))
            ->leftjoin('medicine_companies', 'medicine_companies.id', '=', 'sale_items.company_id')
            ->groupBy('sale_items.company_id')
            ->orderBy('amount', 'DESC')
            ->get();

        $data = array(
            'top_company' => $top_company->take(5),
            'expired_list' => $all_expired_list->take(5),
            'sale_details' => $all_sell_item->take(5),
            'total_expired' => $all_expired,
            'total_expired_one_month' => $expired_one_month,
            'total_expired_three_month' => $expired_three_month
        );

        return response()->json($data);
    }

    private function _getExpType($where, $expTpe)
    {
        $today = date('Y-m-d');
        $exp1M = date('Y-m-d', strtotime("+1 months", strtotime(date('Y-m-d'))));
        $exp3M = date('Y-m-d', strtotime("+3 months", strtotime(date('Y-m-d'))));
        if ($expTpe == 2) {
            $where = array_merge(array(
                ['order_items.exp_date', '>', $today],
                ['order_items.exp_date', '<', $exp1M]
            ), $where);
        } else if ($expTpe == 3) {
            $where = array_merge(array(
                ['order_items.exp_date', '>', $today],
                ['order_items.exp_date', '<', $exp3M]
            ), $where);
        } else if ($expTpe == 1) {
            $where = array_merge(array(
                ['order_items.exp_date', '>', $exp3M]
            ), $where);
        } else if ($expTpe == 4) {
            $where = array_merge(array(['order_items.exp_date', '<', $today]), $where);
        }
        return $where;
    }

    private function _getExpStatus($date)
    {
        $expDate = date("F, Y", strtotime($date));

        $today = date('Y-m-d');
        $exp1M = date('Y-m-d', strtotime("+1 months", strtotime(date('Y-m-d'))));
        $exp3M = date('Y-m-d', strtotime("+3 months", strtotime(date('Y-m-d'))));
        if ($date < $today) {
            return 'EXP';
        } else if ($date <= $exp3M) {
            return '3M';
        } else {
            return 'OK';
        }
    }
    private function _getExpCondition($date)
    {
        $expDate = date("F, Y", strtotime($date));

        $today = date('Y-m-d');
        $exp1M = date('Y-m-d', strtotime("+1 months", strtotime(date('Y-m-d'))));
        $exp3M = date('Y-m-d', strtotime("+3 months", strtotime(date('Y-m-d'))));
        if ($date < $today) {
            return 'EXPIRED';
        } else if ($date <= $exp3M) {
            return 'EXPIRED IN 3 MONTH';
        } else {
            return 'VALID';
        }
    }

    public function search(Request $request)
    {
        $str = $request->input('search');

        $companyData = $request->input('company') ? MedicineCompany::where('company_name', 'like', $request->input('company'))->first() : 0;

        $company_id =  $companyData ? $companyData->id : 0;

        $medicines = Medicine::where('brand_name', 'like', $str . '%')
            ->orWhere('barcode', 'like', $str . '%')
            ->when($company_id, function ($query, $company_id) {
                return $query->where('company_id', $company_id);
            })
            ->orderBy('brand_name', 'asc')
            ->get();
        $data = array();
        foreach ($medicines as $medicine) {
            $medicineStr = $medicine->brand_name . ' (' . $medicine->strength . ',' . $medicine->medicineType->name . ')';
            $data[] = ['id' => $medicine->id, 'name' => $medicineStr];
        }
        return response()->json($data);
    }

    public function searchByPharmacy(Request $request)
    {
        $str = $request->input('search');
        $openSale = true;
        $pharmacyMedicineIds = $openSale ? false : DB::table('inventories')->select('medicine_id')->distinct()->pluck('medicine_id');

        $medicines = Medicine::where('brand_name', 'like', $str . '%')
            ->orWhere('barcode', 'like', $str . '%')
            ->when($pharmacyMedicineIds, function ($query, $pharmacyMedicineIds) {
                return $query->whereIn('id', $pharmacyMedicineIds);
            })
            ->orderBy('brand_name', 'asc')
            ->get();
        $data = array();
        foreach ($medicines as $medicine) {
            $company = DB::table('medicine_companies')->where('id', $medicine->company_id)->first();
            $medicineType = $medicine->product_type == 1 ? $medicine->medicineType->name : ' CP';
            $medicineStr = $medicine->brand_name . ' (' . $medicine->strength . ',' . $medicineType . ')';
            $data[] = ['id' => $medicine->id, 'name' => $medicineStr, 'company' => $company->company_name];
        }
        return response()->json($data);
    }

    public function searchMedicineFromInventory(Request $request)
    {
        $str = $request->input('search');

        $companyData = $request->input('company') ? MedicineCompany::where('company_name', 'like', $request->input('company'))->first() : 0;
        $company_id =  $companyData ? $companyData->id : 0;
        $pharmacyMedicineIds = DB::table('products')->select('medicine_id')->distinct()->pluck('medicine_id');

        $medicines = Medicine::where('brand_name', 'like', $str . '%')
            ->when($company_id, function ($query, $company_id) {
                return $query->where('company_id', $company_id);
            })
            ->when($pharmacyMedicineIds, function ($query, $pharmacyMedicineIds) {
                return $query->whereIn('id', $pharmacyMedicineIds);
            })
            ->get();
        $data = array();
        foreach ($medicines as $medicine) {
            $medicineStr = $medicine->brand_name . ' (' . $medicine->strength . ',' . $medicine->medicineType->name . ')';
            $data[] = ['id' => $medicine->id, 'name' => $medicineStr];
        }
        return response()->json($data);
    }

    public function batchList(Request $request)
    {
        $batches = DB::table('products')->where('medicine_id', $request->input('medicine_id'))->pluck('batch_no');

        return response()->json($batches);
    }

    public function getAvailableQuantity(Request $request)
    {
        $product = DB::table('products')
            ->select(DB::raw('SUM(quantity) as available_quantity'))
            ->where('medicine_id', $request->input('medicine_id'))
            ->first();

        $cartItem = new CartItem();
        $cartItem = $cartItem
            ->select(DB::raw('SUM(quantity) as total_quantity'))
            ->where('medicine_id', $request->input('medicine_id'))
            ->whereDate('created_at', Carbon::today())
            ->first();
        if ($cartItem) {
            $available = $product->available_quantity - $cartItem->total_quantity;
        }
        return response()->json(['available_quantity' => $available]);
    }

    public function searchByCompany(Request $request)
    {
        $companyId = $request->input('company');
        $medicines = Medicine::where('company_id', $companyId)
            ->limit(100)
            ->get();
        $data = array();
        foreach ($medicines as $medicine) {
            $aData = array();
            $aData['id'] = $medicine->id;
            $aData['brand_name'] = $medicine->brand_name;
            $aData['generic_name'] = $medicine->generic_name;
            $aData['strength'] = $medicine->strength;
            $aData['dar_no'] = $medicine->dar_no;
            $aData['price_per_pcs'] = $medicine->price_per_pcs;
            $aData['price_per_box'] = $medicine->price_per_box;
            $aData['price_per_strip'] = $medicine->price_per_strip;
            $aData['pcs_per_box'] = $medicine->pcs_per_box;
            $aData['pcs_per_strip'] = $medicine->pcs_per_strip;

            $aData['medicine_type'] = $medicine->medicineType->name;

            $data[] = $aData;
        }

        return response()->json($data);
    }
}
