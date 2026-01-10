<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Pharmacy;
use App\Models\PaymentType;
use App\Models\PharmacyBranch;
use App\Models\Brand;
use App\Models\MedicineType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Support\Facades\Hash;


class AdminController extends Controller
{
    public function shops()
    {
        $list = Pharmacy::orderBy('id', 'desc')->get();

        return response()->json($list);
    }

    public function branches()
    {
        $list = PharmacyBranch::with('pharmacy')->orderBy('id', 'desc')->get();

        return response()->json($list);
    }

    public function storeShop(Request $request)
    {
        $exist = Pharmacy::where('pharmacy_shop_code', $request->pharmacy_shop_code)->first();

        if ($exist) {
            return response()->json([
                'status' => false,
                'message' => "Duplicate shop code"
            ], 409);
        }

        $client = Pharmacy::create([
            'pharmacy_shop_code' => $request->pharmacy_shop_code,
            'pharmacy_shop_name' => $request->client_name,
            'pharmacy_shop_owner_name' => $request->owner_name,
            'pharmacy_shop_licence_no' => $request->licence_no ?? '',
            'pharmacy_shop_branch_owner_nid' => $request->owner_nid ?? '',
        ]);

        // Save image to storage
        $imagePath = $this->saveLogo($request->branch_image, 'branch');

        $shop = PharmacyBranch::create([
            'pharmacy_id' => $client->id,
            'branch_name' => $request->branch_name,
            'branch_image' => $imagePath,
            'branch_city' => $request->branch_city,
            'branch_area' => $request->branch_area,
            'branch_full_address' => $request->branch_full_address,
            'branch_mobile' => $request->branch_mobile,
            'branch_contact_person_name' => $request->branch_contact_person_name,
            'branch_contact_person_mobile' => $request->branch_contact_person_mobile,
        ]);

        PaymentType::create([
            'name' => PaymentType::$TYPE_CASH,
            'account_no' => '',
            'pharmacy_branch_id' => $shop->id,
        ]);

        (new User())->createUser([
            'name' => $request->client_name,
            'pharmacy_branch_id' => $shop->id,
            'pharmacy_id' => $client->id,
            'user_type' => 'ADMIN',
            'user_mobile' => $request->branch_mobile,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        return response()->json([
            'status' => true,
            'message' => "Client added successfully!",
            'data' => $client
        ], 201);
    }

    private function deleteImageIfExists($path)
    {
        if (!$path) return;

        $fullPath = base_path($path);
        
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    public function updateShop(Request $request, $branchId)
    {
        try {
            $shop = PharmacyBranch::find($branchId);

            if (!$shop) {
                return response()->json([
                    'status' => false,
                    'message' => 'Branch not found'
                ], 404);
            }

            // -----------------------------
            // UPDATE IMAGE IF PROVIDED
            // -----------------------------
            if ($request->branch_image) {

                // Delete old image
                $this->deleteImageIfExists($shop->branch_image);

                // Save new image
                $imagePath = $this->saveLogo($request->branch_image, 'branch');
                $shop->branch_image = $imagePath;
                $shop->save();
            }

            $client = Pharmacy::find($shop->pharmacy_id);


            // -----------------------------
            // UPDATE SHOP (Branch)
            // -----------------------------
            $shop->branch_name = $request->branch_name ?? $shop->branch_name;
            $shop->branch_city = $request->branch_city ?? $shop->branch_city;
            $shop->branch_area = $request->branch_area ?? $shop->branch_area;
            $shop->branch_full_address = $request->branch_full_address ?? $shop->branch_full_address;
            $shop->branch_mobile = $request->branch_mobile ?? $shop->branch_mobile;
            $shop->branch_contact_person_name = $request->branch_contact_person_name ?? $shop->branch_contact_person_name;
            $shop->branch_contact_person_mobile = $request->branch_contact_person_mobile ?? $shop->branch_contact_person_mobile;
            $shop->save();

            // -----------------------------
            // UPDATE PHARMACY OWNER INFO
            // -----------------------------
            if ($client) {
                $client->pharmacy_shop_name = $request->client_name ?? $client->pharmacy_shop_name;
                $client->pharmacy_shop_owner_name = $request->owner_name ?? $client->pharmacy_shop_owner_name;
                $client->pharmacy_shop_licence_no = $request->licence_no ?? $client->pharmacy_shop_licence_no;
                $client->pharmacy_shop_branch_owner_nid = $request->owner_nid ?? $client->pharmacy_shop_branch_owner_nid;
                $client->save();
            }

            // -----------------------------
            // UPDATE USER
            // -----------------------------
            $user = User::where('pharmacy_branch_id', $shop->id)->first();

            if ($user) {
                $user->name = $request->client_name ?? $user->name;
                $user->user_mobile = $request->branch_mobile ?? $user->user_mobile;
                $user->email = $request->email ?? $user->email;

                if ($request->password) {
                    $user->password = app('hash')->make($request->password);
                }

                $user->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Shop updated successfully!',
                'data' => $shop
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'data'   => 'Something went wrong!',
                'status' => false,
                'error'  => $e->getMessage(),
            ], 500);
        }
    }

    private function saveLogo($base64, $folder = 'logo')
    {
        if (!$base64) return null;

        // Remove data:image prefix
        if (str_starts_with($base64, 'data:image')) {
            [$meta, $base64] = explode(',', $base64);
        }

        $binaryData = base64_decode($base64);

        // Detect extension
        $extension = 'png';
        if (isset($meta)) {
            if (str_contains($meta, 'jpeg')) $extension = 'jpg';
            if (str_contains($meta, 'svg')) $extension = 'svg';
        }

        // Unique name
        $fileName = uniqid() . '.' . $extension;

        // Public-facing path (saved in DB)
        $relativePath = "public/$folder";

        // Server filesystem path (where we save)
        $fullPath = base_path($relativePath);

        // Create folder if missing
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        // Save file
        file_put_contents("$fullPath/$fileName", $binaryData);

        // Return path to store in DB
        return "$relativePath/$fileName";
    }

    public function clear(Request $request)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('carts')->truncate();
        DB::table('cart_items')->truncate();
        DB::table('damage_items')->truncate();
        DB::table('notifications')->truncate();
        DB::table('orders')->truncate();
        DB::table('order_dues')->truncate();
        DB::table('order_items')->truncate();
        DB::table('products')->truncate();
        DB::table('sales')->truncate();
        DB::table('sale_items')->truncate();
        DB::table('subscriptions')->truncate();
        DB::table('brands')->truncate();
        DB::table('medicines')->truncate();
        DB::table('medicine_companies')->truncate();
        DB::table('medicine_types')->truncate();
        DB::table('migrations')->truncate();
        DB::table('mrs')->truncate();
        DB::table('payment_types')->truncate();
        DB::table('pharmacies')->truncate();
        DB::table('pharmacy_branches')->truncate();
        DB::table('pharmacy_mr_connections')->truncate();
        DB::table('stock_balances')->truncate();
        DB::table('stock_balance_items')->truncate();
        DB::table('users')->truncate();

        $seeder = new UsersTableSeeder();
        $seeder->run();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json([
            'status' => true,
            'message' => "Reset successfully"
        ], 409);
    }

    public function reset()
    {
        $users = [
            [
                'name' => 'AnalyticalJ',
                'email' => 'admin@analyticalj.com',
                'password' => Hash::make('aj$21'),
                'user_type' => User::ROLE_OWNER,
                'is_admin' => true
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('secret'),
                'user_type' => User::ROLE_ADMIN,
                'is_admin' => false
            ],
            [
                'name' => 'Salesman',
                'email' => 'salesman@shop.com',
                'password' => Hash::make('secret'),
                'user_type' => User::ROLE_SALESMAN,
                'is_admin' => false
            ]
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']], // ✅ Check if already exists
                $data // ✅ Update if exists, create if not
            );
        }

        return response()->json([
            'status' => true,
            'message' => "Reset successfully"
        ], 200); // ✅ Correct status code
    }
    
