<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PharmacyBranch;
use App\Models\Order;
use App\Models\Sale;
use App\Models\OrderItem;
use App\Models\Notification;
use App\Models\InventoryDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->auth;
        $client_id = $user->pharmacy_id;
        $shop_id = $user->pharmacy_branch_id;

        $pharmacy = PharmacyBranch::where('pharmacy_id', $client_id)->count();
        $sale = Sale::where('pharmacy_branch_id', $shop_id)->count();
        $order = Order::where('pharmacy_branch_id', $shop_id)->count();
        $medicine = Product::where('pharmacy_branch_id', $shop_id)->count();
        $company = Order::where('pharmacy_branch_id', $shop_id)->select('company_id')->distinct()->get()->count();
        $customer = Sale::select('customer_mobile')->where('pharmacy_branch_id', $shop_id)->distinct()->get()->count();
        $entry = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')->where('pharmacy_branch_id', $shop_id)->count();

        $data = array();
        $data['total_customer'] = $customer;
        $data['total_order'] = $order;
        $data['total_sale'] = $sale;
        $data['total_pharmacy'] = $pharmacy;
        $data['total_company'] = $company;
        $data['total_medicine'] = $medicine;
        $data['total_entry'] = $entry;

        return response()->json($data);
    }

    public function getStatistics()
    {
        $year = date('Y');
        $orderData = array();
        $saleData = array();
        for ($j = 1; $j <= 12; $j++) {
            $i = $j;
            if ($j < 10) {
                $i = '0' . $j;
            }
            $dateStart = $year . '-' . $i . '-01';
            $dateEnd = $year . '-' . $i . '-31';
            if ($i == 2) {
                $dateEnd = $year . '-' . $i . '-28';
            } elseif ($i == 4) {
                $dateEnd = $year . '-' . $i . '-30';
            } elseif ($i == 6) {
                $dateEnd = $year . '-' . $i . '-30';
            } elseif ($i == 9) {
                $dateEnd = $year . '-' . $i . '-30';
            } elseif ($i == 11) {
                $dateEnd = $year . '-' . $i . '-30';
            }
            $order = DB::table('orders')
                ->whereDate('created_at', '<=', $dateEnd)
                ->whereDate('created_at', '>=', $dateStart)
                ->count();

            $sale = DB::table('sales')
                ->whereDate('created_at', '<=', $dateEnd)
                ->whereDate('created_at', '>=', $dateStart)
                ->count();

            $orderData[] = [strtotime($dateStart) * 1000, $order];
            $saleData[] = [strtotime($dateStart) * 1000, $sale];
        }
        $data['order'] = $orderData;
        $data['sale'] = $saleData;
        return response()->json($data);
    }
}
