<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Mail;
use App\Models\Phone_confirmation;
use Illuminate\Support\Facades\Hash;
use \Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\ResponseObject;
use Illuminate\Support\Facades\Http;
use DB;

class Bosscontroller extends Controller
{
    public function addbusiness(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'user_id'=> 'required',
            'name' => 'required',
            'industry_id' => 'required'
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
            'boss_id' => $post_data['user_id'],
            'name' => $post_data['name'],
            'industry_id' => $post_data['industry_id'],
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $business_id = DB::table('businesses')->insertGetId($data);
        return response()->json([
            'status'=> true,
            'data'=> ['business_id' => $business_id]
        ],200);
    }

    public function adjust_salary(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id'=> 'required',
            'business_id' => 'required',
            'amount' => 'required',
            'type_name' => 'required',
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
            'amount' => $post_data['amount'],
            'employee_id' => $post_data['employee_id'],
            'business_id' => $post_data['business_id'],
            'note' => $post_data['note'],
            'date' => $post_data['date'],
            'type_details' => $post_data['type_name'],
            'payment_type' => 'salary_adjustments',
            'additional_fields' => $post_data['additional_fields'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $t_id = DB::table('employee_transactions')->insertGetId($data);
        $emp_balance = DB::table("employee_balance")->where('employee_id', $post_data['employee_id'])->first();
        $res = DB::table("employee_balance")->where('employee_id', $post_data['employee_id'])->update([ 'balance' => $emp_balance->balance + ($post_data['amount'])]);
        return response()->json([
            'status'=> true,
            'data'=> ['transaction_id' => $t_id]
        ],200);
    }

    public function get_salary_adjustments(Request $request) 
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id'=> 'required'
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

        $employee_id = $post_data['employee_id'];
        $sql = "select * from employee_transactions where employee_id='".$employee_id."' and payment_type = 'salary_adjustments' ";
        if ($post_data['start_date'] && $post_data['end_date']) {
            $sql = $sql." and `date` between '".$post_data['start_date']."' and '".$post_data['end_date']."' ";
        }
        $adjustments = DB::select($sql);
        //$adjustments = DB::table('employee_transactions')->where('employee_id', $employee_id)->where('payment_type', 'salary_adjustments')->get();
        if ($adjustments) {
            return response()->json([
                'status'=> true,
                'data'=> ['adjustments' => $adjustments]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['adjustments' => []]
            ],200);
        }
    }

    public function business_details($id) 
    {
        $business = DB::table('businesses')->where('id', $id)->first();
        if ($business) {
            return response()->json([
                'status'=> true,
                'data'=> ['business_details' => $business]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['business_details' => []]
            ],200);
        }
    }

    public function upcoming_pay($boss_id, $business_id)
    {
        if (!$boss_id) {
            return response()->json(['status'=> false, 'error'=> ['Boss id not provided']],200);
        }

        if (!$business_id) {
            return response()->json(['status'=> false, 'error'=> ['Business id not provided']],200);
        }

        $sql = "select e.id, e.name, e.paid_every, eb.balance  from employee e  join employee_balance eb on e.id=eb.employee_id where e.business_id = '".$business_id."'";
        $res = DB::select($sql);
        $upcoming_pay = [];
        $cur_month = date("M");
        $next_month = date('M', strtotime('+1 month'));
        $last_day_this_month  = date('t');
        foreach ($res as $key=>$r) {
            $upcoming_pay[$key]['employee_id'] = $r->id;
            $upcoming_pay[$key]['name'] = $r->name;
            $upcoming_pay[$key]['balance'] = $r->balance;
            if ($r->paid_every ==1) {
                $label = '01 '.$next_month;
            } else {
                $label = $last_day_this_month.' '.$cur_month; 
            }
            $upcoming_pay[$key]['date_lable'] = $label;
        }
        if ($res) {
            return response()->json([
                'status'=> true,
                'data'=> ['upcoming_pay' => $upcoming_pay]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['upcoming_pay' => []]
            ],200);
        }
    }

