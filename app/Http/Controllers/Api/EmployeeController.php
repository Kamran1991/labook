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

class EmployeeController extends Controller
{
    public function accept_invitation(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'code'=> 'required',
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }
        
        $employee = DB::table('employee')->where('invite_code', $post_data['code'])->first();
        if ($employee) {
            DB::table('employee')->where('id', $employee->id)->update(['invite_status'=> 1, 'is_active'=> 1]);
            DB::table('users')->where('employee_id', $employee->id)->update(['is_active' => 1]);
            return response()->json([
                'status'=> true,
                'data'=> ['msg' => 'Invitation accepted']
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['msg' => 'Internal Error']
            ],200);
        }
    }
    public function upload_selfie(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id'=> 'required',
            'selfie' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }

        $data = [
            'business_id' => $post_data['business_id'],
            'employee_id' => $post_data['employee_id'],
            'selfie' => $post_data['selfie'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $selfie_id = DB::table('selfies')->insertGetId($data);
        return response()->json([
            'status'=> true,
            'data'=> ['selfie_id' => $selfie_id]
        ],200);
    }

    public function employee_info($employee_id) 
    {
        $employee = DB::table('employee')
                ->join('businesses', 'businesses.id', '=', 'employee.business_id')
                ->where('employee.id', $employee_id)
                ->select('employee.*', 'businesses.enable_in_out', 'businesses.attendence_verification')
                ->get();
        if ($employee) {
            return response()->json([
                'status'=> true,
                'data'=> ['employee' => $employee]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['employee' => []]
            ],200);
        }
    }

    public function employee_info_edit(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required',
            'name' => 'required',
            'phone' => 'required'
            
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }

        $data = [
            'name' => $post_data['name'],
            'phone' => $post_data['phone'],
            'address' => $post_data['address'],
            'position' => $post_data['position'],
            'image' => $post_data['image'],
            'dob' => $post_data['dob'],
            'working_type' => $post_data['working_type'],
            "id_card" => $post_data['id_card'],
            "join_date" => $post_data['join_date']
        ];
        DB::table('employee')->where('id', $post_data['employee_id'])->update($data);

        return response()->json([
            'status'=> true
        ],200);
    }

    public function punchin(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required',
            'punch_time' => 'required',
            'punch_date' => 'required',
            //'selfie' => 'required',
            //'location' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }

        $data = [
            'employee_id' => $post_data['employee_id'],
            'type' => 1,
            'punch_in_time' => $post_data['punch_time'],
            'punch_date' => $post_data['punch_date'],
            'location' => $post_data['location'],
            'selfie' => $post_data['selfie'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $attendence_id = DB::table('attendence')->insertGetId($data);

        return response()->json([
            'status'=> true,
            'data'=> ['attendence_id' => $attendence_id]
        ],200);
    }

    public function punchout(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required',
            'punch_time' => 'required',
            'punch_date' => 'required',
            //'selfie' => 'required',
            //'location' => 'required',
            'attendence_id' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }

        $data = [
            'employee_id' => $post_data['employee_id'],
            'type' => 2,
            'punch_in_time' => $post_data['punch_time'],
            'punch_date' => $post_data['punch_date'],
            'location' => $post_data['location'],
            'selfie' => $post_data['selfie'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $attendence_id = DB::table('attendence')->insertGetId($data);
        $emp_info = DB::table('employee')->where('id', $post_data['employee_id'])->first();

        $arr = [
            'punchout_time' => $post_data['punch_time'],
            'punchout_selfie' => $post_data['selfie'],
            'punchout_location' => $post_data['location'],
        ]; 
        if ($emp_info->attendence_settings== 1) {
            $arr['approved_status'] = 1;
            $arr['absent_present'] = 1;
        }
        
        DB::table("attendence")->where('id', $post_data['attendence_id'])->update(
            $arr
        );

        

        return response()->json([
            'status'=> true,
            'data'=> ['attendence_id' => $attendence_id]
        ],200);
    }
    public function attendence_list($employee_id) 
    {
        $attendance = DB::table('attendence')->where('employee_id', $employee_id)->get();
        if ($attendance) {
            return response()->json([
                'status'=> true,
                'data'=> ['attendance' => $attendance]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['attendance' => []]
            ],200);
        }
    }

    public function paycycle(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }

        $emp_info = DB::table('employee')->where('id', $post_data['employee_id'])->first();
        $cur_month = date("M");
        $next_month = date('M', strtotime('+1 month'));
        if ($emp_info->paid_every == 1) {
            $label = '02 '.$cur_month.' - '.'01 '.$next_month; 
        } else{
            $last_day_this_month  = date('t');
            $label = '01 '.$cur_month.' - '.$last_day_this_month.' '.$cur_month; 
        }
        $employee_balance = DB::table("employee_balance")->where('employee_id', $post_data['employee_id'])->first();
        $employee_unpaid_balance = $employee_balance->balance;
        $employee_transaction_history = DB::table('employee_transactions')->where('employee_id', $post_data['employee_id'])->select('amount','date','payment_type')->get();
        return response()->json([
            'status'=> true,
            'data'=> ['label' => $label, 'paycycle_amount' => $employee_unpaid_balance, 'transactions' => $employee_transaction_history]
        ],200);
    }

    public function home(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }

        $emp_info = DB::table('employee')->where('id', $post_data['employee_id'])->first();
        $cur_month = date("M");
        $next_month = date('M', strtotime('+1 month'));
        if ($emp_info->paid_every == 1) {
            $label = '02 '.$cur_month.' - '.'01 '.$next_month;
            $next_pay_date =  '01 '.$next_month;
        } else{
            $last_day_this_month  = date('t');
            $label = '01 '.$cur_month.' - '.$last_day_this_month.' '.$cur_month; 
            $next_pay_date =  $last_day_this_month.' '.$cur_month;
        }
        $employee_balance = DB::table("employee_balance")->where('employee_id', $post_data['employee_id'])->first();
        $employee_unpaid_balance = $employee_balance->balance;

        $sql = "select count(absent_present) as total_present from attendence where MONTH(punch_date)=MONTH(CURRENT_DATE()) and absent_present =1 and employee_id='".$post_data['employee_id']."'";

        $present_obj = DB::select($sql);

        $sql2 = "select * from attendence where employee_id = '".$post_data['employee_id']."' and DATE(punch_date)=DATE(CURRENT_DATE());";
        $is_present = DB::select($sql);

        return response()->json([
            'status'=> true,
            'data'=> ['label' => $label, 
                    'paycycle_amount' => $employee_unpaid_balance,
                    'next_pay_date' => $next_pay_date,
                    'total_present_days' => $present_obj[0]->total_present,
                    'is_present' => $is_present? 1: ''
            ]
        ],200);
    } 
    public function getlastattendencestatus($employee_id)
    {
        $attendance = DB::table('attendence')->where('employee_id', $employee_id)->orderBy('id','desc')->first();
        if ($attendance) {
            return response()->json([
                'status'=> true,
                'data'=> ['attendance' => $attendance]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['attendance' => []]
            ],200);
        }
    }

    public function reimbursment($employee_id)
    {
        $reimbursement = DB::table('reimbursement')->where('employee_id', $employee_id)->get();
        if ($reimbursement) {
            return response()->json([
                'status'=> true,
                'data'=> ['reimbursement' => $reimbursement]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['reimbursement' => []]
            ],200);
        }
    }

    public function addreimbursment(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required',
            'amount' => 'required',
            'type' => 'required',
            'date' => 'required'
        ];
        $validator = Validator::make($post_data,
            $rules
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->getMessages() as $item) {
                array_push($errors, $item);
            }
            return response()->json(['status'=> false, 'error'=> $errors],200);
        }

        $data = [
            'employee_id' => $post_data['employee_id'],
            'type' => $post_data['type'],
            'amount' => $post_data['amount'],
            'date' => $post_data['date'],
            'images' => $post_data['images'],
            'note' => $post_data['note'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $reimbursement_id = DB::table('reimbursement')->insertGetId($data);

        return response()->json([
            'status'=> true,
            'data'=> ['reimbursement_id' => $reimbursement_id]
        ],200);
    }
}