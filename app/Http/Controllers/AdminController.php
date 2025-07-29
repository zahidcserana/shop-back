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

        if (!$exist) {
            $client = Pharmacy::create([
                'pharmacy_shop_code' => $request->pharmacy_shop_code,
                'pharmacy_shop_name' => $request->client_name,
                'pharmacy_shop_owner_name' => $request->owner_name,
                'pharmacy_shop_licence_no' => $request->licence_no,
                'pharmacy_shop_branch_owner_nid' => $request->owner_nid,
            ]);

            $shop = PharmacyBranch::create([
                'pharmacy_id' => $client->id,
                'branch_name' => $request->branch_name,
                'branch_city' => $request->branch_city,
                'branch_area' => $request->branch_area,
                'branch_full_address' => $request->branch_full_address,
                'branch_mobile' => $request->branch_mobile,
                'branch_contact_person_name' => $request->branch_contact_person_name,
                'branch_contact_person_mobile' => $request->branch_contact_person_mobile,
            ]);

            $shop = PaymentType::create([
                'name' => PaymentType::$TYPE_CASH,
                'account_no' => '',
                'pharmacy_branch_id' => $shop->id,
            ]);

            $userData =[
                'name' => $request->client_name,
                'pharmacy_branch_id' => $shop->id,
                'pharmacy_id' => $client->id,
                'user_type' => 'ADMIN',
                'user_mobile' => $request->branch_mobile,
                'email' => $request->email,
                'password' => $request->password,
            ];
            $userModel = new User();
            $userModel->createUser($userData);

            return response()->json([
                'status' => true,
                'message' => "Client added successfully!",
                'data' => $client
            ], 201);
        }

        return response()->json([
            'status' => false,
            'message' => "Duplicate shop code"
        ], 409); // 409 Conflict is better than 302
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

}
