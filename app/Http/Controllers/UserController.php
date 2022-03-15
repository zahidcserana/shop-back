<?php

namespace App\Http\Controllers;

use App\Models\PharmacyMrConnection;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private $user;

    public function __construct(Request $request)
    {
        $this->user = $request->auth;
    }

    public function showAllUsers(Request $request)
    {
        $user = $request->auth;
        $users = User::where('pharmacy_branch_id', $user->pharmacy_branch_id)->get();
        return response()->json($users);
    }

    public function create(Request $request)
    {
        $user = $request->auth;
        $this->validate($request, [
            'name' => 'required',
            'email' => 'unique:users,email',
        ]);
        $data = $request->all();
        $data['pharmacy_branch_id'] = $user->pharmacy_branch_id;
        $data['pharmacy_id'] = $user->pharmacy_id;
        $userModel = new User();
        $user = $userModel->create($data);

        $users = User::where('pharmacy_branch_id', $user->pharmacy_branch_id)->get();
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function password(Request $request)
    {
        $this->validate($request, [
            'password' => 'required|confirmed|min:6'
        ]);
        $data = $request->all();
        $user = User::findOrFail($data['user']);
        $input = array(
            'password' => Hash::make($data['password']),
            'updated_at' => date('Y-m-d H:i:s')
        );
        $user->update($input);

        return response()->json(['success' => true]);
    }

    public function adminCheck(Request $request)
    {
        $status = false;
        if (!$request->email || !$request->password) {
            return response()->json(['status' => $status], 200);
        }
        $user = User::where('email', $request->email)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            $status = true;
        }
        return response()->json(['status' => $status], 200);
    }

    public function sendPushNotification($title, $messageBody, $message, $imageUrl, $urlNeedsToOpen, $sendType)
    {
        $res = array();

        $res['data']['title'] = isset($title) ? $title : '';
        $res['data']['is_background'] = true;
        $res['data']['message'] = isset($messageBody) ? $messageBody : '';
        $res['data']['imageUrl'] = isset($imageUrl) ? $imageUrl : '';
        $res['data']['urlNeedsToOpen'] = isset($urlNeedsToOpen) ? $urlNeedsToOpen : '';
        $res['data']['clickType'] = 2;
        $res['data']['timestamp'] = date('Y-m-d H:i:s');
        $res['data']['notificationData'] = $message;


        $firebaseApiKey = 'AIzaSyDbGByXy5gnQOCrAmJH1dG9heDq5U4peZk';

        $firebaseIds = array(
            'eKPcwtO1nHg:APA91bHZV0bKpTkE-iWGKMGQOkJVdSlhsqcNKfOf8Tsvn1qhvGdASloAsUEVko8l4xOQnIG9dCfrDdsMxV1GAMO0h7o8B759BqgGiyarLndADaSf_Mrj_hwN61xVHQmsk7N4PYOVkDNj'
        );


        if ($sendType == 1) { // Send to individual ids

            $fields = array(
                'registration_ids' => $firebaseIds,
                'data' => $res
            );
        } else { // Send to all

            $fields = array(
                'to' => '/topics/global',
                'data' => $res
            );
        }

        $headers = array(
            'Authorization: key=' . $firebaseApiKey,
            'Content-Type: application/json'
        );

        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        // Execute post
        $result = curl_exec($ch);

        if ($result === FALSE) {
            $result = curl_error($ch);
        }

        // Close connection
        curl_close($ch);

        return $result;
    }

    public function verifyUser(Request $request)
    {
        $this->validate($request, [
            'verification_pin' => 'required',
            'user_mobile' => 'required',
        ]);

        $user = DB::table('users')
            ->where('user_mobile', $request->user_mobile)
            ->where('verification_pin', $request->verification_pin)
            ->first();

        /** * Login credential * */
        return response()->json([
            'status' => 200,
            'message' => 'Login Successful',
            'data' => [
                'token' => $this->jwt($user),
                'email' => $user->email,
                'user_type' => $user->user_type,
            ]
        ], 200);
        /** # Login credential # */

        return response()->json($user);
    }
    protected function jwt($user)
    {
        $payload = [
            'iss' => "lumen-jwt", // Issuer of the token
            'sub' => $user->id, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' => time() + 60 * 60 * 24 * 30 * 12 // Expiration time
        ];

        // As you can see we are passing `JWT_SECRET` as the second parameter that will
        // be used to decode the token in the future.

        return JWT::encode($payload, env('JWT_SECRET'));
    }

    public function getVerificationCode(Request $request)
    {
        $this->validate($request, [
            'user_mobile' => 'required',
        ]);

        $userCode = DB::table('users')
            ->where('user_mobile', $request->user_mobile)
            ->value('verification_pin');

        return response()->json(['code' => $userCode]);
    }

    public function update($id, Request $request)
    {
        $user = User::findOrFail($id);
        $user->update($request->all());

        $user = $request->auth;
        $users = User::where('pharmacy_branch_id', $user->pharmacy_branch_id)->get();

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function mrConnection(Request $request)
    {
        $phamracy_mr_connection = PharmacyMrConnection::create($request->all());

        return response()->json(['success' => true, 'data' => $phamracy_mr_connection]);
    }

    public function test()
    {
        dd('ok');
    }

    public function destroy($userId)
    {
        if (User::destroy($userId)) {
            $users = User::where('pharmacy_branch_id', $this->user->pharmacy_branch_id)->get();

            return response()->json(['status' => true, 'data' => $users]);
        }
        return response()->json(['status' => false]);
    }
}
