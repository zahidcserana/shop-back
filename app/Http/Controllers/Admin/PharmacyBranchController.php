<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PharmacyBranch;
use App\Models\PaymentType;
use App\Models\Pharmacy;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyBranchController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = $request->get('search');
        $sortBy  = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');

        $query = PharmacyBranch::with('pharmacy', 'paymentTypes');

        // Filter 1: Already Expired
        if ($request->boolean('expired')) {
            $query->whereRaw("DATE_ADD(created_at, INTERVAL subscription_period DAY) < NOW()");
        }

        // Filter 2: Expiring within the next 10 days
        if ($request->boolean('expired_soon')) {
            $query->whereRaw("DATE_ADD(created_at, INTERVAL subscription_period DAY) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 10 DAY)");
        }

        // 🔍 Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                ->orWhere('branch_city', 'like', "%{$search}%")
                ->orWhere('branch_mobile', 'like', "%{$search}%")
                ->orWhere('branch_contact_person_name', 'like', "%{$search}%");
            });
        }

        // 🔃 Sorting (whitelist for security)
        $allowedSorts = [
            'id',
            'branch_name',
            'branch_city',
            'branch_contact_person_name',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $data = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data'   => $data->items(),
            'total' => $data->total()
        ]);
    }

    public function store(Request $request)
    {
        try {

            DB::beginTransaction();

            $this->validate($request, [
                'pharmacy_id' => 'required|exists:pharmacies,id',
                'branch_name' => 'required',
                'email' => 'required|unique:users',
                'password' => 'required',
            ]);

            $shop = PharmacyBranch::create([
                'pharmacy_id' => $request->pharmacy_id,
                'branch_name' => $request->branch_name,
                'branch_city' => $request->branch_city,
                'branch_area' => $request->branch_area,
                'branch_full_address' => $request->branch_full_address,
                'branch_mobile' => $request->branch_mobile,
                'branch_alt_mobile' => $request->branch_alt_mobile,
                'branch_contact_person_name' => $request->branch_contact_person_name,
                'branch_contact_person_mobile' => $request->branch_contact_person_mobile,
                'branch_contact_person_alt_mobile' => $request->branch_contact_person_alt_mobile,
                'branch_model_pharmacy_status' => $request->branch_model_pharmacy_status ?? PharmacyBranch::STATUS_ACTIVE,
                'branch_config' => $request->branch_config,
                'subscription_period' => $request->subscription_period,
                'subscription_count' => $request->subscription_count,
            ]);

            $shop->branch_image = $request->branch_image
                ? $shop->saveLogo($request->branch_image, 'branch')
                : null;
            
            $shop->save();

            if ($request->has('payment_methods')) {
                $shop->setPaymentType($request);
            }

            $client = Pharmacy::findOrFail($request->pharmacy_id);

            (new User())->createUser([
                'name' => $client->pharmacy_shop_name,
                'pharmacy_branch_id' => $shop->id,
                'pharmacy_id' => $client->id,
                'user_type' => User::ROLE_ADMIN,
                'user_mobile' => $shop->branch_mobile,
                'email' => $request->email,
                'password' => $request->password,
            ]);

            Warehouse::create([
                'pharmacy_id' => $request->pharmacy_id,
                'name' => Warehouse::$DEFAULT_WAREHOUSE,
                'location' => $request->branch_city,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $shop
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'data'   => 'Something went wrong!',
                'status' => false,
                'error'  => $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $branch = PharmacyBranch::findOrFail($id);

            $this->validate($request, [
                'pharmacy_id' => 'required|exists:pharmacies,id',
                'branch_name' => 'required'
            ]);

            $data = $request->except('branch_image');
            
            if ($request->has('branch_image')) {
                $newImage = $request->branch_image;

                if ($newImage && str_starts_with($newImage, 'data:image')) {
                    // Now these methods belong to $branch via the trait
                    $branch->deleteImageIfExists($branch->branch_image);
                    $data['branch_image'] = $branch->saveLogo($newImage, 'branch');
                } elseif (is_null($newImage)) {
                    $branch->deleteImageIfExists($branch->branch_image);
                    $data['branch_image'] = null;
                }
            }

            $branch->update($data);

            if ($request->has('payment_methods')) {
                $branch->setPaymentType($request);
            }
            
            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $branch->load(['pharmacy', 'paymentTypes'])
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'error'  => $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $shop = PharmacyBranch::with('pharmacy', 'paymentTypes', 'admin')->findOrFail($id);

        return response()->json($shop);
    }


    public function destroy($id)
    {
        PharmacyBranch::findOrFail($id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Shop deleted successfully'
        ]);
    }

    public function subscription(Request $request, $id) 
    {
        try {
            $branch = PharmacyBranch::findOrFail($id);

            $this->validate($request, [
                'added_days' => 'required|integer|min:1'
            ]);

            // Atomic increment
            $branch->increment('subscription_period', $request->added_days);

            return response()->json([
                'status' => true,
                'data' => $branch->refresh(), // refresh() gets the new total from DB
                'message' => "Subscription extended by {$request->added_days} days."
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'error' => $th->getMessage()], 500);
        }
    }
}
