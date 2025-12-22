<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Medicine;
use App\Models\Damage;
use App\Models\DamageItem;
use Illuminate\Http\Request;
use Validator;
use DB;
use Carbon\Carbon;

class DamageController extends Controller
{
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'items'                   => 'required|array|min:1',
                'items.*.medicine_id'     => 'required|integer',
                'items.*.quantity'        => 'required|integer|min:1',
                'items.*.box_trade_price' => 'required|numeric|min:0',
            ]);

            return DB::transaction(function () use ($request) {

                $damage = Damage::create([
                    'pharmacy_branch_id' => $request->auth->pharmacy_branch_id,
                    'pharmacy_id'        => $request->auth->pharmacy_id,
                    'remarks'            => $request->remarks,
                    'status'             => Damage::STATUS_CONFIRMED,
                ]);

                $totalAmount = 0;
                $companyId   = null;

                foreach ($request->items as $item) {

                    $qty   = (int) $item['quantity'];
                    $price = (float) $item['box_trade_price'];

                    $product = Product::where('medicine_id', $item['medicine_id'])
                        ->where('pharmacy_branch_id', $request->auth->pharmacy_branch_id)
                        ->first();

                    if (!$product) {
                        throw new \Exception(
                            'Product not found for medicine ID: ' . $item['medicine_id']
                        );
                    }

                    $companyId ??= $product->company_id;

                    $updated = Product::where('id', $product->id)
                        ->where('quantity', '>=', $qty)
                        ->decrement('quantity', $qty);

                    if (!$updated) {
                        throw new \Exception(
                            'Insufficient stock for medicine ID: ' . $item['medicine_id']
                        );
                    }

                    DamageItem::create([
                        'damage_id'   => $damage->id,
                        'company_id'  => $product->company_id,
                        'medicine_id' => $item['medicine_id'],
                        'batch_no'    => $item['batch_no'] ?? null,
                        'unit'        => $item['unit'] ?? 'pcs',
                        'quantity'    => $qty,
                        'price'       => $price,
                        'remarks'     => $item['remarks'] ?? null,
                    ]);

                    $totalAmount += $qty * $price;
                }

                $damage->update([
                    'total_amount' => $totalAmount,
                    'company_id'   => $companyId,
                    'invoice'      => $damage->id . Carbon::now()->timestamp,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Damage created successfully'
                ], 201);
            });

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Damage save failed',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    public function getProductDetails(Request $request)
    {
        $user = $request->auth;
        $shop_id = $user->pharmacy_branch_id;

        if (!$request->medicine_id) {
            return response()->json(['error' => 'medicine_id is required'], 400);
        }

        $itemDetails = Medicine::join('products', 'products.medicine_id', '=', 'medicines.id')
            ->where('products.pharmacy_branch_id', $shop_id)
            ->where('medicines.id', $request->medicine_id)
            ->select(
                'medicines.id',
                'medicines.brand_name',
                'medicines.pcs_per_box as pieces_per_box',
                'medicines.vat_per_box as box_vat',
                'medicines.barcode',
                'products.tp as trade_price',
                'products.mrp',
                'products.low_stock_qty',
                'products.percentage'
            )
            ->first();

        return response()->json($itemDetails);
    }

    public function damageList(Request $request)
    {
        $pageNo = $request->query('page_no') ?? 1;
        $limit = $request->query('limit') ?? 100;
        $offset = (($pageNo - 1) * $limit);

        $collection = Damage::query();
        $collection->where('pharmacy_branch_id', $request->auth->pharmacy_branch_id);

        $collection->when($request['invoice'], function ($q) use ($request) {
            return $q->where('invoice', 'like', '%' . $request['invoice'] . '%');
        });
       
        $collection->when($request['company_id'], function ($q) use ($request) {
            return $q->where('company_id', $request['company_id']);
        });
        $collection->when($request['damage_date'], function ($q) use ($request) {
            $dateRange = explode(',', $request['damage_date']);
            return $q->whereBetween('created_at', [$dateRange[0], $dateRange[1] . ' 23:59:59']);
        });

        $total = $collection->count();
        $damages = $collection
            ->latest()
            ->offset($offset)
            ->limit($limit)
            ->select('damages.*')
            ->get();

        foreach ($damages as $damage) {
            $damage->company;
        }

        return response()->json(array(
            'total' => $total,
            'page_no' => $pageNo,
            'limit' => $limit,
            'data' => $damages,
        ));
    }

    public function delete(Request $request)
    {
        try {
            $damage = Damage::find($request->damage_id);

            if (!$damage) {
                return response()->json([
                    'status' => false,
                    'message' => "Damage record not found.",
                ], 404);
            }

            $damage->damageItems()->delete();

            if ($damage->delete()) {
                return response()->json([
                    'status' => true,
                    'message' => "Damage and related items deleted successfully.",
                ]);
            }
            
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Deletion failed.',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    public function view($damageId)
    {
        if ($damageId) {
            $orderDetails = Damage::select(
                'id',
                'invoice',
                'created_at as damage_date',
                'total_amount',
                'status'
            )->where('id', $damageId)->first();

            $orderItems = DamageItem::select(
                'damage_items.id as item_id',
                'damage_items.medicine_id',
                'damage_items.damage_id as item_order_id',
                'medicines.brand_name as medicine_name',
                'medicines.generic_name as generic',
                'medicines.barcode',
                'medicine_types.name as medicine_type',
                'damage_items.company_id',
                'brands.name as brand',
                'damage_items.quantity',
                'damage_items.unit',
                'damage_items.batch_no',
                'damage_items.price',
            )
                ->leftjoin('medicines', 'medicines.id', '=', 'damage_items.medicine_id')
                ->leftjoin('medicine_types', 'medicine_types.id', '=', 'medicines.medicine_type_id')
                ->leftjoin('brands', 'medicines.brand_id', '=', 'brands.id')
                ->where('damage_id', $damageId)->get();

            if (sizeof($orderItems)) {
                return response()->json(array(
                    'data' => $orderItems,
                    'damage' => $orderDetails,
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

}
