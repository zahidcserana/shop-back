<?php

namespace App\Http\Controllers;

use DateTime;
use Validator;
use Carbon\Carbon;
use App\Models\Cart;
use App\Models\Sale;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\Medicine;
use App\Models\SaleItem;
use Barryvdh\DomPDF\PDF;
use App\Models\OrderItem;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use App\Models\MedicineCompany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

class SaleController extends Controller
{
  public function downloadPDF($orderId)
  {
    $orderModel = new Order();
    $order = $orderModel->getOrderDetails($orderId);
    $order['no'] = 1;
    $pdf = App::make('dompdf.wrapper');
    $pdf->loadView('pdf', compact('order'))->setPaper('a4', 'portrait');

    return $pdf->download('order.pdf');
  }

  public function _dueLog($itemData, $data)
  {
    $dueLog = array();
    $dueLog = json_decode($itemData->due_log, true);
    if (empty($dueLog)) {
      $dueLog[] = array(
        'total_due_amount' => $itemData['total_due_amount'],
        'total_payble_amount' => $itemData['total_payble_amount'],
        'created_at' => $itemData['created_at']
      );
    }
    $dueLog[] = $data;
    return $dueLog;
  }

  public function paymentTypes(Request $request)
  {
    $user = $request->auth;
    $paymentTypes = PaymentType::select('id', 'name')->where('pharmacy_branch_id', $user->pharmacy_branch_id)->get();
    return response()->json($paymentTypes);
  }

  public function payout(Request $request)
  {
    $user = $request->auth;
    $data = $request->all();
    $data['updated_at'] = date('Y-m-d H:i:s');
    $data['updated_by'] = $user->id;
    $saleData = Sale::where('id', $data['sale_id'])->first();

    $dueLog = $this->_dueLog($saleData, $data);
    $tDue = $saleData->total_due_amount - ($data['amount'] ?? 0);
    $discount = 0;
    if ($tDue <= 5) {
      $discount = $tDue;
      $tDue = 0;
    }

    $input = array(
      'total_due_amount' => $tDue,
      'status' => $tDue > 0 ? 'DUE' : 'COMPLETE',
      'discount' => $saleData->discount + $discount,
      // 'status' => $data['status'],
      'due_log' => json_encode($dueLog),
      'updated_at' => $data['updated_at'],
      'updated_by' => $data['updated_by'],
    );
    $saleModel = new Sale();
    if ($saleData->update($input)) {
      // $saleModel->updateOrder($saleData->sale_id);
      return response()->json(['success' => true, 'data' => $saleModel->getOrderDetails($saleData->id)]);
    }
    return response()->json(['success' => false, 'data' => $saleModel->getOrderDetails($saleData->id)]);
  }

  public function discount(Request $request)
  {
    $user = $request->auth;
    $data = $request->all();
    $data['updated_at'] = date('Y-m-d H:i:s');
    $data['updated_by'] = $user->id;
    $saleData = Sale::where('id', $data['id'])->first();

    // $dueLog = $this->_dueLog($saleData, $data);

    $input = array(
      'total_payble_amount' => $data['total_payble_amount'] ?? 0,
      'discount' => $data['discount'] ?? 0,
      'updated_at' => $data['updated_at'],
      'updated_by' => $data['updated_by'],
    );
    $saleModel = new Sale();
    if ($saleData->update($input)) {
      // $saleModel->updateOrder($saleData->sale_id);
      return response()->json(['success' => true, 'data' => $saleModel->getOrderDetails($saleData->id)]);
    }
    return response()->json(['success' => false, 'data' => $saleModel->getOrderDetails($saleData->id)]);
  }

  public function saleDueList(Request $request)
  {
    $data = $request->query();
    $pageNo = $request->query('page_no') ?? 1;
    $limit = $request->query('limit') ?? 500;
    $offset = (($pageNo - 1) * $limit);
    $where = array();
    $user = $request->auth;
    $where = array_merge(array(['sales.status', 'DUE']), $where);
    $where = array_merge(array(['sales.pharmacy_branch_id', $user->pharmacy_branch_id]), $where);
    if (!empty($data['invoice'])) {
      $where = array_merge(array(['sales.invoice', 'LIKE', '%' . $data['invoice'] . '%']), $where);
    }
    if (!empty($data['customer_mobile'])) {
      $where = array_merge(array(['sales.customer_mobile', 'LIKE', '%' . $data['customer_mobile'] . '%']), $where);
    }
    if (!empty($data['sale_date'])) {
      $dateRange = explode(',', $data['sale_date']);
      // $query = Sale::where($where)->whereBetween('created_at', $dateRange);
      $where = array_merge(array([DB::raw('DATE(created_at)'), '>=', $dateRange[0]]), $where);
      $where = array_merge(array([DB::raw('DATE(created_at)'), '<=', $dateRange[1]]), $where);
    }
    $query = Sale::where($where);
    $total = $query->count();
    $orders = $query
      ->orderBy('sales.id', 'desc')
      ->offset($offset)
      ->limit($limit)
      ->get();
    $orderData = array();
    foreach ($orders as $order) {
      $aData = array();
      $aData['id'] = $order->id;
      $aData['customer_name'] = $order->customer_name;
      $aData['customer_mobile'] = $order->customer_mobile;
      $aData['invoice'] = $order->invoice;
      $aData['total_payble_amount'] = $order->total_payble_amount;
      $aData['total_due_amount'] = $order->total_due_amount;
      $aData['created_at'] = date("Y-m-d H:i:s", strtotime($order->created_at));
      $aData['image'] = $order->file_name ?? '';
      $orderData[] = $aData;
    }
    $data = array(
      'total' => $total,
      'data' => $orderData,
      'page_no' => $pageNo,
      'limit' => $limit,
    );
    return response()->json($data);
  }

