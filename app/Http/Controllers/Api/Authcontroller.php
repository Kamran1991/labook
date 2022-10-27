<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Phone_confirmation;
use Illuminate\Support\Facades\Hash;
use \Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\ResponseObject;
use Illuminate\Support\Facades\Http;
use DB;

class Authcontroller extends Controller
{
    public function send_verification_code(Request $request)
    {
        $post_data = $request->json()->all();
        if (!$post_data['phone']) {
            return response()->json([ 'status'=> false, 'error'=> ['Phone is required']],200);
        } else {
            $res = User::where('phone', $post_data['phone'])->first();
            if ($res) {
                $type = 'login';
                $type_2 = '';
                $res = $this->send_sms($post_data['phone'], $type, $post_data);
            } else {
                $type = "register";
                $res = $this->send_sms($post_data['phone'], $type, $post_data);
                if ($res) {
                    if ($post_data['user_type']== 1 || $post_data['user_type']=='boss') {
                        $is_active = 1; 
                    } else {
                        $is_active = NULL; 
                    }
                    $user = User::create([
                        'phone' => $post_data['phone'],
                        'type' => $post_data['user_type'],
                        'access_token' => Hash::make(uniqid()),
                        'is_active' => $is_active
                    ]);
                }    
            }
            return response()->json([ 'status'=> true, 'message' => 'Code sent','type' => $type],200);
        }
    }

    public function login(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $validator = Validator::make($post_data, [
            'phone'=> 'required',
            'code' => 'required'
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json([ 'error'=> $errors],200);
        }
        $res = User::where('phone', $post_data['phone'])->first();

        if (!$res) {
            return response([
                'status' => false,
                'error' => 'Verification failed'
            ], 200);
        } else {
            $code = DB::table('phone_confirmation')->where('phone', $post_data['phone'])->where('code', $post_data['code'])->first();
            if ($code) {
                return response([
                    'status' => true,
                    'data' => $res,
                    'token' => $res->access_token
                ],200); 
            } else {
                return response([
                    'status' => false,
                    'error' => 'Verification failed'
                ], 200);
            }
              
        }
    }

    

    public function do_verification(Request $request)
    {
        $post_data = $request->json()->all();
        $verification_status = $this->verify_code($request);
        if ($verification_status) {
            DB::table('users')
                ->where('phone', $post_data['phone'])
                ->update(['is_verified' => 1]);
            return response()->json([
                    'status'=> true, 
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'error'=> ['Verification not matched']
            ],200);

        }
    }

    public function update_ecomerce_status(Request $request)
    {
        $post_data = $request->json()->all();
        $res = User::where('access_token', $post_data['access_token'])->first();
        if ($res) {
            DB::table('users')->where('id', $res->id)
                ->update(['ecomerce_platform_status' => $post_data['status']]);
            return response()->json([
                'status'=> true
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }

    private function send_sms($phone, $type, $post_data)
    {
        $result = DB::table('phone_confirmation')->where('phone', $phone)->first();
        if ($result) {
            $code = $result->code;
        } else {
            $code = rand(1000,9999);
        }
        if (!@$result->is_test_number) {
                $account_sid = 'ACe9b08c5128ae31b9e0a44fbcffcd56c6';
                $auth_token = '1d651dffc88ec77da2a71e0d3def8d86';
        
                $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/SMS/Messages";
               
                $from = "19377453506"; // twilio trial verified number
                $body = "Your verification code is ". $code;
                $data = array (
                    'From' => $from,
                    'To' => $phone,
                    'Body' => $body,
                );
                $post = http_build_query($data);
                $x = curl_init($url );
                curl_setopt($x, CURLOPT_POST, true);
                curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($x, CURLOPT_USERPWD, "$account_sid:$auth_token");
                curl_setopt($x, CURLOPT_POSTFIELDS, $post);
                $y = curl_exec($x);
                //print_r($y);
                curl_close($x);
               // return true;
            
        } else {
            $code = 1111;
        }
        
        
        if ($type == 'register') {
            $sms = DB::table('phone_confirmation')->insert([
                'phone' => $phone,
                'code' => $code,
                'ip' => @$post_data['ip'],
                'device_id' => @$post_data['device_id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        return true;
    }

    public function forgetpassword(Request $request)
    {
        $post_data = $request->json()->all();
        $res = User::where('phone', $post_data['phone'])->first();
    
        if ($res) {
            $type = 'forget';
            $res2 = $this->send_sms($post_data['phone'], $type, $post_data);
            return response([
                'status' => true,
                'access_token' => $res->access_token
            ],200); 
        } else {
            return response([
                'status' => false,
                'error' => 'Invalid Phone Number'
            ], 200);
        }
    }

    public function reset_password(Request $request)
    {
        $post_data = $request->json()->all();
        $res = User::where('access_token', $post_data['access_token'])->first();
        if ($res) {
            DB::table('users')->where('id', $res->id)
                ->update(['password' => Hash::make($post_data['password'])]);
            return response([
                'status' => true
            ],200); 
        } else {
            return response([
                'status' => false,
                'error' => 'Invalid Phone Number'
            ], 200);
        }
    }
    public function logout(Request $request)
    {
        $post_data = $request->json()->all();
        if (!$post_data['user_id']) {
            return response()->json([ 'status'=> false, 'error'=> ['User Id is required']],200);
        }
        DB::table('users')->where('id', $post_data['user_id'])->update(['loggedout_at' => date('Y-m-d H:i:s')]);
        return response([
            'status' => true
        ],200); 
    }

    private function verify_code($data)
    {
        $result = DB::table('phone_confirmation')->where('phone', $data['phone'])->where('code', $data['code'])->exists();
        if ($result) 
            return 1;
        else 
            return 0;    
    }

    private function verify_phone($phone)
    {
        $result = DB::table('phone_confirmation')->where('phone', $phone)->exists();
        if ($result) 
            return 1;
        else 
            return 0;
    }
}
