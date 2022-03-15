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
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SubscriptionController extends Controller
{
  private $user;
  public function __construct(Request $request) {
    $this->user = $request->auth;
  }

  public function subscription(Request $request) {
    $user = $request->auth;
    $data = $request->all();

    $data['pharmacy_branch_id'] = $user->pharmacy_branch_id;
    $branch = DB::table('pharmacy_branches')->where('id', $user->pharmacy_branch_id)->first();

    $data['subscription_count'] = $branch->subscription_count;
    $response = $this->subscriptionRequest($data);
    $msg = '';

    if($response->status) {
      $updateData['subscription_period'] = $response->data->subscription_period;
      DB::table('pharmacy_branches')->where('id', $user->pharmacy_branch_id)->update($updateData);

      $input = array(
        'pharmacy_id' =>  $user->pharmacy_id ?? 0,
        'pharmacy_branch_id' =>  $user->pharmacy_branch_id,
        'coupon_code' => $data['coupon_code'],
        'coupon_type' => $data['coupon_type'],
        'status' => 'USED',
        'apply_date' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
      );
      $subscription = DB::table('subscriptions')->insert($input);

      return response()->json(['status'=>true, 'message'=>$response->message]);

    }
    return response()->json(['status'=>false, 'message'=>$response->message]);
  }

  public function subscriptionResponse() {
    $status = false;
    $msg = '';

    $data = json_decode(file_get_contents('php://input'), true);

    $coupon = DB::table('subscriptions')
    ->where('coupon_type', $data['coupon_type'])
    ->where('coupon_code', $data['coupon_code'])
    ->first();
    $subscription_period = 0;
    if($coupon) {
      if($coupon->status == 'USED') {
        $msg = 'Already used this coupon.';
      }else{
        $status = true;
        DB::table('subscriptions')->where('id',$coupon->id)->update(['status'=>'USED', 'apply_date'=>date('Y-m-d H:i:s')]);
        $branch = DB::table('pharmacy_branches')->where('id', $data['pharmacy_branch_id'])->first();
        if($branch) {
          if($coupon->coupon_type == '1MONTH') {
            $subscription_period = $branch->subscription_period + 30;
          } else if($coupon->coupon_type == '3MONTH') {
            $subscription_period = $branch->subscription_period + 30 * 3;
          } else if($coupon->coupon_type == '6MONTH') {
            $subscription_period = $branch->subscription_period + 30 * 6;
          } else if($coupon->coupon_type == '1YEAR') {
            $subscription_period = $branch->subscription_period + 360;
          }
          $updateData['subscription_count'] = $data['subscription_count'];
          $updateData['subscription_period'] = $subscription_period;
          DB::table('pharmacy_branches')->where('id', $data['pharmacy_branch_id'])->update($updateData);
        }
      }
    }else{
      $msg = 'Invalid coupon.';
    }
    return response()->json(['status'=>$status, 'message'=>$msg, 'data'=>['subscription_period' => $subscription_period]]);
  }

  public function subscriptionRequest($data) {
    $curl = curl_init();
    //dd(json_encode($data));

    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://43.225.151.252:9898/api/apply-subscription",
        //CURLOPT_URL => "http://103.23.41.189:99/api/subscription-response",
        // CURLOPT_URL => "http://localhost/spe_api/api/subscription-response",
        // CURLOPT_URL => "http://54.214.203.243:91/data_sync",
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
      return false;
        // echo "cURL Error #:" . $err;
    } else {
        $response = json_decode($response);
        return $response;
  }
}

  public function subscriptionPlan(Request $request) {
    $user = $request->auth;
    $subscription = DB::table('pharmacy_branches')->where('id', $user->pharmacy_branch_id)->first();
    if($subscription) {
      $data['subscription_period'] = $subscription->subscription_period;
      $data['subscription_count'] = $subscription->subscription_count;

      return response()->json(['status'=>true, 'data'=>$data]);
    }
    return response()->json(['status'=>false, 'message'=> 'Subscription Plan not found!']);
  }

  public function subscriptionCount(Request $request) {
    $user = $request->auth;
    $subscription = DB::table('pharmacy_branches')->where('id', $user->pharmacy_branch_id)->first();
    if($subscription) {
      if(!empty($request->count)) {
        DB::table('pharmacy_branches')->where('id', $user->pharmacy_branch_id)->update(['subscription_count'=>$request->count]);
      }
      return response()->json(['status'=>true]);
    }
    return response()->json(['status'=>false, 'message'=> 'Subscription Plan not found!']);
  }

  public function subscriptionCoupon() {
    $coupon = array();
    for($i=1; $i<=12; $i++) {
      $coupon[] = $this->_randomString(16);
    }
    foreach ($coupon as $key => $value) {
      $input = array(
        'pharmacy_id' => $pharmacy_id ?? 0,
        'pharmacy_branch_id' => $pharmacy_branch_id ?? 0,
        'coupon_code' => $value,
        'coupon_type' => '1MONTH',
      );
      DB::table('subscriptions')->insert($input);
    }
    return response()->json($coupon);
  }

  public function getSubscriptions() {
    $coupons = DB::table('subscriptions')->get();
    $data['coupons'] = $coupons;
    $subscription = DB::table('pharmacy_branches')->where('id', $this->user->pharmacy_branch_id)->first();
    $data['subscription_period'] = $subscription->subscription_period;
    $data['subscription_count'] = $subscription->subscription_count;

    return response()->json(['status'=>false, 'data'=> $data]);
  }

}