    public function editbusiness(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'user_id'=> 'required',
            'name' => 'required',
            'industry_id' => 'required',
            'business_id' => 'required'
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
            'boss_id' => $post_data['user_id'],
            'name' => $post_data['name'],
            'is_active' => 1,
            'industry_id' => $post_data['industry_id'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $business_id = DB::table('businesses')->where('id',$post_data['business_id'])->update($data);
        return response()->json([
            'status'=> true,
            'data'=> ['business_id' => $business_id]
        ],200);
    }

    public function editbusinessprofile(Request $request) 
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'user_id'=> 'required',
            'name' => 'required',
            'industry_id' => 'required',
            'business_id' => 'required'
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
            'boss_id' => $post_data['user_id'],
            'name' => $post_data['name'],
            'is_active' => 1,
            'owner_name' => $post_data['owner_name'],
            'logo' => $post_data['logo'],
            'phone' => $post_data['phone'],
            'address' => $post_data['address'],
            'email' => $post_data['email'],
            'industry_id' => $post_data['industry_id'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $business_id = DB::table('businesses')->where('id',$post_data['business_id'])->update($data);
        return response()->json([
            'status'=> true,
            'data'=> ['business_id' => $business_id]
        ],200);
    }

    public function businesslist($boss_id) 
    {
        $businesses = DB::table('businesses')->where('boss_id', $boss_id)->get();
        if ($businesses) {
            return response()->json([
                'status'=> true,
                'data'=> ['businesses' => $businesses]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['businesses' => []]
            ],200);
        }
    }

    public function businessprofiledetails($boss_id, $business_id) 
    {
        $businesses = DB::table('businesses')->where('id', $business_id)->get();
        if ($businesses) {
            return response()->json([
                'status'=> true,
                'data'=> ['businesses' => $businesses]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['businesses' => []]
            ],200);
        }
    }

    public function enableinout($business_id, $status)
    {
        $businesses = DB::table('businesses')->where('id', $business_id)->update(['enable_in_out' => $status]);
        if ($businesses) {
            return response()->json([
                'status'=> true,
                'data'=> ['businesses' => $businesses]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['businesses' => []]
            ],200);
        }
    }

    public function add_staff(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();

        $rules = [
            'user_id'=> 'required',
            'business_id' => 'required',
            'name' => 'required',
            'phone' => 'required|unique:employee',
            'salary' => 'required',
            'period' => 'required',
            'paid_every' => 'required',
            'working_hour_start' => 'required',
            'working_hour_end' => 'required',
            'working_days' => 'required'
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
            'boss_id' => $post_data['user_id'],
            'business_id' => $post_data['business_id'],
            'name' => $post_data['name'],
            'phone' => $post_data['phone'],
            'salary' => $post_data['salary'],
            'period' => $post_data['period'],
            'paid_every' => $post_data['paid_every'],
            'working_hour' => $post_data['working_hour_start'],
            'working_end' => $post_data['working_hour_end'],
            'working_days' => $post_data['working_days'],
            'allowance' => $post_data['allowance'],
            'created_at' => date('Y-m-d H:i:s'),
            'deduct_salary' => $post_data['deduct_salary']
            //'is_active' => 1,
        ];
        $employee_id = DB::table('employee')->insertGetId($data);

        if ($post_data['default_working_schedule']) {
            $has_schedule = DB::table('default_schedule')->where('business_id', $post_data['business_id'])->first();
            $schedule = [
                'boss_id' => $post_data['user_id'],
                'business_id' => $post_data['business_id'],
                'working_hour_start' => $post_data['working_hour_start'],
                'working_hour_end' => $post_data['working_hour_end'],
                'working_days' => $post_data['working_days']
            ];
            if ($has_schedule) {
                DB::table('default_schedule')->where('business_id', $post_data['business_id'])->update($schedule);
            } else {
                DB::table('default_schedule')->insert($schedule);
            }
        }
        $user_data = [
            'phone' => $post_data['phone'],
            'type' => 2,
            'access_token' => Hash::make(uniqid()),
            'employee_id' => $employee_id
        ];
        DB::table('users')->insert($user_data);
        return response()->json([
            'status'=> true,
            'data'=> ['employee_id' => $employee_id]
        ],200);
    }

    public function edit_staff(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required',
            'user_id'=> 'required',
            'name' => 'required',
            'phone' => 'required',
            'salary' => 'required',
            'period' => 'required',
            'paid_every' => 'required',
            'working_hour_start' => 'required',
            'working_hour_end' => 'required',
            'working_days' => 'required',
            
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
            'boss_id' => $post_data['user_id'],
            'name' => $post_data['name'],
            'phone' => $post_data['phone'],
            'salary' => $post_data['salary'],
            'period' => $post_data['period'],
            'paid_every' => $post_data['paid_every'],
            'working_hour' => $post_data['working_hour_start'],
            'working_end' => $post_data['working_hour_end'],
            'working_days' => $post_data['working_days'],
            'allowance' => $post_data['allowance'],
            'updated_at' => date('Y-m-d H:i:s'),
            'deduct_salary' => $post_data['deduct_salary']
            //'is_active' => 1,
        ];
        $employee_id = DB::table('employee')->where('id', $post_data['employee_id'])->update($data);

        if ($post_data['default_working_schedule']) {
            $has_schedule = DB::table('default_schedule')->where('business_id', $post_data['business_id'])->first();
            $schedule = [
                'boss_id' => $post_data['user_id'],
                'business_id' => $post_data['business_id'],
                'working_hour_start' => $post_data['working_hour_start'],
                'working_hour_end' => $post_data['working_hour_end'],
                'working_days' => $post_data['working_days']
            ];
            if ($has_schedule) {
                DB::table('default_schedule')->where('business_id', $post_data['business_id'])->update($schedule);
            } else {
                DB::table('default_schedule')->insert($schedule);
            }
        }

        return response()->json([
            'status'=> true
        ],200);
    }

