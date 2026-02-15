<?php

namespace App\Http\Controllers;

use DateTime;
use Validator;
use Carbon\Carbon;
use App\Models\EmiInstallment;
use App\Models\Cart;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
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

class EmiInstallmentController extends Controller
{
  public function index(Request $request)
  {
    $pageNo = (int) $request->query('page_no', 1);
    $limit  = (int) $request->query('limit', 100);
    $offset = ($pageNo - 1) * $limit;

    $query = EmiInstallment::with(['customer', 'sale'])
          ->leftJoin('sales', 'sales.id', '=', 'emi_installments.sale_id')
          ->where('sales.pharmacy_branch_id', $request->auth->pharmacy_branch_id);

    // Month filter
    if ($request->filled('month')) {
        $query->whereYear('due_date', substr($request->month, 0, 4))
              ->whereMonth('due_date', substr($request->month, 5, 2));
    }

    // Status filter
    if ($request->filled('status')) {
        $query->where('emi_installments.status', $request->status);
    }

    // Customer filter
    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->customer_id);
    }

    // Clone for summary & count
    $summaryQuery = clone $query;
    $countQuery   = clone $query;

    // Total rows
    $total = $countQuery->count();

    // Paginated data
    $data = $query
        ->orderBy('due_date')
        ->offset($offset)
        ->limit($limit)
        ->select('emi_installments.*')
        ->get();

    // Summary (ALL filtered rows)
    $summary = [
        'amount'       => (float) $summaryQuery->sum('amount'),
        'paid_amount'  => (float) $summaryQuery->sum('paid_amount'),
        'due_amount'   => (float) $summaryQuery->sum(
            DB::raw('amount - paid_amount')
        ),
    ];

    return response()->json([
        'data' => $data,
        'summary' => $summary,
        'pagination' => [
            'total'        => $total,
            'page_no'      => $pageNo,
            'limit'        => $limit,
            'total_pages'  => ceil($total / $limit),
        ]
    ]);
  }

  public function pay(Request $request, $id)
  {
    try {

      $this->validate($request, [
        'paid_amount' => 'required|numeric|min:1',
        'paid_date'   => 'nullable|date',
      ]);

      return DB::transaction(function () use ($request, $id) {

          $emi = EmiInstallment::lockForUpdate()->findOrFail($id);
          $sale = Sale::lockForUpdate()->findOrFail($emi->sale_id);

          $remaining = $emi->amount - $emi->paid_amount;
          $paying = min($request->paid_amount, $remaining);

          // Update EMI
          $emi->paid_amount += $paying;
          $emi->paid_date = $request->paid_date ?? now();

          if ($emi->paid_amount >= $emi->amount) {
              $emi->status = 'paid';
          } else {
              $emi->status = 'partial';
          }

          $emi->save();

          // Adjust sale due
          $sale->total_due_amount = max(0, $sale->total_due_amount - $paying);

          if ($sale->total_due_amount < 5) {
            $sale->status = Sale::STATUS_COMPLETE;
          }
          
          $sale->save();
          (new Customer())->updateBalance($sale, $paying);

          return response()->json([
            'message' => 'Installment payment successful',
            'emi' => $emi,
            'sale_due' => $sale->total_due_amount
          ]);
      });
    } catch (\Throwable $th) {
      DB::rollBack();

      return response()->json([
          'status' => false,
          'error'  => $th->getMessage(),
      ], 500);
    }
  }

  public function setOverdue() {
    EmiInstallment::where('status', 'pending')
    ->whereDate('due_date', '<', now())
    ->update(['status' => 'overdue']);
  }

}
