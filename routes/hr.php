<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Auth::routes(['verify' => true]);

Route::group(
    [

        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth', 'verified'],
    ],
    function () {
        Route::group(['namespace' => 'hr'], function () {
            //hr programs
            Route::resource('hr', 'hrprogramscontroller');


            //hr attendance
            // routes/web.php (داخل مجموعة hr أو حسب تنظيم مشروعك)

            Route::get('attendance/process', 'attendancecontroller@processIndex')->name('attendance.process.index');
            Route::post('attendance/process/run', 'attendancecontroller@processRun')->name('attendance.process.run');

            Route::get('attendance/employees/by-branch', 'attendancecontroller@employeesByBranch')->name('attendance.employees.byBranch');

            // استقبال من الجهاز (اختياري الآن)
            Route::post('attendance/logs/receive', 'attendancecontroller@receiveLog')->name('attendance.logs.receive');
            Route::resource('attendance', 'attendancecontroller');

            //shifts
            Route::resource('shifts', 'shiftscontroller');
            //employee_shifts
            Route::resource('employee_shifts', 'employee_shiftscontroller');
            //hr advances
            Route::resource('advances', 'advancescontroller');

            Route::post('advances/{advance}/approve', 'advancescontroller@approve')->name('advances.approve');
            Route::post('advances/{advance}/reject', 'advancescontroller@reject')->name('advances.reject');

            // (اختياري لتحسين الفلاتر)
            Route::get('advances/employees/by-branch', 'advancescontroller@employeesByBranch')->name('advances.employees.byBranch');
            //hr deductions
            Route::resource('deductions', 'deductionscontroller');
            Route::post('deductions/{deduction}/approve', 'deductionscontroller@approve')->name('deductions.approve');
            Route::get('deductions/employees/by-branch', 'deductionscontroller@employeesByBranch')->name('deductions.employees.byBranch');

            //hr overtime
// Overtime
Route::resource('overtime', 'overtimecontroller');
Route::post('overtime/{overtime}/approve', 'overtimecontroller@approve')->name('overtime.approve');
Route::get('overtime/employees/by-branch', 'overtimecontroller@employeesByBranch')->name('overtime.employees.byBranch');
Route::post('overtime/generate/from-attendance', 'overtimecontroller@generateFromAttendance')->name('overtime.generateFromAttendance');
// Allowances
Route::resource('allowances', 'allowancescontroller');
Route::post('allowances/{allowance}/approve', 'allowancescontroller@approve')->name('allowances.approve');
Route::get('allowances/employees/by-branch', 'allowancescontroller@employeesByBranch')->name('allowances.employees.byBranch');
            //hr payrolls
            Route::resource('payrolls', 'payrollscontroller');
            //hr devices
            Route::resource('devices', 'devicescontroller');

        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
