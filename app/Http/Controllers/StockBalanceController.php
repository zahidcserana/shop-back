<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Medicine;
use App\Models\MedicineCompany;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockBalanceController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->query();
        $pageNo = $request->query('page_no') ?? 1;
        $limit = $request->query('limit') ?? 500;
        $offset = (($pageNo - 1) * $limit);
        $where = array();
        $user = $request->auth;
        $where = array_merge(array(['stock_balances.pharmacy_branch_id', $user->pharmacy_branch_id]), $where);

        if (!empty($data['date_range'])) {
            $dateRange = explode(',', $data['date_range']);
            $where = array_merge(array([DB::raw('DATE(date_open)'), '>=', $dateRange[0]]), $where);
            $where = array_merge(array([DB::raw('DATE(date_close)'), '<=', $dateRange[1]]), $where);
        }

        $query = StockBalance::where($where)->with(['stockBalanceItems', 'stockBalanceItems.medicine']);

        $total = $query->count();

        $results = $query
            ->offset($offset)
            ->limit($limit)
            ->latest()
            ->get();

        foreach ($results as $row) {
            foreach ($row->stockBalanceItems as $item) {
                $item->product = ['name' => $item->medicine->brand_name, 'strength' => $item->medicine->strength, 'type' => $item->medicine->medicineType->name];
                unset($item->medicine);
            }
        }

        $data = array(
            'total' => $total,
            'data' => $results,
            'page_no' => $pageNo,
            'limit' => $limit
        );

        return response()->json($data);
    }

    public function stockBalance(Request $request)
    {
        $user = $request->auth;

        $stockBalance = StockBalance::where('pharmacy_branch_id', $user->pharmacy_branch_id)
            ->whereNull('date_close')->whereNotNull('date_open')
            ->latest()->first();

        DB::beginTransaction();

        try {
            if (!$stockBalance) {
                $stockBalance = new StockBalance();
                $stockBalance->openStockItems($user, Date('Y-m-d'));

                DB::commit();
            } else {
                if (!$stockBalance->date_open < Date('Y-m-d')) {
                    return response()->json(['success' => false, 'error' => 'Already opened']);
                }
                $stockBalance->date_close = Date('Y-m-d');
                $stockBalance->update();

                $stockBalance->closeStockItems();

                $stockBalance = new StockBalance();
                $stockBalance->openStockItems($user, Date('Y-m-d', strtotime("+1 day")));

                DB::commit();
            }
        } catch (\Throwable $th) {
            DB::rollback();
        }

        return response()->json(['success' => true, 'data' => Date('Y-m-d')]);
    }
}
