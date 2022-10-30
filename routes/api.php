<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Authcontroller;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\BossController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\CronjobController;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Middleware\Hasaccesskey;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('boss')->group(function () {
    Route::get('attendence_cron', [CronjobController::class, 'attendence_cron']);
    Route::get('paycycle_cron', [CronjobController::class, 'set_paycycle']);
    Route::get('paycycle_list/{id}', [BossController::class, 'paycycle_list']);
    Route::get('salary_report/{id}', [BossController::class, 'emp_salary_report']);
    Route::middleware([Hasaccesskey::class])->group(function () {
        Route::post('send_verification_code', [Authcontroller::class, 'send_verification_code']);
        Route::post('login', [Authcontroller::class, 'login']);
        Route::middleware([EnsureTokenIsValid::class])->group(function () {
            Route::post('logout', [Authcontroller::class, 'logout']);
            Route::post('addbusiness', [BossController::class, 'addbusiness']);
            Route::get('businessdetails/{id}', [BossController::class, 'business_details']);
            Route::post('editbusiness', [BossController::class, 'editbusiness']);

            Route::post('editbusinessprofile', [BossController::class, 'editbusinessprofile']);
            Route::get('businessprofiledetails/{boss_id}/{business_id}', [BossController::class, 'businessprofiledetails']);
            Route::get('send_email_notification/{employee_id}', [BossController::class, 'send_email_notification']);
            Route::get('enableinout/{business_id}/{status}', [BossController::class, 'enableinout']);

            Route::get('businesslist/{boss_id}', [BossController::class, 'businesslist']);
            Route::post('addstaff', [BossController::class, 'add_staff']);
            Route::post('editstaff', [BossController::class, 'edit_staff']);
            Route::post('changestaffstatus', [BossController::class, 'update_staff_status']);
            Route::get('stafflist/{business_id}', [BossController::class, 'staff_list']);
            Route::delete('staff', [BossController::class, 'deletestaff']);
            Route::get('defaultschedule/{business_id}', [BossController::class, 'get_default_schedule']);
            Route::get('inviteallstaff/{business_id}', [BossController::class, 'invite_all_staff']);
            Route::get('invitestaff/{id}', [BossController::class, 'invite_staff']);
            Route::get('change_invite_status/{employee_id}/{id}', [BossController::class, 'change_invite_status']);
            Route::post('attendence_reminder_settings', [BossController::class, 'attendence_reminder_settings']);
            Route::get('get_attendence_reminder_settings/{id}/{business_id}', [BossController::class, 'get_attendence_reminder_settings']);
            Route::post('verify_business', [BossController::class, 'verify_business']);
            Route::post('edit_attendence', [BossController::class, 'edit_attendence']);
            Route::post('approve_attendence', [BossController::class, 'approve_attendence']);
            Route::get('get_staff_profile/{id}', [BossController::class, 'get_staff_profile']);
            Route::post('edit_staff_salary', [BossController::class, 'edit_staff_salary']);
            Route::post('datewise_attendence_status', [BossController::class, 'datewise_attendence_status']);
            Route::post('employee_past_months', [BossController::class, 'employee_past_months']);
            Route::get('get_staff_attendence_history/{id}', [BossController::class, 'get_staff_attendence_history']);
            Route::post('monthly_attendence_report', [BossController::class, 'monthly_attendence_report']);
            Route::post('create_invitation_link', [BossController::class, 'create_invitation_link']);
            Route::get('home/{id}/{business_id}', [BossController::class, 'home']);
            Route::get('upcoming_pay/{id}/{business_id}', [BossController::class, 'upcoming_pay']);
            Route::get('employee_balance/{id}', [BossController::class, 'employee_balance']);
            Route::post('employee_transaction_history', [BossController::class, 'employee_transaction_history']);
            Route::get('total_unpaid_salary/{id}/{business}', [BossController::class, 'total_unpaid_salary']);
            Route::post('set_attendence_verification_settings', [BossController::class, 'set_attendence_verification_settings']);
            Route::post('add_loan', [BossController::class, 'add_loan']);
            Route::post('adjust_salary', [BossController::class, 'adjust_salary']);
            Route::post('salary_adjustments', [BossController::class, 'get_salary_adjustments']);
            Route::get('loan/{employee_id}', [BossController::class, 'get_loan']);
            Route::get('record_payment/{employee_id}', [BossController::class, 'release_payment']);
            Route::post('paycycle', [BossController::class, 'paycycle']);

            Route::get('employee_tap_labook/{id}', [BossController::class, 'employee_tap_labook']);
            
        });
    });
});

Route::prefix('employee')->group(function () {
    Route::middleware([Hasaccesskey::class])->group(function () {
        Route::post('send_verification_code', [Authcontroller::class, 'send_verification_code']);
        Route::post('login', [Authcontroller::class, 'login']);
        Route::post('accept_invitation', [EmployeeController::class, 'accept_invitation']);
        Route::middleware([EnsureTokenIsValid::class])->group(function () {
            Route::post('selfie', [EmployeeController::class, 'upload_selfie']);
            Route::post('logout', [Authcontroller::class, 'logout']);
            Route::get('info/{id}', [EmployeeController::class, 'employee_info']);
            Route::post('editinfo', [EmployeeController::class, 'employee_info_edit']);
            Route::post('punchin', [EmployeeController::class, 'punchin']);
            Route::post('punchout', [EmployeeController::class, 'punchout']);
            Route::get('attendence_list/{id}', [EmployeeController::class, 'attendence_list']);
            Route::post('paycycle', [EmployeeController::class, 'paycycle']);
            Route::post('home', [EmployeeController::class, 'home']);
            Route::get('getlastattendencestatus/{id}', [EmployeeController::class, 'getlastattendencestatus']);
            Route::get('reimbursment/{id}', [EmployeeController::class, 'reimbursment']);
            Route::post('addreimbursment', [EmployeeController::class, 'addreimbursment']);
        });
    });
});

Route::prefix('v1')->group(function () {
    Route::middleware([Hasaccesskey::class])->group(function () {
        Route::post('verification', [Authcontroller::class, 'do_verification']);
        Route::post('register', [Authcontroller::class, 'register']);
        Route::post('login', [Authcontroller::class, 'login']);
        Route::post('update_ecomerce_status', [Authcontroller::class, 'update_ecomerce_status']);
        Route::post('forgetpassword', [Authcontroller::class, 'forgetpassword']);
        Route::post('resetpassword', [Authcontroller::class, 'reset_password']);
        
        Route::middleware([EnsureTokenIsValid::class])->group(function () {
            Route::get('tabs/{user_id}', [HomeController::class, 'get_tabs']);
        Route::post('add_tab', [HomeController::class, 'add_tab']);
        Route::put('update_tab', [HomeController::class, 'update_tab']);
        Route::put('delete_tab', [HomeController::class, 'delete_tab']);

        
        Route::get('get_tab_items/{tab_id}', [HomeController::class, 'get_tab_items']);
        Route::post('add_tab_items', [HomeController::class, 'add_tab_items']);
        Route::put('update_tab_items', [HomeController::class, 'update_tab_items']);
        Route::put('delete_tab_items', [HomeController::class, 'delete_tab_items']);
       
        Route::post('keyboard_survey', [HomeController::class, 'keyboard_survey']);
        Route::post('analytics', [HomeController::class, 'analytics']);
        Route::get('test/{id}', [HomeController::class, 'test']);
        
        });
    });
});
