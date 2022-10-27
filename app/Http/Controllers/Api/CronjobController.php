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

class CronjobController extends Controller
{
    public function attendence_cron()
    {
        $sql = "select e.*, b.attendence_verification,b.enable_in_out from employee e join businesses b on e.business_id = b.id where 1=1 and b.is_active = 1 and e.is_active = 1;";
        $employees = DB::select($sql);
        //print_r($employees); exit;
        $today = substr(date("l"),0,3);
        $today_date = date('Y-m-d');
        foreach ($employees as $employee) {
            $working_days = json_decode($employee->working_days, true);
            if ($working_days) {
                $working_days = array_map( 'strtolower', $working_days );
            } else {
                $working_days = [];
            }
            
            if (($employee->enable_in_out == NULL || !$employee->enable_in_out) && $employee->attendence_verification ==1) {
                
                if (in_array(strtolower($today),$working_days )){
                    $data = [
                        'employee_id' => $employee_id->id,
                        'type' => 1,
                        'approved_status' => 1,
                        'absent_present' => 1,
                        'punch_in_time' => date("Y-m-d").' 00:00:00',
                        'punch_date' => date("Y-m-d"),
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $attendence_id = DB::table('attendence')->insertGetId($data);
                    $data['status'] = 1;
                    $this->process_employee_salary($data);
                } else {
                    // off day
                    $data = [
                        'employee_id' => $employee_id->id,
                        'type' => 1,
                        'approved_status' => 1,
                        'absent_present' => 3,
                        'punch_in_time' => date("Y-m-d").' 00:00:00',
                        'punch_date' => date("Y-m-d"),
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $data['status'] = 1;
                    $attendence_id = DB::table('attendence')->insertGetId($data);
                    $this->process_employee_salary($data);
                }

                //auto present
                
            } else if ($employee->enable_in_out == 1 && $employee->attendence_verification ==1) {
                if (in_array(strtolower($today),$working_days )){
                    $attendence = DB::table('attendence')->where('employee_id', $employee->id)->where('punch_date', $today_date)->first();
                    if (!$attendence) {
                        $data = [
                            'employee_id' => $employee->id,
                            'type' => 1,
                            'approved_status' => 1,
                            'absent_present' => 2,
                            'punch_in_time' => date("Y-m-d").' 00:00:00',
                            'punch_date' => date("Y-m-d"),
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        $attendence_id = DB::table('attendence')->insertGetId($data);
                        //unmark
                        if ($employee->deduct_salary) {
                            $data['status'] = 1;
                        } else {
                            $data['status'] = 0;
                        }
                        $this->process_employee_salary($data);
                    }
                } else {
                    // off day
                    $data = [
                        'employee_id' => $employee->id,
                        'type' => 1,
                        'approved_status' => 1,
                        'absent_present' => 3,
                        'punch_in_time' => date("Y-m-d").' 00:00:00',
                        'punch_date' => date("Y-m-d"),
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $attendence_id = DB::table('attendence')->insertGetId($data);
                   
                    $data['status'] = 1;
                    $this->process_employee_salary($data);
                }
            } else if ($employee->enable_in_out == 1 && $employee->attendence_verification !=1) {
                if (in_array(strtolower($today),$working_days )){
                    $attendence = DB::table('attendence')->where('employee_id', $employee->id)->where('punch_date', $today_date)->first();
                    if (!$attendence) {
                        $data = [
                            'employee_id' => $employee->id,
                            'type' => 1,
                            'absent_present' => 2,
                            'punch_in_time' => date("Y-m-d").' 00:00:00',
                            'punch_date' => date("Y-m-d"),
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                        $attendence_id = DB::table('attendence')->insertGetId($data);
                        //unmark
                        
                    }
                } else {
                    // off day
                    $data = [
                        'employee_id' => $employee->id,
                        'type' => 1,
                        'approved_status' => 1,
                        'absent_present' => 3,
                        'punch_in_time' => date("Y-m-d").' 00:00:00',
                        'punch_date' => date("Y-m-d"),
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $attendence_id = DB::table('attendence')->insertGetId($data);
                   
                    $data['status'] = 1;
                    $this->process_employee_salary($data);
                }
            }
            
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
    public function set_paycycle()
    {
        $employees = DB::table('employee')->where('is_active', 1)->get();
        $cur_month = date("M");
        $cur_month_yr = date('Y-m');
        $next_month = date('M', strtotime('+1 month'));
        $next_month_yr = date('Y-m', strtotime('+1 month'));
        foreach ($employees as $employee) {
            $employee_id = $employee->id;
            $sql = "select * from paycycles where employee_id = '".$employee_id."' and MONTH(start_date)=MONTH(NOW());";
            $res = DB::select($sql);
            if ($res) {

            } else {
                if ($employee->paid_every == 1) {
                    $label = '02 '.$cur_month.' - '.'01 '.$next_month;
                    $start_date = $cur_month_yr.'-02';
                    $end_date = $next_month_yr.'-01';
                } else if ($employee->paid_every == -1) {
                    $last_day_this_month  = date('t');
                    $label = '01 '.$cur_month.' - '.$last_day_this_month.' '.$cur_month;
                    $start_date = $cur_month_yr.'-01';
                    $end_date = $cur_month_yr.'-'.$last_day_this_month;
                }
                 else {
                    //spefic date 
                    $text = date("Y-m-".$employee->paid_every);
                    $start_date =date('Y-m-d', strtotime("+1 day", strtotime($text)));
                    $end_date =date('Y-m-d', strtotime("+30 day", strtotime($text)));
                    
                    $label = date('d M', strtotime("+1 day", strtotime($text))).' - '.date('d M', strtotime("+30 day", strtotime($text)));
                }
                $arr = [
                    'label' => $label,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'status' => 1,
                    'employee_id' => $employee->id,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                DB::table('paycycles')->insert($arr);
            }
        }
    }
}