    public function edit_staff_salary(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required',
            'salary' => 'required',
            'period' => 'required',
            'paid_every' => 'required',
            'working_hour_start' => 'required',
            'working_hour_end' => 'required',
            'working_days' => 'required',
            
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
           
            'salary' => $post_data['salary'],
            'period' => $post_data['period'],
            'paid_every' => $post_data['paid_every'],
            'working_hour' => $post_data['working_hour_start'],
            'working_end' => $post_data['working_hour_end'],
            'working_days' => $post_data['working_days'],
            'allowance' => $post_data['allowance'],
            'updated_at' => date('Y-m-d H:i:s'),
            'deduct_salary' => $post_data['deduct_salary']
            //'is_active' => 1,
        ];
        $employee_id = DB::table('employee')->where('id', $post_data['employee_id'])->update($data);

        if (@$post_data['default_working_schedule']) {
            $has_schedule = DB::table('default_schedule')->where('business_id', $post_data['business_id'])->first();
            $schedule = [
                'boss_id' => $post_data['boss_id'],
                'business_id' => $post_data['business_id'],
                'working_hour_start' => $post_data['working_hour_start'],
                'working_hour_end' => $post_data['working_hour_end'],
                'working_days' => $post_data['working_days']
            ];
            if ($has_schedule) {
                DB::table('default_schedule')->where('business_id', $post_data['business_id'])->update($schedule);
            } else {
                DB::table('default_schedule')->insert($schedule);
            }
        }

