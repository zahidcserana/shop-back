<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\CartItem;
use App\Models\Medicine;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\DamageItem;
use App\Models\ConsumerGood;
use App\Models\MedicineType;
use App\Models\Notification;
use App\Models\MedicineCompany;
use App\Models\InventoryDetail;
use App\Models\Order;
use App\Models\OrderDue;
use App\Models\OrderItem;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use App\Exports\PurchaseExport;
use Illuminate\Support\Facades\App;
use Validator;
use DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
  public function genericSearch(Request $request)
  {
    $str = $request->input('search');

    $list = Medicine::where('medicines.generic_name', 'like', $str . '%')
      ->join('products', 'medicines.id', '=', 'products.medicine_id')
      ->orderBy('brand_name', 'asc')
      ->distinct()
      ->get(['generic_name']);
    $data = array();
    foreach ($list as $item) {
      $data[] = $item->generic_name;
    }
    return response()->json($data);
  }
  public function index(Request $request)
  {
    $data = $request->query();
    $pageNo = $request->query('page_no') ?? 1;
    $limit = $request->query('limit') ?? 500;
    $offset = (($pageNo - 1) * $limit);
    $where = array();
    $user = $request->auth;
    // $where = array_merge(array(['sales.pharmacy_branch_id', $user->pharmacy_branch_id]), $where);
    if (!empty($data['generic'])) {
      $where = array_merge(array(['medicines.generic_name', 'LIKE', $data['generic'] . '%']), $where);
    }
    if (!empty($data['company_id'])) {
      $where = array_merge(array(['medicines.company_id', $data['company_id']]), $where);
    }
    if (!empty($data['brand_id'])) {
      $where = array_merge(array(['medicines.brand_id', $data['brand_id']]), $where);
    }
    if (!empty($data['medicine_id'])) {
      $where = array_merge(array(['medicines.id', $data['medicine_id']]), $where);
    }
    if (!empty($data['type_id'])) {
      $where = array_merge(array(['medicines.medicine_type_id', $data['type_id']]), $where);
    }
    if (!empty($data['sale_date'])) {
      $dateRange = explode(',', $data['sale_date']);
      // $query = Sale::where($where)->whereBetween('created_at', $dateRange);
      $where = array_merge(array([DB::raw('DATE(created_at)'), '>=', $dateRange[0]]), $where);
      $where = array_merge(array([DB::raw('DATE(created_at)'), '<=', $dateRange[1]]), $where);
    }
    $query = Medicine::where($where)
      // ->join('medicine_companies', 'medicines.company_id', '=', 'medicine_companies.id')
      ->join('medicine_types', 'medicines.medicine_type_id', '=', 'medicine_types.id')
      ->leftJoin('brands', 'medicines.brand_id', '=', 'brands.id');

    $total = $query->count();
    $products = $query
      ->select('medicines.id', 'medicines.id as medicine_id', 'brand_id', 'medicines.generic_name', 'medicines.barcode', 'medicines.medicine_type_id', 'medicines.brand_name', 'medicines.strength', 'medicine_types.name as type', 'brands.name as brand')
      ->orderBy('medicines.brand_name', 'asc')
      ->offset($offset)
      ->limit($limit)
      ->get();
    $data = array(
      'total' => $total,
      'data' => $products,
      'page_no' => $pageNo,
      'limit' => $limit,
    );
    return response()->json($data);
  }
  public function edit($id, Request $request)
  {
    $data = $request->all();
    $input = array(
      'brand_name' => $data['medicine'],
      'company_id' => $data['company_id'],
      'generic_name' => $data['generic'],
      'medicine_type_id' => $data['type_id'],
      'updated_at' => date('Y-m-d H:i:s')
    );

    $product = Medicine::findOrFail($id);
    $product->update($input);

    return response()->json(['success' => true]);
  }

  public function delete($id)
  {
    SaleItem::where('medicine_id', $id)->delete();
    CartItem::where('medicine_id', $id)->delete();
    OrderItem::where('medicine_id', $id)->delete();
    DamageItem::where('medicine_id', $id)->delete();
    Product::where('medicine_id', $id)->delete();
    Notification::where('medicine_id', $id)->delete();
    Medicine::where('id', $id)->delete();

    return response()->json(['success' => true]);
  }
}
