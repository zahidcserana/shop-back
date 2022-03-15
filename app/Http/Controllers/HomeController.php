<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\Sale;
use App\Models\Beximco;
use App\Models\Medicine;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Notification;
use App\Models\InventoryDetail;
use App\Models\MedicineType;
use App\Models\MedicineCompany;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class HomeController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->auth;

        $pharmacy = DB::table('pharmacy_branches')->count();
        $sale = DB::table('sales')->where('pharmacy_branch_id', $user->pharmacy_branch_id)->count();
        $order = DB::table('orders')->where('pharmacy_branch_id', $user->pharmacy_branch_id)->count();
        $company = DB::table('orders')->where('pharmacy_branch_id', $user->pharmacy_branch_id)->select('company_id')->distinct()->get()->count();
        $medicine = DB::table('order_items')->select('medicine_id')->distinct()->get()->count();
        $customer = DB::table('sales')->select('customer_mobile')->distinct()->get()->count();
        $entry = DB::table('order_items')->count();

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

    public function salePurchasSummary(Request $request)
    {
        $user = $request->auth;
        $sales = DB::table('sales')
            ->select('medicine_companies.company_name as company', DB::raw('SUM(sale_items.sub_total) as amount'))
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->join('medicine_companies', 'sale_items.company_id', '=', 'medicine_companies.id')
            ->where('sales.pharmacy_branch_id', $user->pharmacy_branch_id)
            ->groupBy('sale_items.company_id')
            ->get();

        $orders = DB::table('orders')
            ->select('medicine_companies.company_name as company', DB::raw('SUM(order_items.sub_total) as amount'))
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('medicine_companies', 'order_items.company_id', '=', 'medicine_companies.id')
            ->where('orders.pharmacy_branch_id', $user->pharmacy_branch_id)
            ->groupBy('order_items.company_id')
            ->get();

        $data['sale'] = $sales;
        $data['purchase'] = $orders;

        return response()->json($data);
    }

    public function statusSync($statusData)
    {
        foreach ($statusData as $item) {
            OrderItem::where('id', $item['server_item_id'])->update(['status' => $item['status']]);
        }
    }

    public function dataSync()
    {
        $details_data = [];
        $orders = Order::where('is_sync', 0)->where('status', 'ACCEPTED')->get();

        foreach ($orders as $order) :
            $items = $order->items()->get();
            $details_data[] = array('order_details' => $order, 'order_items' => $items);
        endforeach;

        // $saleData = [];
        // $orders = DB::table('products')->where('is_sync', 0)->where('status', '<>', 'CANCEL')->get();

        foreach ($orders as $order) :
            $items = $order->items()->get();
            $details_data[] = array('order_details' => $order, 'order_items' => $items);
        endforeach;

        /** status sync start */
        // $statusData = OrderItem::where(['is_status_updated' => 1, 'is_status_sync' => 0])
        //     ->whereNotNull('server_item_id')
        //     ->get();

        $itemIds = array();
        // foreach ($statusData as $item) {
        //     $itemIds[] = $item->id;
        // }
        /** status sync end */

        $data = array(
            'details_data' => $details_data,
            'status_data' => [],
        );

        // Make Post Fields Array

        $curl = curl_init();

        curl_setopt_array($curl, array(
            // CURLOPT_URL => "http://dgdaapi.local/data_sync",
            CURLOPT_URL => "http://54.214.203.243:91/data_sync",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                // Set here requred headers
                "accept: */*",
                "accept-language: en-US,en;q=0.8",
                "content-type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response = json_decode($response);
            if (!empty($response)) {
                $data = $response->data;
                foreach ($data as $order) {
                    Order::find($order->local_order_id)->update(['server_order_id' => $order->server_order_id, 'is_sync' => 1]);
                    foreach ($order->items as $item) {
                        OrderItem::find($item->local_item_id)->update(['server_item_id' => $item->server_item_id]);
                    }
                }
                if (!empty($itemIds)) {
                    DB::table('order_items')->whereIn('id', $itemIds)->update(array('is_status_sync' => 1));
                }
            }


            //  print_r(json_decode($response));
        }

        return response()->json([
            'success' => true,
            'data' => $details_data,
            'message' => 'Response is pending from the Server!'
        ], 200);
    }

    public function saleDataSync()
    {
        $details_data = [];
        $orders = Sale::where('is_sync', 0)->get();

        foreach ($orders as $order) :
            $items = $order->items()->get();
            $details_data[] = array('order_details' => $order, 'order_items' => $items);
        endforeach;

        $data = array(
            'details_data' => $details_data
        );
        // $this->saleDataSyncToDB(json_encode($data));
        // Make Post Fields Array
        $curl = curl_init();

        curl_setopt_array($curl, array(
            // CURLOPT_URL => "http://dgdaapi.local/sale_data_sync",
            CURLOPT_URL => "http://54.214.203.243:91/sale_data_sync",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                // Set here requred headers
                "accept: */*",
                "accept-language: en-US,en;q=0.8",
                "content-type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $response = json_decode($response);
            if (!empty($response)) {
                $data = $response->data;
                foreach ($data as $order) {
                    Sale::find($order->local_sale_id)->update(['server_sale_id' => $order->server_sale_id, 'is_sync' => 1]);
                    foreach ($order->items as $item) {
                        SaleItem::find($item->local_item_id)->update(['server_item_id' => $item->server_item_id]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $details_data,
            'message' => 'Response is pending from the Server!'
        ], 200);
    }
    public function saleDataSyncToDB(Request $request)
    {

        $data = $request->all();
        // $data = json_decode(file_get_contents('php://input'), true);

        $inserted_items = [];
        $inserted_item_ids = [];
        $all_datas = $data['details_data'];

        foreach ($all_datas as $data) :

            $order_details = $data['order_details'];
            $order_items = $data['order_items'];

            if (sizeof($order_details)) {
                // $this->imageUpload($order_details);

                $insert_order = new Sale();

                // $insert_order->token = $order_details['token'];
                $insert_order->pharmacy_id = $order_details['pharmacy_id'];
                $insert_order->created_by = $order_details['created_by'];
                $insert_order->pharmacy_branch_id = $order_details['pharmacy_branch_id'];
                $insert_order->invoice = $order_details['invoice'];
                // $insert_order->company_id = $order_details['company_id'];
                // $insert_order->company_invoice = $order_details['company_invoice'];
                // $insert_order->mr_id = $order_details['mr_id'];
                // $insert_order->purchase_date = $order_details['purchase_date'];
                // $insert_order->quantity = $order_details['quantity'];
                $insert_order->sub_total = $order_details['sub_total'];
                // $insert_order->tax = $order_details['tax'];
                $insert_order->discount = $order_details['discount'];
                // $insert_order->total_amount = $order_details['total_amount'];
                $insert_order->total_payble_amount = $order_details['total_payble_amount'];
                $insert_order->total_advance_amount = $order_details['total_advance_amount'];
                $insert_order->total_due_amount = $order_details['total_due_amount'];
                $insert_order->payment_type = $order_details['payment_type'];
                // $insert_order->status = $order_details['status'];
                // $insert_order->remarks = $order_details['remarks'];
                // $insert_order->is_manual = $order_details['is_manual'];
                $insert_order->is_sync = $order_details['is_sync'];
                $insert_order->file = $order_details['file'];
                $insert_order->file_name = $order_details['file_name'];
                $insert_order->created_at = $order_details['created_at'];
                $insert_order->updated_at = $order_details['updated_at'];
                $insert_order->save();

                $local_order_id = $order_details['id'];
                $server_order_id = $insert_order->id;
            }

            if (sizeof($order_items)) {

                foreach ($order_items as $item) :
                    $insert_item = new SaleItem();
                    $insert_item->medicine_id = $item['medicine_id'];
                    $insert_item->company_id = $item['company_id'];
                    $insert_item->quantity = $item['quantity'];
                    $insert_item->sale_id = $server_order_id;
                    $insert_item->exp_date = $item['exp_date'];
                    $insert_item->mfg_date = $item['mfg_date'];
                    $insert_item->batch_no = $item['batch_no'];
                    $insert_item->dar_no = $item['dar_no'];
                    // $insert_item->power = $item['power'];
                    $insert_item->unit_price = $item['unit_price'];
                    $insert_item->sub_total = $item['sub_total'];
                    $insert_item->discount = $item['discount'];
                    $insert_item->total_payble_amount = $item['total_payble_amount'];
                    // $insert_item->tax = $item['tax'];
                    // $insert_item->status = $item['status'];
                    $insert_item->created_at = $item['created_at'];
                    $insert_item->updated_at = $item['updated_at'];
                    $insert_item->save();

                    $inserted_item_ids[] = array('local_item_id' => $item['id'], 'server_item_id' => $insert_item->id);
                endforeach;
            }
            $inserted_items[] = array('local_sale_id' => $local_order_id, 'server_sale_id' => $server_order_id, 'items' => $inserted_item_ids);

            $local_order_id = null;
            $server_order_id = null;
            $inserted_item_ids = [];
        endforeach;
        return response()->json([
            'success' => true,
            'data' => $inserted_items,
            'message' => 'Response from the Server!'
        ], 200);
    }
    public function imageUpload($data)
    {
        if ($data['file']) {
            $picture   = base64_decode($data['file']);
            $dir = 'assets/prescription_image/' . $data['file_name'];
            file_put_contents($dir, $picture);
        }
    }

    public function dataSyncToDB(Request $request)
    {

        $data = json_decode(file_get_contents('php://input'), true);
        $inserted_items = [];
        $inserted_item_ids = [];
        if (!empty($data['status_data'])) {
            $statusData = $data['status_data'];

            $this->statusSync($statusData);
        }

        $all_datas = $data['details_data'];

        foreach ($all_datas as $data) :

            $order_details = $data['order_details'];
            $order_items = $data['order_items'];

            if (sizeof($order_details)) {

                $insert_order = new Order();

                $insert_order->token = $order_details['token'];
                $insert_order->pharmacy_id = $order_details['pharmacy_id'];
                $insert_order->created_by = $order_details['created_by'];
                $insert_order->pharmacy_branch_id = $order_details['pharmacy_branch_id'];
                $insert_order->invoice = $order_details['invoice'];
                $insert_order->company_id = $order_details['company_id'];
                $insert_order->company_invoice = $order_details['company_invoice'];
                $insert_order->mr_id = $order_details['mr_id'];
                $insert_order->purchase_date = $order_details['purchase_date'];
                $insert_order->quantity = $order_details['quantity'];
                $insert_order->sub_total = $order_details['sub_total'];
                $insert_order->tax = $order_details['tax'];
                $insert_order->discount = $order_details['discount'];
                $insert_order->total_amount = $order_details['total_amount'];
                $insert_order->total_payble_amount = $order_details['total_payble_amount'];
                $insert_order->total_advance_amount = $order_details['total_advance_amount'];
                $insert_order->total_due_amount = $order_details['total_due_amount'];
                $insert_order->payment_type = $order_details['payment_type'];
                $insert_order->status = $order_details['status'];
                $insert_order->remarks = $order_details['remarks'];
                $insert_order->is_manual = $order_details['is_manual'];
                $insert_order->is_sync = $order_details['is_sync'];
                $insert_order->created_at = $order_details['created_at'];
                $insert_order->updated_at = $order_details['updated_at'];
                $insert_order->save();

                $local_order_id = $order_details['id'];
                $server_order_id = $insert_order->id;
            }

            if (sizeof($order_items)) {

                foreach ($order_items as $item) :

                    $insert_item = new OrderItem();
                    $insert_item->medicine_id = $item['medicine_id'];
                    $insert_item->company_id = $item['company_id'];
                    $insert_item->quantity = $item['quantity'];
                    $insert_item->order_id = $server_order_id;
                    $insert_item->exp_date = $item['exp_date'];
                    $insert_item->mfg_date = $item['mfg_date'];
                    $insert_item->batch_no = $item['batch_no'];
                    $insert_item->dar_no = $item['dar_no'];
                    $insert_item->power = $item['power'];
                    $insert_item->unit_price = $item['unit_price'];
                    $insert_item->sub_total = $item['sub_total'];
                    $insert_item->discount = $item['discount'];
                    $insert_item->total = $item['total'];
                    $insert_item->tax = $item['tax'];
                    $insert_item->status = $item['status'];
                    $insert_item->created_at = $item['created_at'];
                    $insert_item->updated_at = $item['updated_at'];
                    $insert_item->save();

                    $inserted_item_ids[] = array('local_item_id' => $item['id'], 'server_item_id' => $insert_item->id);
                endforeach;
            }

            $inserted_items[] = array('local_order_id' => $local_order_id, 'server_order_id' => $server_order_id, 'items' => $inserted_item_ids);

            $local_order_id = null;
            $server_order_id = null;
            $inserted_item_ids = [];
        endforeach;

        return response()->json([
            'success' => true,
            'data' => $inserted_items,
            'message' => 'Response from the Server!'
        ], 200);
    }

    public function awsData()
    {
    }
    public function districtList()
    {
        $districts = DB::table('districts')->get();

        return response()->json($districts);
    }

    public function areaList($cityId)
    {
        $areas = DB::table('areas')->where('district_id', $cityId)->get();

        return response()->json($areas);
    }

    public function CompanyList()
    {
        $companies = DB::table('medicine_companies')->get();

        return response()->json($companies);
    }

    public function dataSync_old()
    {
        $orders = Order::where('is_sync', 0)->get();

        $db_ext = \DB::connection('live');
        $itemIds = array();
        foreach ($orders as $order) {
            $items = $order->items()->get();

            $itemIds[] = $order->id;
            unset($order->id);
            $order = $order->toArray();
            $itemId = $db_ext->table('orders')->insertGetId($order);

            foreach ($items as $item) {
                $local_item_id = $item->id;
                unset($item->id);
                $item->order_id = $itemId;
                $item = $item->toArray();
                $server_item_id = $db_ext->table('order_items')->insertGetId($item);
                OrderItem::find($local_item_id)->update(['server_item_id' => $server_item_id]);
            }
        }
        DB::table('orders')->whereIn('id', $itemIds)->update(array('is_sync' => 1));
        $this->statusSync();
        return response()->json(['success' => true]);
    }

    public function statusSync_old()
    {
        $items = OrderItem::where(['is_status_updated' => 1, 'is_status_sync' => 0])->get();

        $db_ext = \DB::connection('live');
        $itemIds = array();
        foreach ($items as $item) {
            $db_ext->table('order_items')->where('id', $item->server_item_id)->update(['status' => $item->status]);

            $itemIds[] = $item->id;
        }
        DB::table('order_items')->whereIn('id', $itemIds)->update(array('is_status_sync' => 1));

        return true;
    }

    public function companyScript()
    {
        $items = OrderItem::all();
        foreach ($items as $item) {
            $order = Order::find($item->order_id)->update(['company_id' => $item->company_id]);
        }
    }

    public function generateNotification(Request $request)
    {
        // $user = $request->auth;
        // $pharmacy_branch_id = $user->pharmacy_branch_id;

        $oneMonth = date('Y-m-d', strtotime('+1 month', strtotime(date('Y-m-d'))));
        $twoMonth = date('Y-m-d', strtotime('+2 month', strtotime(date('Y-m-d'))));
        $threeMonth = date('Y-m-d', strtotime('+3 month', strtotime(date('Y-m-d'))));

        $lowStoclItems = InventoryDetail::select('inventory_details.medicine_id', 'medicines.brand_name', 'inventory_details.exp_date')
            ->where('inventory_details.quantity', '<',  10)
            ->leftjoin('medicines', 'medicines.id', '=', 'inventory_details.medicine_id')
            ->get();

        foreach ($lowStoclItems as $item) :
            $alreadyExist = Notification::where('medicine_id', $item->medicine_id)->where('notification_date', date('Y-m-d'))->where('category', 'LOW_QTY')->get();
            if (!sizeof($alreadyExist)) {
                $details = $item->brand_name . ', The stock quantity is bellow 10. Please update the stock!';
                $insertNotification = new Notification();
                $insertNotification->category = "LOW_QTY";
                $insertNotification->details = $details;
                $insertNotification->pharmacy_branch_id = 1;
                $insertNotification->medicine_id = $item->medicine_id;
                $insertNotification->notification_date = date('Y-m-d');
                $insertNotification->importance = 4;
                $insertNotification->save();
            }
        endforeach;

        $threeMonthItem = InventoryDetail::select('inventory_details.medicine_id', 'medicines.brand_name', 'inventory_details.exp_date')
            ->whereBetween('inventory_details.exp_date', [$twoMonth, $threeMonth])
            ->leftjoin('medicines', 'medicines.id', '=', 'inventory_details.medicine_id')
            ->get();

        foreach ($threeMonthItem as $item) :
            $alreadyExist = Notification::where('medicine_id', $item->medicine_id)->where('notification_date', date('Y-m-d'))->where('category', 'EXP_DATE')->get();
            if (!sizeof($alreadyExist)) {
                $details = $item->brand_name . ', will be expired within three months!';
                $insertNotification = new Notification();
                $insertNotification->category = 'EXP_DATE';
                $insertNotification->details = $details;
                $insertNotification->pharmacy_branch_id = 1;
                $insertNotification->medicine_id = $item->medicine_id;
                $insertNotification->notification_date = date('Y-m-d');
                $insertNotification->importance = 1;
                $insertNotification->save();
            }
        endforeach;

        $twoMonthItem = InventoryDetail::select('inventory_details.medicine_id', 'medicines.brand_name', 'inventory_details.exp_date')
            ->whereBetween('inventory_details.exp_date', [$oneMonth, $twoMonth])
            ->leftjoin('medicines', 'medicines.id', '=', 'inventory_details.medicine_id')
            ->get();

        foreach ($twoMonthItem as $item) :
            $alreadyExist = Notification::where('medicine_id', $item->medicine_id)->where('notification_date', date('Y-m-d'))->where('category', 'EXP_DATE')->get();
            if (!sizeof($alreadyExist)) {
                $details = $item->brand_name . ', will be expired within two months!';
                $insertNotification = new Notification();
                $insertNotification->category = "EXP_DATE";
                $insertNotification->details = $details;
                $insertNotification->pharmacy_branch_id = 1;
                $insertNotification->medicine_id = $item->medicine_id;
                $insertNotification->notification_date = date('Y-m-d');
                $insertNotification->importance = 2;
                $insertNotification->save();
            }
        endforeach;

        $oneMonthItem = InventoryDetail::select('inventory_details.medicine_id', 'medicines.brand_name', 'inventory_details.exp_date')
            ->whereBetween('inventory_details.exp_date', [date('Y-m-d'), $oneMonth])
            ->leftjoin('medicines', 'medicines.id', '=', 'inventory_details.medicine_id')
            ->get();

        foreach ($oneMonthItem as $item) :
            $alreadyExist = Notification::where('medicine_id', $item->medicine_id)->where('notification_date', date('Y-m-d'))->where('category', 'EXP_DATE')->get();
            if (!sizeof($alreadyExist)) {
                $details = $item->brand_name . ', will be expired within one month!';
                $insertNotification = new Notification();
                $insertNotification->category = "EXP_DATE";
                $insertNotification->details = $details;
                $insertNotification->pharmacy_branch_id = 1;
                $insertNotification->medicine_id = $item->medicine_id;
                $insertNotification->notification_date = date('Y-m-d');
                $insertNotification->importance = 3;
                $insertNotification->save();
            }
        endforeach;

        $expiredItem = InventoryDetail::select('inventory_details.medicine_id', 'medicines.brand_name', 'inventory_details.exp_date')
            ->where('inventory_details.exp_date', '<',  date('Y-m-d'))
            ->leftjoin('medicines', 'medicines.id', '=', 'inventory_details.medicine_id')
            ->get();

        foreach ($expiredItem as $item) :
            $alreadyExist = Notification::where('medicine_id', $item->medicine_id)->where('notification_date', date('Y-m-d'))->where('category', 'EXP_DATE')->get();
            if (!sizeof($alreadyExist)) {
                $details = $item->brand_name . ', has been expired! Please through this item to trush!';
                $insertNotification = new Notification();
                $insertNotification->category = "EXP_DATE";
                $insertNotification->details = $details;
                $insertNotification->pharmacy_branch_id = 1;
                $insertNotification->medicine_id = $item->medicine_id;
                $insertNotification->notification_date = date('Y-m-d');
                $insertNotification->importance = 4;
                $insertNotification->save();
            }
        endforeach;

        return response()->json(array(
            'data' => 'Notification has been updated!',
            'status' => 'Successful'
        ));
    }

    public function generateLowStockNotification(Request $request)
    {
        $user = $request->auth;
        $pharmacy_branch_id = $user->pharmacy_branch_id;

        $lowStoclItems = Product::select('products.medicine_id', 'medicines.brand_name', 'products.exp_date', 'products.low_stock_qty', 'products.quantity')
            ->leftjoin('medicines', 'medicines.id', '=', 'products.medicine_id')
            ->get();

        foreach ($lowStoclItems as $item) :
            if ($item->low_stock_qty >= $item->quantity) {
                $alreadyExist = Notification::where('medicine_id', $item->medicine_id)->where('notification_date', date('Y-m-d'))->where('category', 'LOW_QTY')->get();
                if (!sizeof($alreadyExist)) {
                    $details = $item->brand_name . ', The stock quantity is bellow 100. Please update the stock!';
                    $insertNotification = new Notification();
                    $insertNotification->category = "LOW_QTY";
                    $insertNotification->details = $details;
                    $insertNotification->pharmacy_branch_id = $pharmacy_branch_id;
                    $insertNotification->medicine_id = $item->medicine_id;
                    $insertNotification->notification_date = date('Y-m-d');
                    $insertNotification->importance = 4;
                    $insertNotification->save();
                }
            }
        endforeach;
    }

    public function getAllNotificationList(Request $request)
    {
        $notifications = Notification::select('notifications.id', 'notifications.category', 'notifications.details', 'notifications.notification_date', 'notifications.is_read', 'notifications.importance', 'medicines.brand_name')
            ->leftjoin('medicines', 'medicines.id', '=', 'notifications.medicine_id')
            ->orderby('notifications.id', 'DESC')
            ->take(150)
            ->get();

        return response()->json(array(
            'data' => $notifications,
            'status' => 'Successful'
        ));
    }

    public function getNotificationList(Request $request)
    {
        $notifications = Notification::select('notifications.id', 'notifications.category', 'notifications.details', 'notifications.notification_date', 'notifications.is_read', 'notifications.importance', 'medicines.brand_name')
            ->leftjoin('medicines', 'medicines.id', '=', 'notifications.medicine_id')
            ->orderby('notifications.id', 'DESC')
            ->take(10)
            ->get();

        return response()->json(array(
            'data' => $notifications,
            'status' => 'Successful'
        ));
    }

    public function getSalePersonsList(Request $request)
    {
        $user = $request->auth;
        $pharmacy_id = $user->pharmacy_id;
        $pharmacy_branch_id = $user->pharmacy_branch_id;

        $userList = User::select('id', 'name', 'email')->where('pharmacy_id', $pharmacy_id)->where('pharmacy_branch_id', $pharmacy_branch_id)
            ->get();

        return response()->json(array(
            'data' => $userList,
            'status' => 'List Successful!'
        ));
    }

    public function updateMedicineDetails()
    {
        $medicineDetails = Beximco::all();

        foreach ($medicineDetails as $medicine) :
            $med_id = $medicine->med_id;
            $med_TP = $medicine->med_TP;
            $med_VAT = $medicine->med_VAT;
            $med_MRP = $medicine->med_MRP;
            $med_qty_per_box = $medicine->med_qty_per_box;
            $med_name = $medicine->med_name;

            $med_company = $medicine->med_company_id;
            $med_type = $medicine->med_type;
            $med_generic = $medicine->med_generic;
            $med_strength = $medicine->med_strength;

            $UpdateMedicine = Medicine::find($med_id);
            if (sizeof($UpdateMedicine)) {
                $UpdateMedicine->brand_name  = $med_name;
                $UpdateMedicine->pcs_per_box = $med_qty_per_box ? $med_qty_per_box : 0;
                $UpdateMedicine->tp_per_box  = $med_TP ? $med_TP : 0;
                $UpdateMedicine->vat_per_box = $med_VAT ? $med_VAT : 0;
                $UpdateMedicine->mrp_per_box = $med_MRP ? $med_MRP : 0;
                $UpdateMedicine->save();
            } else {
                if (!$med_id) {

                    $company = MedicineCompany::where('company_name', 'like', $med_company)->get();

                    if (sizeof($company)) {
                        $company_id = $company[0]->id;
                    } else {
                        $addCompany = new MedicineCompany();
                        $addCompany->company_name = $med_company;
                        $addCompany->save();
                        $company_id = $addCompany->id;
                    }

                    $MedicineType = MedicineType::where('name', 'like', $med_type)->get();

                    if (sizeof($MedicineType)) {
                        $type_id = $MedicineType[0]->id;
                    } else {
                        $addCPType = new MedicineType();
                        $addCPType->name = $med_type;
                        $addCPType->save();
                        $type_id = $addCPType->id;
                    }

                    $AddMedicine = new Medicine();
                    $AddMedicine->brand_name  = $med_name;
                    $AddMedicine->generic_name = $med_generic;
                    $AddMedicine->strength    = $med_strength;
                    $AddMedicine->company_id  = $company_id;
                    $AddMedicine->medicine_type_id = $type_id;
                    $AddMedicine->brand_name  = $med_name;
                    $AddMedicine->pcs_per_box = $med_qty_per_box ? $med_qty_per_box : 0;
                    $AddMedicine->tp_per_box  = $med_TP ? $med_TP : 0;
                    $AddMedicine->vat_per_box = $med_VAT ? $med_VAT : 0;
                    $AddMedicine->mrp_per_box = $med_MRP ? $med_MRP : 0;

                    $AddMedicine->save();
                }
            }
        endforeach;
        return response()->json(array(
            'status' => 'Successful'
        ));
    }
}