    public function cleanDatabase($clientId)
    {
        try {
            DB::transaction(function () use ($clientId) {
                $this->cleanPurchaseOrders($clientId);
                $this->cleanSales($clientId);
                $this->cleanProductsAndMedicines($clientId);
            });

            return response()->json([
                'status' => true,
                'message' => 'Database cleaned successfully.'
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Failed to clean database.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function cleanProductsAndMedicines($clientId)
    {
        // Get product IDs and related medicine IDs
        $productData = DB::table('products')
            ->where('pharmacy_branch_id', $clientId)
            ->select('id', 'medicine_id')
            ->get();

        $productIds  = $productData->pluck('id');
        $medicineIds = $productData->pluck('medicine_id');

        // Delete products first
        if ($productIds->isNotEmpty()) {
            DB::table('products')->whereIn('id', $productIds)->delete();
        }

        // Delete medicines only belonging to this branch
        if ($medicineIds->isNotEmpty()) {
            DB::table('medicines')->whereIn('id', $medicineIds)->delete();
        }
    }

    public function cleanPurchaseOrders($clientId)
    {
        // Get all purchase order IDs
        $orderIds = DB::table('orders')
            ->where('pharmacy_branch_id', $clientId)
            ->pluck('id');

        if ($orderIds->isNotEmpty()) {
            // Delete purchase order items
            DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
        }

        // Delete purchase orders
        DB::table('orders')->where('pharmacy_branch_id', $clientId)->delete();
    }

    public function cleanSales($clientId)
    {
        // Get sale IDs
        $saleIds = DB::table('sales')
            ->where('pharmacy_branch_id', $clientId)
            ->pluck('id');

        if ($saleIds->isNotEmpty()) {
            // Delete sale items
            DB::table('sale_items')->whereIn('sale_id', $saleIds)->delete();
        }

        // Delete sales
        DB::table('sales')->where('pharmacy_branch_id', $clientId)->delete();
    }

}