        return response()->json([
            'status'=> true
        ],200);
    }

    public function update_staff_status(Request $request) 
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required',
            'user_id'=> 'required',
            'status' => 'required'
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
        $employee_id = DB::table('employee')->where('id', $post_data['employee_id'])->update([ 'is_active' => $post_data['status']]);
        DB::table('users')->where('employee_id', $post_data['employee_id'])->update([ 'is_active' => $post_data['status']]);
        return response()->json([
            'status'=> true
        ],200);
    }

    public function staff_list($business_id) 
    {
        if (!$business_id) {
            return response()->json(['status'=> false, 'error'=> ['Business id not provided']],200);
        }
        $staff = DB::table('employee')->where('business_id', $business_id)->get();
        if ($staff) {
            return response()->json([
                'status'=> true,
                'data'=> ['stafflist' => $staff]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['staff' => []]
            ],200);
        }
    }

    public function deletestaff(Request $request)
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
        $staff = DB::table('employee')->where('id', $post_data['employee_id'])->delete();
        DB::table('users')->where('employee_id', $post_data['employee_id'])->delete();
        if ($staff) {
            return response()->json([
                'status'=> true
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }

    public function get_default_schedule($business_id)
    {
        if (!$business_id) {
            return response()->json(['status'=> false, 'error'=> ['Business id not provided']],200);
        }
        $schedule = DB::table('default_schedule')->where('business_id', $business_id)->get();
        if ($schedule) {
            return response()->json([
                'status'=> true,
                'data'=> ['default_schedule' => $schedule]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['default_schedule' => []]
            ],200);
        }
    }

    public function invite_all_staff($business_id) 
    {
        $staff_list = DB::table('employee')->where('business_id', $business_id)->get();
        if ($staff_list) {
            foreach ($staff_list as $key=> $staff) {
                $this->send_sms($staff->id, $staff->phone);
            } 
            return response()->json([
                'status'=> true,
                'data'=> ['message' => 'Invited']
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['message' => 'Internal Error']
            ],200);
        }
    }

    public function invite_staff($id) 
    {
        if (!$id) {
            return response()->json(['status'=> false, 'error'=> ['Staff id not provided']],200);
        }
        $staff = DB::table('employee')->where('id', $id)->first();
        if ($staff) {
            $this->send_sms($staff->id, $staff->phone);
            return response()->json([
                'status'=> true,
                'data'=> ['message' => 'Invited']
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['message' => 'Internal Error']
            ],200);
        }
    }

    public function change_invite_status($employee_id, $type)
    {
        if (!$employee_id) {
            return response()->json(['status'=> false, 'error'=> ['Staff id not provided']],200);
        }
        if (!$type) {
            return response()->json(['status'=> false, 'error'=> ['Invite status not provided']],200);
        }
        $staff = DB::table('employee')->where('id', $employee_id)->first();
        if ($staff) {
            DB::table('employee')->where('id', $employee_id)->update(['invite_status' => $type]);
            return response()->json([
                'status'=> true,
                'data'=> ['message' => 'Status Changed']
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['message' => 'Internal Error']
            ],200);
        }
    }

    public function attendence_reminder_settings(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'boss_id' => 'required',
            'business_id' => 'required',
            'time' => 'required'
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
        DB::table('attendence_reminder_settings')->where('boss_id', $post_data['boss_id'])->where('business_id', $post_data['business_id'])->delete();
        $arr = [
            'time' => $post_data['time'],
            'boss_id' => $post_data['boss_id'],
            'business_id' => $post_data['business_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        $settings_id = DB::table('attendence_reminder_settings')->insertGetId($arr);
        if ($settings_id) {
            return response()->json([
                'status'=> true,
                'data' => ['settings_id' => $settings_id]
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }

    public function get_attendence_reminder_settings($boss_id, $business_id)
    {
        if (!$boss_id) {
            return response()->json(['status'=> false, 'error'=> ['Boss id not provided']],200);
        }
        if (!$business_id) {
            return response()->json(['status'=> false, 'error'=> ['Business id not provided']],200);
        }
        $settings = DB::table('attendence_reminder_settings')->where('boss_id', $boss_id)->where('business_id', $business_id)->first();
        if ($settings) {
            return response()->json([
                'status'=> true,
                'data'=> ['settings' => $settings]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['settings' => []]
            ],200);
        }
    }

    public function verify_business(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'boss_id' => 'required',
            'business_id' => 'required',
            'status' => 'required',
            'name' => 'required',
            'id_image'=> 'required'
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

        $arr = [
            'is_verified' => $post_data['status'],
            'owner_name' => $post_data['name'],
            'id_image' => $post_data['id_image'],
            'selfie' => $post_data['selfie'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $res = DB::table('businesses')->where('id', $post_data['business_id'])->update($arr);
        if ($res) {
            return response()->json([
                'status'=> true
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }

    public function edit_attendence(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'boss_id' => 'required',
            'attendence_id' => 'required',
            'employee_id' => 'required',
            'absent_present' => 'required'
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

        $arr = [
            'absent_present' => $post_data['absent_present'],
            'punch_in_time'  => $post_data['punch_in'],
            'punchout_time'  => $post_data['punch_out'],
            'note' => $post_data['note']
        ];
        $res = DB::table('attendence')->where('id', $post_data['attendence_id'])->update($arr);
        if ($res) {
            return response()->json([
                'status'=> true
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }

    public function approve_attendence(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'boss_id' => 'required',
            'attendence_id' => 'required',
            'employee_id' => 'required',
            'status' => 'required'
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

        $arr = [
            'approved_status' => $post_data['status'],
            'approved_at' => date('Y-m-d H:i:s'),
            'approved_by' => $post_data['boss_id']
        ];
        $res = DB::table('attendence')->where('id', $post_data['attendence_id'])->update($arr);
        $this->process_employee_salary($post_data);
        if ($res) {
            return response()->json([
                'status'=> true
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }

    public function get_staff_profile($employee_id)
    {
        if (!$employee_id) {
            return response()->json(['status'=> false, 'error'=> ['Employee id not provided']],200);
        }
    
        $employee_info = DB::table('employee')->where('id', $employee_id)->first();
        if ($employee_info) {
            return response()->json([
                'status'=> true,
                'data'=> ['employee_info' => $employee_info]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['employee_info' => []]
            ],200);
        }
    }

    public function datewise_attendence_status(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'boss_id' => 'required',
            'employee_id' => 'required',
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
        $sql = "select * from attendence where employee_id = '".$post_data['employee_id']."' and MONTH(punch_date) = MONTH('".$post_data['date']."') and YEAR(punch_date) = YEAR('".$post_data['date']."') and type =1 and absent_present is not null;
        ";
        $res = DB::select($sql);
        $sql = "select * from attendence where employee_id = '".$post_data['employee_id']."' and MONTH(punch_date) = MONTH('".$post_data['date']."') and YEAR(punch_date) = YEAR('".$post_data['date']."') and type =1 and absent_present = 1;
        ";
        $present = DB::select($sql);
        $sql = "select * from attendence where employee_id = '".$post_data['employee_id']."' and MONTH(punch_date) = MONTH('".$post_data['date']."') and YEAR(punch_date) = YEAR('".$post_data['date']."') and type =1 and absent_present = 0;
        ";
        $absent = DB::select($sql);
        $sql = "select * from attendence where employee_id = '".$post_data['employee_id']."' and MONTH(punch_date) = MONTH('".$post_data['date']."') and YEAR(punch_date) = YEAR('".$post_data['date']."') and type =1 and absent_present = 3;
        ";
        $holiday = DB::select($sql);
        if ($res) {
            return response()->json([
                'status'=> true,
                'data' => ['attendence_list' => $res,
                    'total_present' => count($present),
                    'total_absent' => count($absent),
                    'total_halfday' => 0,
                    'total_holiday' => count($holiday),
                ]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data' => ['attendence_list' => []]
            ],200);
        }
    }

    public function get_staff_attendence_history($employee_id)
    {
        if (!$employee_id) {
            return response()->json(['status'=> false, 'error'=> ['Employee id not provided']],200);
        }
    
        $sql = "select * from attendence where employee_id = '".$employee_id."' order by punch_date;";
        $res = DB::select($sql);
        if ($res) {
            return response()->json([
                'status'=> true,
                'data'=> ['history' => $res]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['history' => []]
            ],200);
        }
    }

    public function create_invitation_link(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'boss_id' => 'required',
            'business_id' => 'required',
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

        $employee_info = DB::table('employee')->where('id', $post_data['employee_id'])->first();

        if ($employee_info->invite_link) {
            $link = $employee_info->invite_link;
        } else {
            $code = uniqid();
            $arr = [
                'invite_code' => $code,
                'invite_link' => 'https://labook.nestmart.co/invite/'.$code,
                'invite_status' => 1
            ];
            $res = DB::table('employee')->where('id', $post_data['employee_id'])->update($arr);
            $link = 'https://labook.nestmart.co/invite/'.$code;
        }
       
        if ($link) {
            return response()->json([
                'status'=> true,
                'data'=> ['invite_link' => $link]
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }
    public function paycycle_list($employee_id)
    {
        $sql = "select *, 
        case when MONTH(start_date) = MONTH(NOw()) && YEAR(start_date) = YEAR(NOw()) THEN 1 
        when paid_by is null THEN 2
        when paid_by is not null THEN 3
         END as paycycle_status from paycycles where employee_id = '".$employee_id."' order by start_date desc";
         //echo $sql;
        $paycyles = DB::select($sql);
        $new_paycycle = [];
        if ($paycyles) {
            foreach ($paycyles as $key=>$paycyle) {
                $new_paycycle[$key]['employee_id'] = $paycyle->employee_id;
                $new_paycycle[$key]['label'] = $paycyle->label;
                $new_paycycle[$key]['start_date'] = $paycyle->start_date;
                $new_paycycle[$key]['end_date'] = $paycyle->end_date;
                $new_paycycle[$key]['paycycle_status'] = $paycyle->paycycle_status;
                $res = DB::select("select sum(amount) as amt from employee_transactions where employee_id = '".$paycyle->employee_id."' and date BETWEEN '".$paycyle->start_date."' and '".$paycyle->end_date."'");
                $new_paycycle[$key]['paycycle_amount'] = $res ? $res[0]->amt : 0;
            }
            return response()->json([
                'status'=> true,
                'data'=> ['paycycle' => $new_paycycle]
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }

    private function process_employee_salary($data)
    {
        $employee_id = $data['employee_id'];
        $status = $data['status'];
        $employee_info = DB::table('employee')->where('id', $employee_id)->first();
        if ($employee_info) {
            $payment_type = 'salary_by_attendence';
            if (strtolower($employee_info->period) == 'weekly') {
                $amount = $employee_info->salary / 7;
            } else if (strtolower($employee_info->period) == 'daily') {
                $amount = $employee_info->salary / 1;
            } else if (strtolower($employee_info->period) == 'monthly') {
                $amount = $employee_info->salary / 30;
            } else {
                $sql = "select time_to_sec(timediff(punchout_time, punch_in_time )) / 3600 as total_hour from attendence where id ='".$post_data['attendence_id']."';";
                $res = DB::select($sql);
                if ($res) {
                    $total_hour = $res[0]->total_hour;
                    $amount = $employee_info->salary / $total_hour;
                } else {
                    $amount = 0;
                }
            }
            if ($data['status'] != 1 && $employee_info->deduct_salary) {
                $amount = -1 * $amount;
                $payment_type = 'deduct';
            }
            $arr = [
                'employee_id' => $data['employee_id'],
                'date' => date('Y-m-d'),
                'created_at' => date('Y-m-d H:i:s'),
                'amount' => $amount,
                'business_id' => $employee_info->business_id,
                'payment_type' => $payment_type
            ];
            DB::table('employee_transactions')->insert($arr);
            $balance_info = DB::table('employee_balance')->where('employee_id', $data['employee_id'])->first();
            if ($balance_info) {
                DB::table('employee_balance')->where('employee_id', $data['employee_id'])->update(['balance' => $balance_info->balance + $amount, 'updated_at' => date('Y-m-d H:i:s')]);
            } else {
                $insert_to_balance = [
                    'employee_id' => $data['employee_id'],
                    'balance' => $amount,
                    'business_id' => $employee_info->business_id,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                DB::table('employee_balance')->insert($insert_to_balance);
            }
            return true;
        } else {
            return false;
        }
    }

    public function employee_balance($employee_id)
    {   
        if (!$employee_id) {
            return response()->json(['status'=> false, 'error'=> ['Employee id not provided']],200);
        }
    
        $res = DB::table('employee_balance')->where('employee_id', $employee_id)->first();
        if ($res) {
            return response()->json([
                'status'=> true,
                'data'=> ['balance' => $res]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['balance' => []]
            ],200);
        }
    }

    public function employee_transaction_history(Request $request)
    {   
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'employee_id' => 'required',
            'start_date'  => 'required',
            'end_date'  => 'required',
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
        $employee_id = $post_data['employee_id'];
        //$res = DB::table('employee_transactions')->where('employee_id', $employee_id)->get();
        $sql = "select * from employee_transactions where employee_id = '".$employee_id."' and date between '".$post_data['start_date']."' and '".$post_data['end_date']."'";
        $res = DB::select($sql);
        if ($res) {
            return response()->json([
                'status'=> true,
                'data'=> ['transactions' => $res]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['transactions' => []]
            ],200);
        }
    }

    public function total_unpaid_salary($boss_id, $business_id)
    {
        if (!$boss_id) {
            return response()->json(['status'=> false, 'error'=> ['Boss id not provided']],200);
        }

        if (!$business_id) {
            return response()->json(['status'=> false, 'error'=> ['Business id not provided']],200);
        }

        $sql = "select sum(balance) as total_unpaid from employee_balance where business_id = '".$business_id."'";
        $res = DB::select($sql);
       
        if ($res) {
            return response()->json([
                'status'=> true,
                'data'=> ['total_unpaid' => $res[0]->total_unpaid]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['balance' => []]
            ],200);
        }
    }

    public function home($boss_id, $business_id)
    {   
        if (!$boss_id) {
            return response()->json(['status'=> false, 'error'=> ['Boss id not provided']],200);
        }

        if (!$business_id) {
            return response()->json(['status'=> false, 'error'=> ['Business id not provided']],200);
        }
        $sql = "select sum(balance) as total_unpaid from employee_balance where business_id = '".$business_id."'";
        $res = DB::select($sql);
        $total_salary = $res ? $res[0]->total_unpaid : 0;

        $sql2 = "select count(*) as total_employee from employee where business_id = '".$business_id."'";

        $res2 = DB::select($sql2);
        $total_employee = $res2 ? $res2[0]->total_employee : 0;

        $sql3 = "select count(*) as total_present from attendence where business_id = '".$business_id."' and type=1 and punch_date = '".date('Y-m-d')."' ;";
        $res3 = DB::select($sql3);

        $total_present_employee = $res3 ? $res3[0]->total_present : 0;

        if ($res) {
            return response()->json([
                'status'=> true,
                'data'=> ['total_salary' => $total_salary, 'total_employee'=> $total_employee, 'total_present_employee' => $total_present_employee]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['total_salart' => $total_salary, 'total_employee'=> $total_employee, 'total_present_employee' => $total_present_employee]
            ],200);
        }
    }

    public function monthly_attendence_report(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'boss_id' => 'required',
            'business_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required'
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
        /*$sql = "select *, dayname(punch_date) as day_name from attendence where month(punch_date) = '".$post_data['month']."' and business_id = '".$post_data['business_id']."'";*/
        $sql = "select distinct(punch_date) as punch_date from attendence where find_in_set(employee_id, '".$post_data['employee_id']."') and punch_date
        between '".$post_data['start_date']."' and '".$post_data['end_date']."' and type =1";
        $res = DB::select($sql);
        $report = [];

        foreach ($res as $key=>$r) {
            $sql2 = "select distinct a.employee_id as emp_id,e.name,a.punch_in_time,a.punchout_time,a.absent_present,a.approved_status from employee e join attendence a on e.id = a.employee_id where a.absent_present = 0 and
            punch_date='".$r->punch_date."' and find_in_set(e.id, '".$post_data['employee_id']."');";
            $absent_list  = DB::select($sql2);
            $sql2 = "select distinct a.employee_id as emp_id,e.name,a.punch_in_time,a.punchout_time,a.absent_present,a.approved_status from employee e join attendence a on e.id = a.employee_id where a.absent_present = 1 and
            punch_date='".$r->punch_date."' and find_in_set(e.id, '".$post_data['employee_id']."');";
            $present_list  = DB::select($sql2);
            //echo $sql2;
            $sql2 = "select distinct a.employee_id as emp_id,e.name,a.punch_in_time,a.punchout_time,a.absent_present,a.approved_status from employee e join attendence a on e.id = a.employee_id where a.absent_present = 3 and
            punch_date='".$r->punch_date."' and find_in_set(e.id, '".$post_data['employee_id']."');";
            $offday_list  = DB::select($sql2);
            
            $date = $r->punch_date;
            $report[$key]['date'] = $date;
            $report[$key]['absent'] = count($absent_list);
            $report[$key]['present'] = count($present_list);
            $report[$key]['offday'] = count($offday_list);
            $report[$key]['halfday'] = 0;
            $report[$key]['absent_list'] = $absent_list;
            $report[$key]['present_list'] = $present_list;
            $report[$key]['offday_list'] = $offday_list;
            $report[$key]['halfday_list'] = [];
        }
        if ($report) {
            return response()->json([
                'status'=> true,
                'data'=> ['monthly_attendence' => $report]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['monthly_attendence' => []]
            ],200);
        }
    }

    public function employee_past_months(Request $request)
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
        $sql = "select DATE_FORMAT(punch_date, '%b %Y') AS created_month,DATE_FORMAT(punch_date, '%Y-%m-01') as filter_date FROM attendence where employee_id = '".$post_data['employee_id']."' GROUP BY created_month";
        $res = DB::select($sql);
        if ($res) {
            return response()->json([
                'status'=> true,
                'data'=> ['months' => $res]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['months' => []]
            ],200);
        }
        
    }

    public function set_attendence_verification_settings(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'boss_id' => 'required',
            'business_id' => 'required',
            'settings' => 'required'
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
        //$res = DB::table('employee')->where('id', $post_data['employee_id'])->update(['attendence_settings' => $post_data['settings']]);

        $res = DB::table("businesses")->where('id', $post_data['business_id'])->update([
            'attendence_verification' => $post_data['settings']
        ]);
       
        if ($res) {
            return response()->json([
                'status'=> true
                
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }

    public function add_loan(Request $request)
    {
        $errors = [];
        $post_data = $request->json()->all();
        $rules = [
            'boss_id' => 'required',
            'employee_id' => 'required',
            'business_id' => 'required',
            'amount' => 'required',
            //'loan_date' => 'required',
            'type' => 'required'
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
        $res = DB::table('loan')->insertGetId([
            'employee_id' => $post_data['employee_id'],
            'boss_id' => $post_data['boss_id'],
            'business_id' => $post_data['business_id'],
            'loan_amount' => $post_data['amount'],
            'loan_given_date' => $post_data['loan_date'],
            'return_date' => $post_data['return_date'],
            'type' => $post_data['type'],
            'note' => $post_data['note'],
            'payment_method' => $post_data['payment_method'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
       
        if ($res) {
            return response()->json([
                'status'=> true,
                'data'=>['loan_id' => $res]
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
    }

    public function release_payment()
    {

    }

    public function emp_salary_report($employee_id)
    {
        $paycyle_info = DB::table('paycycles')->where('employee_id', $employee_id)->where('status', 1)->orderby('id', 'desc')->get();
        $salary_report = [];
        $emp_info = DB::table('employee')->where('id', $employee_id)->first();
        foreach ($paycyle_info as $key=>$paycyle) {
            $salary_report[$key]['label'] = $paycyle->label;
            $res = DB::select("select sum(amount) as amt from employee_transactions where employee_id = '".$paycyle->employee_id."' and date BETWEEN '".$paycyle->start_date."' and '".$paycyle->end_date."'");
            $salary_report[$key]['salary_earned'] = $res ? $res[0]->amt : 0;
            $salary_report[$key]['payment_recorded'] = $paycyle->paid_amount ? $paycyle->paid_amount : 0;

            $total_absent = DB::select("SELECT count(*) as total from `attendence` WHERE employee_id ='".$employee_id."' and punch_date BETWEEN '".$paycyle->start_date."' and '".$paycyle->end_date."' and absent_present in (0,2)");
            
            $salary_report[$key]['total_absent'] = $total_absent ? $total_absent[0]->total : 0;

            $total_present = DB::select("SELECT count(*) as total from `attendence` WHERE employee_id ='".$employee_id."' and punch_date BETWEEN '".$paycyle->start_date."' and '".$paycyle->end_date."' and absent_present =1");

            $salary_report[$key]['total_present'] = $total_present ? $total_present[0]->total : 0;

        }

        if ($salary_report) {
            return response()->json([
                'status'=> true,
                'data' => ['salary_report' => $salary_report]
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }
        
    }

    public function send_email_notification($boss_id) 
    {
        $boss_info = DB::table('users')->where('id', $boss_id)->first();

        $title = "New Labook Pay Request";
        $body = "New Labook pay request. User_id: ".$boss_id.", User email: {email}";
        Mail::send(['text'=>'mail'], $data, function($message) {
            $message->to('abc@gmail.com', 'Tutorials Point')->subject
               ($title);
            $message->from('xyz@gmail.com','Virat Gandhi');
        });

        if ($boss_info) {
            return response()->json([
                'status'=> true
            ],200);
        } else {
            return response()->json([
                'status'=> false
            ],200);
        }

    }
    public function get_loan($employee_id)
    {
        if (!$employee_id) {
            return response()->json(['status'=> false, 'error'=> ['Employee id not provided']],200);
        }

        $sql = "select * from loan where employee_id = '".$employee_id."'";
        $res = DB::select($sql);
       
        if ($res) {
            return response()->json([
                'status'=> true,
                'data'=> ['loan_details' => $res]
            ],200);
        } else {
            return response()->json([
                'status'=> false,
                'data'=> ['loan_details' => []]
            ],200);
        }
    }

    private function send_sms($staff_id,$phone) 
    {
        $account_sid = 'ACe9b08c5128ae31b9e0a44fbcffcd56c6';
        $auth_token = '1d651dffc88ec77da2a71e0d3def8d86';
        $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/SMS/Messages";
               
        $from = "19377453506"; // twilio trial verified number
        $code = uniqid();
        $body = "https://labook.nestmart.co/invite/". $code;
        $update_arr = [
            'invite_link' => $body,
            'invite_status' => 2,
            'invite_code' => $code
        ];
        DB::table('employee')->where('id', $staff_id)->update($update_arr);
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
        curl_close($x);
    }

    /**
     *
     * Send Email to boss when someone tap labook
     * int $employee_id
     * void
     */
    public function employee_tap_labook($employee_id)
    {
        $query = DB::table('users');
        $query = $query->select('id', 'is_active as active', 'name', 'phone as phone_number', 'type as user_type', );
        $query = $query->where('id', $employee_id);
        $boss_info = $query->first();
        $head = 'A user with id ' . $boss_info->id . ' has tapped Labook pay';
        $data = array(
            'title' => $head,
            'body' =>  json_encode($boss_info)
        );

        if ($boss_info) {
            //void send(string|array $view, array $data, Closure|string $callback)
            Mail::send('mail', $data, function($callback) {
                $callback->subject('Someone has tapped Labook pay');
                $callback->to('shahbaz.webdev@gmail.com', 'Shahbaz Khan');
                $callback->from('mshahbazkhuram@gmail.com','Shahbaz Khan');
            });
            return response()->json([
                'status'=> true,
                'message' => "email sent Successfully"
            ], 200);
        } else {
            return response()->json([
                'status'=> false,
                'message' => "There is some error while sending email please contact support"
            ], 200);
        }
    }
}