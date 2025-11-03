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
      $user = $request->auth;

      $pageNo = $request->query('page_no', 1);
      $limit = $request->query('limit', 500);
      $offset = ($pageNo - 1) * $limit;

      $query = Medicine::join('medicine_types', 'medicines.medicine_type_id', '=', 'medicine_types.id')
          ->join('products', 'products.medicine_id', '=', 'medicines.id')
          ->leftJoin('brands', 'medicines.brand_id', '=', 'brands.id')
          ->where('products.pharmacy_branch_id', $user->pharmacy_branch_id)
          ->whereNull('products.deleted_at');

      // ✅ Apply filters dynamically
      if ($request->filled('generic')) {
          $query->where('medicines.generic_name', 'LIKE', $request->generic . '%');
      }
      if ($request->filled('company_id')) {
          $query->where('medicines.company_id', $request->company_id);
      }
      if ($request->filled('brand_id')) {
          $query->where('medicines.brand_id', $request->brand_id);
      }
      if ($request->filled('medicine_id')) {
          $query->where('medicines.id', $request->medicine_id);
      }
      if ($request->filled('type_id')) {
          $query->where('medicines.medicine_type_id', $request->type_id);
      }
      if ($request->filled('sale_date')) {
          $dateRange = explode(',', $request->sale_date);
          if (count($dateRange) === 2) {
              $query->whereBetween(DB::raw('DATE(medicines.created_at)'), [$dateRange[0], $dateRange[1]]);
          }
      }

      // ✅ Clone query for total count
      $total = (clone $query)->count();

      $products = $query
          ->select(
              'medicines.id',
              'medicines.id as medicine_id',
              'medicines.brand_id',
              'medicines.generic_name',
              'medicines.barcode',
              'medicines.medicine_type_id',
              'medicines.brand_name',
              'medicines.strength',
              'medicine_types.name as type',
              'brands.name as brand'
          )
          ->orderBy('medicines.id', 'desc')
          ->offset($offset)
          ->limit($limit)
          ->get();

      return response()->json([
          'total' => $total,
          'data' => $products,
          'page_no' => $pageNo,
          'limit' => $limit
      ]);
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

  public function destroy($id, Request $request)
  {
    $product = Product::where('medicine_id', $id)
        ->where('pharmacy_branch_id', $request->auth->pharmacy_branch_id)
        ->first();

    if (!$product) {
        return response()->json(['success' => false, 'message' => 'Product not found'], 404);
    }

    $product->delete();

    // dd($product);
    // SaleItem::where('medicine_id', $id)->delete();
    // CartItem::where('medicine_id', $id)->delete();
    // OrderItem::where('medicine_id', $id)->delete();
    // DamageItem::where('medicine_id', $id)->delete();
    // Product::where('medicine_id', $id)->delete();
    // Notification::where('medicine_id', $id)->delete();
    // Medicine::where('id', $id)->delete();

    return response()->json(['success' => true]);
  }
}