  public function uploadimage(Request $request)
  {
    if ($request->hasFile('file')) {
      $user = $request->auth;
      $file      = $request->file('file');
      $filename  = $file->getClientOriginalName();
      $extension = $file->getClientOriginalExtension();
      $picture   = $user->pharmacy_branch_id . date('YmdHis') . '-' . $extension;

      $dir = 'assets/prescription_image/' . $picture;
      $file->move('assets/prescription_image', $picture);

      $im = file_get_contents($dir);
      $uploded_image_file_bytecode = base64_encode($im);

      $cartModel = new Cart();
      $checkFile = $cartModel->where('token', $request->token)->whereNotNull('file_name')->first();
      // return response()->json(["message" => $checkFile]);

      if ($checkFile && file_exists('assets/prescription_image/' . $checkFile->file_name)) {
        unlink('assets/prescription_image/' . $checkFile->file_name);
      }

      $cartData = $cartModel->where('token', $request->token)->update(['file' => $uploded_image_file_bytecode, 'file_name' => $picture]);

      return response()->json(['success' => true, "file_name" => $picture]);
    } else {
      return response()->json(["message" => "Select image first."]);
    }
  }

  public function create(Request $request)
  {
    $data = $request->all();

    $this->validate($request, [
      'token' => 'required',
    ]);
    $orderModel = new Sale();
    $order = $orderModel->makeOrder($data);
    // if($order['success'] == true && $data['sendsms']) {
    //   $data = array(
    //     'mobile' => $order['data']['customer_mobile'],
    //     'message' => 'Thank you for your order. Your Order Invoice is '. $order['data']['invoice'] . '.'
    //   );
    //   $this->_sendMessage($data);
    // }
    return response()->json($order);
  }
  private function _sendMessage($data)
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "http://35.162.97.16/api/v0.0.3/send-sms-api",
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
  }

  public function deleteItem(Request $request)
  {
    $data = $request->all();
    $cartItemModel = new SaleItem();
    $result = $cartItemModel->deleteItem($data);

    return response()->json($result);
  }

  public function view($saleId)
  {
    $orderModel = new Sale();
    $order = DB::table('sales')->where('id', $saleId)->first();

    if (empty($order)) {
      $data['order_items'] = [];
      return response()->json($data);
    }
    return response()->json($orderModel->getOrderDetails($saleId));
  }

  public function latestSale(Request $request)
  {
    $query = $request->query();

    $where = array();
    $user = $request->auth;
    $where = array_merge(array(['sales.pharmacy_branch_id', $user->pharmacy_branch_id]), $where);
    if (!empty($query['invoice'])) {
      $where = array_merge(array(['sales.invoice', 'LIKE', '%' . $query['invoice'] . '%']), $where);
    }

    if (!empty($query['customer_mobile'])) {
      $where = array_merge(array(['sales.customer_mobile', 'LIKE', '%' . $query['customer_mobile'] . '%']), $where);
    }
    if (!empty($query['sale_date'])) {
      $date = explode('GMT', $query['sale_date']);
      $timestamp = strtotime($date[0]);
      $saleDate = date('Y-m-d', $timestamp);

      $query = Sale::where($where)->whereDate('created_at', '=', $saleDate);
    } else {
      $query = Sale::where($where);
    }

    $total = $query->count();
    $orders = $query
      ->orderBy('sales.id', 'desc')
      ->limit(5)
      ->get();
    $orderData = array();
    foreach ($orders as $order) {
      $aData = array();
      $aData['sale_id'] = $order->id;
      $aData['customer_name'] = $order->customer_name;
      $aData['customer_mobile'] = $order->customer_mobile;
      $aData['invoice'] = $order->invoice;
      $aData['total_payble_amount'] = $order->total_payble_amount;
      $aData['created_at'] = date("Y-m-d H:i:s", strtotime($order->created_at));

      $orderData[] = $aData;
    }

    return response()->json($orderData);
  }

  public function index(Request $request)
  {
      $pageNo = $request->query('page_no') ?? 1;
      $limit = $request->query('limit') ?? 100;
      $offset = ($pageNo - 1) * $limit;

      // Base query with LEFT JOIN for filtering
      $baseQuery = Sale::query()
          ->select('sales.*')
          ->leftJoin('sale_items', 'sales.id', '=', 'sale_items.sale_id')
          ->where('sales.pharmacy_branch_id', $request->auth->pharmacy_branch_id)
          ->where('sales.status', '<>', 'CANCEL');

      // Filters
      $baseQuery->when($request['invoice'], fn($q) => $q->where('sales.invoice', 'like', '%' . $request['invoice'] . '%'));
      $baseQuery->when($request['customer_mobile'], fn($q) => $q->where('sales.customer_mobile', 'like', '%' . $request['customer_mobile'] . '%'));
      $baseQuery->when($request['medicine_id'], fn($q) => $q->where('sale_items.medicine_id', $request['medicine_id']));
      $baseQuery->when($request['sale_date'], function ($q) use ($request) {
          $range = explode(',', $request['sale_date']);
          return $q->whereBetween('sales.created_at', [$range[0], $range[1] . ' 23:59:59']);
      });

      // Group to prevent duplicates
      $baseQuery->groupBy('sales.id');

      // Clone for pagination count (after grouping)
      $countQuery = (clone $baseQuery)->select('sales.id');
      $total = $countQuery->get()->count();

      // Clone for summary — WITHOUT JOIN to avoid duplication
      $summaryQuery = Sale::query()
          ->where('pharmacy_branch_id', $request->auth->pharmacy_branch_id)
          ->where('status', '<>', 'CANCEL');

      $summaryQuery->when($request['invoice'], fn($q) => $q->where('invoice', 'like', '%' . $request['invoice'] . '%'));
      $summaryQuery->when($request['customer_mobile'], fn($q) => $q->where('customer_mobile', 'like', '%' . $request['customer_mobile'] . '%'));
      $summaryQuery->when($request['sale_date'], function ($q) use ($request) {
          $range = explode(',', $request['sale_date']);
          return $q->whereBetween('created_at', [$range[0], $range[1] . ' 23:59:59']);
      });

      // Apply medicine filter carefully using subquery if needed
      if ($request['medicine_id']) {
          $saleIds = DB::table('sale_items')
              ->where('medicine_id', $request['medicine_id'])
              ->pluck('sale_id');
          $summaryQuery->whereIn('id', $saleIds);
      }

      $summary = $summaryQuery
          ->selectRaw('SUM(total_payble_amount) as total_sale_amount, SUM(total_due_amount) as total_due_amount')
          ->first();

      // Fetch paginated data
      $orders = $baseQuery
          ->orderBy('sales.id', 'desc')
          ->offset($offset)
          ->limit($limit)
          ->get();

      // Format response
      $orderData = $orders->map(function ($order) {
          return [
              'id' => $order->id,
              'customer_name' => $order->customer_name,
              'customer_mobile' => $order->customer_mobile,
              'invoice' => $order->invoice,
              'total_payble_amount' => $order->total_payble_amount,
              'total_due_amount' => $order->total_due_amount,
              'created_at' => date('Y-m-d H:i:s', strtotime($order->created_at)),
              'image' => $order->file_name ?? '',
          ];
      });

      return response()->json([
          'total' => $total,
          'data' => $orderData,
          'page_no' => $pageNo,
          'limit' => $limit,
          'total_sale_amount' => (float) ($summary->total_sale_amount ?? 0),
          'total_due_amount' => (float) ($summary->total_due_amount ?? 0),
      ]);
  }

  public function dayWiseReport(Request $request)
  {
      $yearMonth = $request->query('year_month', date('Y-m'));
      [$year, $month] = explode('-', $yearMonth);

      // Start and end dates of the month
      $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
      $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

      // Fetch actual sales data grouped by date
      $rawData = Sale::query()
          ->selectRaw('DATE(created_at) as sale_date, SUM(total_payble_amount) as total_amount')
          ->where('pharmacy_branch_id', $request->auth->pharmacy_branch_id)
          ->where('status', '<>', 'CANCEL')
          ->whereBetween('created_at', [$startDate, $endDate])
          ->groupBy(DB::raw('DATE(created_at)'))
          ->pluck('total_amount', 'sale_date');

      // Fill missing days with 0 amount
      $days = [];
      $cursor = $startDate->copy();
      while ($cursor <= $endDate) {
          $dateStr = $cursor->format('Y-m-d');
          $days[] = [
              'date' => $cursor->format('d/m/Y'),
              'amount' => (float) ($rawData[$dateStr] ?? 0),
          ];
          $cursor->addDay();
      }

      return response()->json($days);
  }


  public function report(Request $request)
  {
    $data = $request->query();
    $pageNo = $request->query('page_no') ?? 1;
    $limit = $request->query('limit') ?? 500;
    $offset = (($pageNo - 1) * $limit);
    $where = array();
    $user = $request->auth;
    // $where = array_merge(array(['sales.status', '<>', 'CANCEL']), $where);

    if (!empty($data['company_id'])) {
      $where = array_merge(array(['sale_items.company_id', $data['company_id']]), $where);
    }
    if (!empty($data['product_id'])) {
      $where = array_merge(array(['sale_items.medicine_id', $data['product_id']]), $where);
    }
    if (!empty($data['product_type_id'])) {
      $where = array_merge(array(['sale_items.product_type', $data['product_type_id']]), $where);
    }
    if (empty($data['return_status'])) {
      $where = array_merge(array(['sale_items.return_status', '<>', 'RETURN']), $where);
    }
    if (!empty($data['customer_mobile'])) {
      $where = array_merge(array(['sales.customer_mobile', 'LIKE', '%' . $data['customer_mobile'] . '%']), $where);
    }
    if (!empty($data['sale_date'])) {
      $dateRange = explode(',', $data['sale_date']);
      // $query = Sale::where($where)->whereBetween('created_at', $dateRange);
      $where = array_merge(array([DB::raw('DATE(created_at)'), '>=', $dateRange[0]]), $where);
      $where = array_merge(array([DB::raw('DATE(created_at)'), '<=', $dateRange[1]]), $where);
    }
    $query = SaleItem::where($where)->groupBy('medicine_id', 'product_type')->selectRaw('sum(quantity) as quantity, medicine_id, product_type');

    $total = $query->count();
    $orders = $query
      ->orderBy('quantity', 'desc')
      // ->offset($offset)
      // ->limit($limit)
      ->get();
    $orderData = array();
    $totalSaleAmount = array();
    $totalDueAmount = array();
    foreach ($orders as $order) {
      $aData = array();
      // $aData['id'] = $order->id;
      $medicine = DB::table('medicines')->where('id', $order->medicine_id)->first();
      $aData['medicine'] = ['id' => $medicine->id, 'name' => $medicine->brand_name];

      $company = DB::table('medicine_companies')->where('id', $medicine->company_id)->first();

      $aData['company'] = ['id' => $company->id, 'name' => $company->company_name];

      $medicineTypes = DB::table('medicine_types')->where('id', $medicine->medicine_type_id)->first();
      $aData['type'] = ['id' => $medicineTypes->id, 'name' => $medicineTypes->name];
      $aData['quantity'] = $order->quantity;

      $orderData[] = $aData;
    }
    // return response()->json($orderData);
    $data = array(
      'total' => $total,
      'data' => $orderData,
      'page_no' => $pageNo,
      'limit' => $limit,
      'total_sale_amount' => array_sum($totalSaleAmount),
      'total_due_amount' => array_sum($totalDueAmount),
    );
    return response()->json($data);
  }

  public function update(Request $request)
  {
    $user = $request->auth;
    $data = $request->all();
    $data['updated_at'] = date('Y-m-d H:i:s');
    $data['updated_by'] = $user->id;
    $itemData = SaleItem::where('id', $data['item_id'])->first();
    if ($itemData->quantity == $data['new_quantity'] || $data['new_quantity'] == 0 || $data['new_quantity'] > $itemData->quantity) {
      return response()->json(['success' => false, 'data' => []]);
    }
    $saleData = Sale::where('id', $itemData->sale_id)->first();

    $changeLog = $this->_changeLog($itemData, $data);
    $saleItem = new SaleItem();
    $saleItem->updateInventoryQuantity($itemData, $itemData->quantity - $data['new_quantity'], 'add');

    $input = array(
      'quantity' => $data['new_quantity'],
      'unit_type' => $data['unit_type'] ?? 'PCS',
      'sub_total' => $itemData->unit_price * $data['new_quantity'],
      'change_log' => json_encode($changeLog),
      'updated_at' => $data['updated_at'],
      'updated_by' => $data['updated_by'],
      'return_status' => 'CHANGE',
      'refund_quantity' => $itemData->refund_quantity + ($itemData->quantity - $data['new_quantity'])
    );
    $saleModel = new Sale();

    if ($itemData->update($input)) {
      $saleModel->updateOrder($itemData->sale_id);
      return response()->json(['success' => true, 'data' => $saleModel->getOrderDetails($itemData->sale_id)]);
    }
    return response()->json(['success' => false, 'data' => $saleModel->getOrderDetails($itemData->sale_id)]);
  }
  public function _changeLog($itemData, $data)
  {
    $changeLog = array();
    $changeLog = json_decode($itemData->change_log, true);
    if (empty($changeLog)) {
      $changeLog[] = array(
        'quantity' => $itemData['quantity'],
        'unit_price' => $itemData['unit_price'],
        'sub_total' => $itemData['sub_total'],
        'created_at' => $itemData['created_at']
      );
    }
    $changeLog[] = $data;
    return $changeLog;
  }

  public function saleDueReport(Request $request)
  {
    $data = $request->query();
    $pageNo = $request->query('page_no') ?? 1;
    $limit = $request->query('limit') ?? 500;
    $offset = (($pageNo - 1) * $limit);
    $where = array();
    $dateRangeData = '';
    $user = $request->auth;
    $where = array_merge(array(['sales.status', 'DUE']), $where);
    $where = array_merge(array(['sales.pharmacy_branch_id', $user->pharmacy_branch_id]), $where);
    if (!empty($data['invoice'])) {
      $where = array_merge(array(['sales.invoice', 'LIKE', '%' . $data['invoice'] . '%']), $where);
    }
    if (!empty($data['customer_mobile'])) {
      $where = array_merge(array(['sales.customer_mobile', 'LIKE', '%' . $data['customer_mobile'] . '%']), $where);
    }
    if (!empty($data['due_amount'])) {
      $where = array_merge(array(['sales.total_due_amount', '>=', $data['due_amount']]), $where);
    }
    if (!empty($data['sale_date'])) {
      $dateRange = explode(',', $data['sale_date']);
      $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '>=', $dateRange[0]]), $where);
      $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '<=', $dateRange[1]]), $where);
      $dateRangeData = $dateRange[0] . ' - ' . $dateRange[1];
    } else {
      $today = date('Y-m-d');
      $lastMonth = date("Y-m-d", strtotime("-1 month"));
      $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '>=', $lastMonth]), $where);
      $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '<=', $today]), $where);
      $dateRangeData = $lastMonth . ' - ' . $today;
    }
    $query = Sale::where($where)
      // ->orWhere('due_log', '<>', null)
      ;
    $total = $query->count();
    $orders = $query
      ->orderBy('sales.id', 'desc')
      ->get();
    $orderData = array();
    $sum_sale_amount = 0;
    $sum_advance_amount = 0;
    $sum_sale_due = 0;

    foreach ($orders as $order) {
      $due = $order->due_log ? json_decode($order->due_log, true) : '';
      $aData = array();
      $aData['id'] = $order->id;
      $aData['customer_name'] = $order->customer_name;
      $aData['status'] = $order->status;
      $aData['customer_mobile'] = $order->customer_mobile;
      $aData['invoice'] = $order->invoice;
      $aData['discount'] = $order->discount;
      $aData['sub_total'] = $order->sub_total;
      $sum_sale_amount += $aData['total_payble_amount'] = $order->total_payble_amount;
      $sum_advance_amount += $aData['total_advance_amount'] = $order->total_advance_amount;
      $sum_sale_due += $aData['total_due_amount'] = $order->total_due_amount;
      $aData['created_at'] = date("Y-m-d H:i:s", strtotime($order->created_at));
      $aData['due_payment_date'] = $due ? end($due)['updated_at'] : '';
      $orderData[] = $aData;
    }

    $sammary = array(
      'sum_sale_amount' => $sum_sale_amount,
      'total_advance_amount' => $sum_advance_amount,
      'sum_sale_due' => $sum_sale_due,
      'dateRangeData' => $dateRangeData,
    );

    $data = array(
      'data' => $orderData,
      'summary' => $sammary,
    );

    return response()->json($data);
  }

  public function saleReport(Request $request)
  {
    $query = $request->query();

    $pageNo = $request->query('page_no') ?? 1;
    $limit = $request->query('limit') ?? 1000;
    $offset = (($pageNo - 1) * $limit);
    $where = array();
    $user = $request->auth;
    $where = array_merge(array(['sales.pharmacy_branch_id', $user->pharmacy_branch_id]), $where);
    $dateRangeData = '';

    if (!empty($query['invoice'])) {
      $where = array_merge(array(['sales.invoice', 'LIKE', '%' . $query['invoice'] . '%']), $where);
    }
    if (!empty($query['payment_type'])) {
      $where = array_merge(array(['sales.payment_type', 'LIKE', '%' . $query['payment_type'] . '%']), $where);
    }

    if (!empty($query['sales_man_name'])) {
      $where = array_merge(array(['users.name', 'LIKE', '%' . $query['sales_man_name'] . '%']), $where);
    }
    if (!empty($query['sales_man'])) {
      $where = array_merge(array(['users.id', $query['sales_man']]), $where);
    }
    // if (!empty($query['user_id'])) {
    //     $where = array_merge(array(['users.id', $query['user_id']]), $where);
    // }
    if (!empty($query['sale_date'])) {
      $dateRange = explode(',', $query['sale_date']);
      // $query = Sale::where($where)->whereBetween('created_at', $dateRange);
      if (!empty($query['start_time']) && !empty($query['start_time'])) {
        $start = $dateRange[0] . ' ' . $query['start_time'] . ':00' . ':00';
        $end = $dateRange[0] . ' ' . $query['end_time'] . ':00' . ':00';
        $where = array_merge(array(['sales.created_at', '>=', $start]), $where);
        $where = array_merge(array(['sales.created_at', '<=', $end]), $where);
      } else {
        $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '>=', $dateRange[0]]), $where);
        $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '<=', $dateRange[1]]), $where);
      }
      $dateRangeData = $dateRange[0] . ' - ' . $dateRange[1];
    } else {
      $today = date('Y-m-d');
      $lastMonth = date("Y-m-d", strtotime("-1 month"));
      $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '>=', $lastMonth]), $where);
      $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '<=', $today]), $where);
      $dateRangeData = $lastMonth . ' - ' . $today;
    }
    // if (!empty($query['company'])) {
    //   $where = array_merge(array(['medicine_companies.company_name', 'LIKE', '%' . $query['company'] . '%']), $where);
    // }
    // if (!empty($query['generic'])) {
    //   $where = array_merge(array(['medicines.generic_name', 'LIKE', '%' . $query['generic'] . '%']), $where);
    // }
    if (!empty($query['product_id'])) {
      $where = array_merge(array(['sale_items.medicine_id', $query['product_id']]), $where);
    }
    if (!empty($query['product_type_id'])) {
      $where = array_merge(array(['sale_items.product_type', $query['product_type_id']]), $where);
    }
    if (!empty($query['customer_mobile'])) {
      $where = array_merge(array(['sales.customer_mobile', 'LIKE', '%' . $query['customer_mobile'] . '%']), $where);
    }
    if (!empty($query['customer_name'])) {
      $where = array_merge(array(['sales.customer_name', 'LIKE', '%' . $query['customer_name'] . '%']), $where);
    }

    $query = Sale::where($where)
      ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
      // ->join('medicine_companies', 'sale_items.company_id', '=', 'medicine_companies.id')
      ->join('medicines', 'sale_items.medicine_id', '=', 'medicines.id')
      ->join('medicine_types', 'medicines.medicine_type_id', '=', 'medicine_types.id')
      ->leftJoin('brands', 'medicines.brand_id', '=', 'brands.id')
      ->join('users', 'sales.created_by', '=', 'users.id');

    $total = $query->count();
    // dd($total);
    $orders = $query
      ->select(
        'sales.created_at as sale_date',
        'sales.payment_type',
        'sales.id as sale_id',
        'sales.invoice',
        'sales.sub_total as sale_amount',
        'sales.total_payble_amount',
        'sales.discount as sale_discount',
        'sales.total_due_amount as sale_due',
        'sales.customer_name',
        'sales.customer_mobile',
        'sale_items.id as item_id',
        'sale_items.medicine_id',
        'sale_items.quantity',
        'sale_items.sub_total',
        'sale_items.unit_price as mrp',
        'sale_items.tp',
        'users.name',
        'users.user_mobile',
        // 'medicines.company_id as company_id',
        'medicines.brand_name',
        'medicines.strength',
        'medicine_types.name as medicine_type',
        'brands.name as brand'
      )
      ->orderBy('sales.id', 'desc')
      ->get();
    $result = array();
    foreach ($orders as $element) {
      $result[$element['sale_id']][] = $element;
    }
    $array = array();
    $sum_total_profit = 0;
    $sum_sale_amount = 0;
    $sum_sale_discount = 0;
    $sum_grand_total = 0;
    $sum_sale_due = 0;
    $sum_quantity = 0;

    foreach ($result as $i => $item) {
      $items = array();
      $t = 0;
      $total_profit = 0;
      foreach ($item as $aItem) {
        if ($t == 0) {
          $saleData = array(
            'sale_id' => $i,
            'invoice' => $aItem->invoice,
            'payment_type' => $aItem->payment_type,
            'sale_date' => $aItem->sale_date,
            'sales_man' => $aItem->name,
            'customer' => ['name' => $aItem->customer_name, 'mobile' => $aItem->customer_mobile],
            'sale_amount' => $aItem->sale_amount,
            'sale_discount' => $aItem->sale_discount,
            'sale_due' => $aItem->sale_due,
            'grand_total' => $aItem->total_payble_amount
          );
          $sum_sale_amount += $aItem->sale_amount;
          $sum_sale_discount += $aItem->sale_discount;
          $sum_grand_total += $aItem->total_payble_amount;
          $sum_sale_due += $aItem->sale_due;
        }
        $sum_quantity += $aItem->quantity;
        $sub_tp = $aItem->tp * $aItem->quantity;
        $profit = $aItem->sub_total - $sub_tp;
        $total_profit += $profit;
        $aData = array();
        $aData['medicine'] = ['id' => $aItem->medicine_id, 'brand' => $aItem->brand, 'name' => $aItem->brand_name, 'type' => substr($aItem->medicine_type, 0, 3)];
        $aData['quantity'] = $aItem->quantity;
        $aData['mrp'] = $aItem->mrp;
        $aData['tp'] = $aItem->tp;
        $aData['sub_tp'] = $sub_tp;
        $aData['profit'] = $profit;
        $aData['sub_total'] = $aItem->sub_total;
        $items[] = $aData;
        $t++;
      }
      $sum_total_profit += $total_profit;
      $saleData['total_profit'] = $total_profit;
      $saleData['item'] = $items;

      $array[] = $saleData;
    }
    $summary = array(
      'sum_total_profit' => $sum_total_profit,
      'sum_sale_amount' => $sum_sale_amount,
      'sum_sale_discount' => $sum_sale_discount,
      'sum_grand_total' => $sum_grand_total,
      'sum_sale_due' => $sum_sale_due,
      'sum_quantity' => $sum_quantity,
      'dateRangeData' => $dateRangeData,
    );
    $data = array(
      'data' => $array,
      'summary' => $summary,
    );

    return response()->json($data);
  }

  public function saleReturnReport(Request $request)
  {
    $query = $request->query();

    $pageNo = $request->query('page_no') ?? 1;
    $limit = $request->query('limit') ?? 1000;
    $offset = (($pageNo - 1) * $limit);
    $where = array();
    $user = $request->auth;
    $dateRangeData = '';
    $where = array_merge(array(['sales.pharmacy_branch_id', $user->pharmacy_branch_id]), $where);

    if (!empty($query['invoice'])) {
      $where = array_merge(array(['sales.invoice', 'LIKE', '%' . $query['invoice'] . '%']), $where);
    }
    if (!empty($query['payment_type'])) {
      $where = array_merge(array(['sales.payment_type', 'LIKE', '%' . $query['payment_type'] . '%']), $where);
    }

    if (!empty($query['sales_man_name'])) {
      $where = array_merge(array(['users.name', 'LIKE', '%' . $query['sales_man_name'] . '%']), $where);
    }
    if (!empty($query['sales_man'])) {
      $where = array_merge(array(['users.id', $query['sales_man']]), $where);
    }
    // if (!empty($query['user_id'])) {
    //     $where = array_merge(array(['users.id', $query['user_id']]), $where);
    // }
    if (!empty($query['sale_date'])) {
      $dateRange = explode(',', $query['sale_date']);
      // $query = Sale::where($where)->whereBetween('created_at', $dateRange);
      if (!empty($query['start_time']) && !empty($query['start_time'])) {
        $start = $dateRange[0] . ' ' . $query['start_time'] . ':00' . ':00';
        $end = $dateRange[0] . ' ' . $query['end_time'] . ':00' . ':00';
        $where = array_merge(array(['sales.created_at', '>=', $start]), $where);
        $where = array_merge(array(['sales.created_at', '<=', $end]), $where);
      } else {
        $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '>=', $dateRange[0]]), $where);
        $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '<=', $dateRange[1]]), $where);
      }
      $dateRangeData = $dateRange[0] . ' - ' . $dateRange[1];
    } else {
      $today = date('Y-m-d');
      $lastMonth = date("Y-m-d", strtotime("-1 month"));
      $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '>=', $lastMonth]), $where);
      $where = array_merge(array([DB::raw('DATE(sales.created_at)'), '<=', $today]), $where);
      $dateRangeData = $lastMonth . ' - ' . $today;
    }
    // if (!empty($query['company'])) {
    //   $where = array_merge(array(['medicine_companies.company_name', 'LIKE', '%' . $query['company'] . '%']), $where);
    // }
    if (!empty($query['generic'])) {
      $where = array_merge(array(['medicines.generic_name', 'LIKE', '%' . $query['generic'] . '%']), $where);
    }
    if (!empty($query['product_id'])) {
      $where = array_merge(array(['sale_items.medicine_id', $query['product_id']]), $where);
    }
    if (!empty($query['product_type_id'])) {
      $where = array_merge(array(['sale_items.product_type', $query['product_type_id']]), $where);
    }
    if (!empty($query['customer_mobile'])) {
      $where = array_merge(array(['sales.customer_mobile', 'LIKE', '%' . $query['customer_mobile'] . '%']), $where);
    }
    if (!empty($query['customer_name'])) {
      $where = array_merge(array(['sales.customer_name', 'LIKE', '%' . $query['customer_name'] . '%']), $where);
    }

    $query = Sale::where($where)
      ->whereIn('sale_items.return_status', ['RETURN', 'CHANGE'])
      ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
      // ->join('medicine_companies', 'sale_items.company_id', '=', 'medicine_companies.id')
      ->join('medicines', 'sale_items.medicine_id', '=', 'medicines.id')
      ->leftJoin('brands', 'medicines.brand_id', '=', 'brands.id')
      ->join('medicine_types', 'medicines.medicine_type_id', '=', 'medicine_types.id')
      ->join('users', 'sales.created_by', '=', 'users.id');

    $total = $query->count();
    $orders = $query
      ->select(
        'sales.created_at as sale_date',
        'sales.payment_type',
        'sales.id as sale_id',
        'sales.invoice',
        'sales.sub_total as sale_amount',
        'sales.total_payble_amount',
        'sales.discount as sale_discount',
        'sales.total_due_amount as sale_due',
        'sales.customer_name',
        'sales.customer_mobile',
        'sale_items.id as item_id',
        'sale_items.medicine_id',
        'sale_items.refund_quantity',
        'sale_items.quantity',
        'sale_items.change_log',
        'sale_items.sub_total',
        'sale_items.unit_price as mrp',
        'sale_items.tp',
        'users.name',
        'users.user_mobile',
        'medicines.company_id as company_id',
        'medicines.brand_name',
        'medicines.strength',
        'medicine_types.name as medicine_type',
        'brands.name as brand'
        // 'medicine_companies.company_name as medicine_company'
      )
      ->orderBy('sales.id', 'desc')
      ->get();
    $result = array();
    foreach ($orders as $element) {
      $result[$element['sale_id']][] = $element;
    }
    $array = array();
    $sum_total_profit = 0;
    $sum_sale_amount = 0;
    $sum_sale_discount = 0;
    $sum_grand_total = 0;
    $sum_sale_due = 0;
    $sum_quantity = 0;

    foreach ($result as $i => $item) {
      $items = array();
      $t = 0;
      $total_profit = 0;
      foreach ($item as $aItem) {
        if ($t == 0) {
          $saleData = array(
            'sale_id' => $i,
            'invoice' => $aItem->invoice,
            'payment_type' => $aItem->payment_type,
            'sale_date' => $aItem->sale_date,
            'sales_man' => $aItem->name,
            'customer' => ['name' => $aItem->customer_name, 'mobile' => $aItem->customer_mobile],
            'sale_amount' => $aItem->sale_amount,
            'sale_discount' => $aItem->sale_discount,
            'sale_due' => $aItem->sale_due,
            'grand_total' => $aItem->total_payble_amount
          );
          $sum_sale_amount += $aItem->sale_amount;
          $sum_sale_discount += $aItem->sale_discount;
          $sum_grand_total += $aItem->total_payble_amount;
          $sum_sale_due += $aItem->sale_due;
        }
        $sum_quantity += $aItem->quantity;
        $sub_tp = $aItem->tp * $aItem->quantity;
        $profit = $aItem->sub_total - $sub_tp;
        $total_profit += $profit;
        $aData = array();
        $aData['medicine'] = ['id' => $aItem->medicine_id, 'brand' => $aItem->brand, 'name' => $aItem->brand_name, 'type' => substr($aItem->medicine_type, 0, 3)];
        $aData['quantity'] = $aItem->quantity;
        $aData['mrp'] = $aItem->mrp;
        $aData['tp'] = $aItem->tp;
        $aData['sub_tp'] = $sub_tp;
        $aData['profit'] = $profit;
        $aData['sub_total'] = $aItem->sub_total;
        $aData['refund_quantity'] = $aItem->refund_quantity;
        $aData['change_log'] = !empty($aItem->change_log) ? json_decode($aItem->change_log, true) : [];
        $items[] = $aData;
        $t++;
      }
      $sum_total_profit += $total_profit;
      $saleData['total_profit'] = $total_profit;
      $saleData['item'] = $items;

      $array[] = $saleData;
    }
    $sammary = array(
      'sum_total_profit' => $sum_total_profit,
      'sum_sale_amount' => $sum_sale_amount,
      'sum_sale_discount' => $sum_sale_discount,
      'sum_grand_total' => $sum_grand_total,
      'sum_sale_due' => $sum_sale_due,
      'sum_quantity' => $sum_quantity,
      'dateRangeData' => $dateRangeData,
    );
    $data = array(
      'data' => $array,
      'summary' => $sammary,
    );
    return response()->json($data);
  }

  public function summary($where)
  {
    $model = new Sale();
    $model = $model
      ->select(DB::raw('
              SUM(sub_total) as t_sub_total,
              SUM(discount) as t_discount,
              SUM(total_payble_amount) as t_payble_amount,
              SUM(total_due_amount) as t_due_amount,
              SUM(quantity) as t_quantity
              '))
      ->where($where)
      ->first();
    $data = array(
      'sub_total' => $model->t_sub_total,
      'discount' => $model->t_discount,
      'payble_amount' => $model->t_payble_amount,
      'due_amount' => $model->t_due_amount,
      'quantity' => $model->t_quantity,
    );

    return $data;
  }

  /*
    public function statusUpdate(Request $request)
    {
        $updateQuery = $request->all();
        $updateQuery['updated_at'] = date('Y-m-d H:i:s');

        $changeStatus = OrderItem::find($request->item_id)->is_status_updated;
        if ($changeStatus) {
            return response()->json(['success' => false, 'error' => 'Status Already changed']);
        }
        unset($updateQuery['item_id']);
        $updateQuery['is_status_updated'] = true;
        if (OrderItem::find($request->item_id)->update($updateQuery)) {
            return response()->json(['success' => true, 'status' => OrderItem::find($request->item_id)->status]);
        }
        return response()->json(['success' => false, 'error' => 'Already changed']);
    }



    public function getOrderList(Request $request)
    {
        $query = $request->query();

        $pageNo = $request->query('page_no') ?? 1;
        $limit = $request->query('limit') ?? 100;
        $offset = (($pageNo - 1) * $limit);

        $where = array();
        $user = $request->auth;
        $where = array_merge(array(['orders.pharmacy_branch_id', $user->pharmacy_branch_id]), $where);
        $where = array_merge(array(['orders.is_manual', true]), $where);

        $query = Order::select('orders.id as order_id',
            'orders.company_id',
            'medicine_companies.company_name',
            'orders.invoice',
            'orders.company_invoice',
            'orders.mr_id',
            'mrs.mr_full_name as mr_name',
            'orders.purchase_date',
            'orders.quantity',
            'orders.sub_total',
            'orders.tax as vat',
            'orders.discount',
            'orders.total_amount',
            'orders.total_payble_amount',
            'orders.total_advance_amount',
            'orders.total_due_amount',
            'orders.payment_type',
            'orders.status',
            'orders.created_by')->where($where)
            ->leftjoin('medicine_companies', 'orders.company_id', '=', 'medicine_companies.id')
            ->leftjoin('mrs', 'orders.mr_id', '=', 'mrs.id');

        $total = $query->count();
        $orders = $query
            ->orderBy('orders.id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json(array(
            'total' => $total,
            'page_no' => $pageNo,
            'limit' => $limit,
            'data' => $orders,
        ));
    }

    public function getItemList(Request $request)
    {
        $order_id = $request->query('order');

        if ($order_id) {
            $orderItems = OrderItem::select('order_items.id as item_id', 'medicines.brand_name as medicine_name', 'order_items.quantity',
                'order_items.exp_date', 'order_items.mfg_date', 'order_items.batch_no', 'order_items.unit_price', 'order_items.discount',
                'order_items.total', 'order_items.tax', 'order_items.pieces_per_strip', 'order_items.strip_per_box', 'order_items.free_qty',
                'order_items.receive_qty', 'order_items.mrp', 'order_items.trade_price', 'order_items.is_received')
                ->leftjoin('medicines', 'medicines.id', '=', 'order_items.medicine_id')
                ->where('order_id', $order_id)->get();

            if (count($orderItems)) {
                return response()->json(array(
                    'data' => $orderItems,
                    'status' => 'Successful'
                ));
            }
            return response()->json(array(
                'data' => '',
                'status' => 'Successful',
                'message' => 'No Item found'
            ));
        }

        return response()->json(array(
            'data' => 'No Item found',
            'status' => 'Unsuccessfull',
            'message' => 'Please, select order id!'
        ));

    }

    public function getOrderDetails($orderId)
    {

        if ($orderId) {
            $OrderInfo = Order::select('orders.id as order_id',
                'orders.company_id',
                'medicine_companies.company_name',
                'orders.invoice',
                'orders.company_invoice',
                'orders.mr_id',
                'mrs.mr_full_name as mr_name',
                'orders.purchase_date',
                'orders.quantity',
                'orders.sub_total',
                'orders.tax',
                'orders.discount',
                'orders.total_amount',
                'orders.total_payble_amount',
                'orders.total_advance_amount',
                'orders.total_due_amount',
                'orders.payment_type',
                'orders.status',
                'orders.created_by')->where('orders.id', $orderId)
                ->leftjoin('medicine_companies', 'orders.company_id', '=', 'medicine_companies.id')
                ->leftjoin('mrs', 'orders.mr_id', '=', 'mrs.id')
                ->first();

            return response()->json(array(
                'data' => $OrderInfo,
                'status' => 'Successful',
            ));
        }

        return response()->json(array(
            'data' => '',
            'status' => 'Unsuccessfull!'
        ));
    }

    public function receiveItem(Request $request)
    {

        $item_id = $request->item_id;

        if ($item_id) {
            $orderItem = OrderItem::find($item_id);
            $orderItem->quantity = $request->quantity;
            $orderItem->batch_no = $request->batch_no;
            if ($request->exp_date) {
                $orderItem->exp_date = date("Y-m-d", strtotime($request->exp_date));
            }
            $orderItem->free_qty = $request->free_qty;
            if ($request->mfg_date) {
                $orderItem->mfg_date = date("Y-m-d", strtotime($request->mfg_date));
            }
            $orderItem->mrp = $request->mrp;
            $orderItem->pieces_per_strip = $request->pieces_per_strip;
            $orderItem->receive_qty = $request->receive_qty;
            $orderItem->strip_per_box = $request->strip_per_box;
            $orderItem->total = $request->total;
            $orderItem->trade_price = $request->trade_price;
            $orderItem->unit_price = $request->unit_price;
            $orderItem->tax = $request->vat;
            $orderItem->is_received = 1;

            $orderItem->save();
            $orderItem->medicine_name = $request->medicine_name;

            return response()->json(array(
                'data' => $orderItem,
                'status' => 'Successful'
            ));
        }

        return response()->json(array(
            'data' => 'No Item found',
            'status' => 'Unsuccessfull',
            'message' => 'Please, select order id!'
        ));
    }*/

  // private function _getExpStatus($date)
  // {
  //     $expDate = date("F, Y", strtotime($date));
  //
  //     $today = date('Y-m-d');
  //     $exp1M = date('Y-m-d', strtotime("+1 months", strtotime(date('Y-m-d'))));
  //     $exp3M = date('Y-m-d', strtotime("+3 months", strtotime(date('Y-m-d'))));
  //     if ($date < $today) {
  //         return 'EXP';
  //     } else if ($date >= $today && $date <= $exp1M) {
  //         return '1M';
  //     } else if ($date > $exp1M && $date <= $exp3M) {
  //         return '3M';
  //     } else {
  //         return 'OK';
  //     }
  // }

  // private function _getExpCondition($where, $expTpe)
  // {
  //     $today = date('Y-m-d');
  //     $exp1M = date('Y-m-d', strtotime("+1 months", strtotime(date('Y-m-d'))));
  //     $exp3M = date('Y-m-d', strtotime("+3 months", strtotime(date('Y-m-d'))));
  //     if ($expTpe == 2) {
  //         $where = array_merge(array(
  //             ['order_items.exp_date', '>', $today],
  //             ['order_items.exp_date', '<', $exp1M]
  //         ), $where);
  //     } else if ($expTpe == 3) {
  //         $where = array_merge(array(
  //             ['order_items.exp_date', '>', $exp1M],
  //             ['order_items.exp_date', '<', $exp3M]
  //         ), $where);
  //     } else if ($expTpe == 1) {
  //         $where = array_merge(array(
  //             ['order_items.exp_date', '>', $exp3M]
  //         ), $where);
  //     } else if ($expTpe == 4) {
  //         $where = array_merge(array(['order_items.exp_date', '<', $today]), $where);
  //     }
  //     return $where;
  // }


}
