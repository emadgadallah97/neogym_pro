<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use App\Http\Controllers\hr\payrollscontroller;

Auth::routes(['verify' => true]);

Route::group(
    [
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'auth', 'verified'],
    ],
    function () {
        Route::group(['namespace' => 'hr'], function () {

            // ==================== HR Programs ====================
            Route::resource('hr', 'hrprogramscontroller');

            // ==================== Attendance ====================
            Route::get('attendance/process', 'attendancecontroller@processIndex')
                ->name('attendance.process.index');
            Route::post('attendance/process/run', 'attendancecontroller@processRun')
                ->name('attendance.process.run');
            Route::get('attendance/employees/by-branch', 'attendancecontroller@employeesByBranch')
                ->name('attendance.employees.byBranch');
            Route::post('attendance/logs/receive', 'attendancecontroller@receiveLog')
                ->name('attendance.logs.receive');
            Route::resource('attendance', 'attendancecontroller');

            // ==================== Shifts ====================
            Route::resource('shifts', 'shiftscontroller');

            // ==================== Employee Shifts ====================
            Route::resource('employee_shifts', 'employee_shiftscontroller');

            // ==================== Advances ====================
            Route::get('advances/employees/by-branch', 'advancescontroller@employeesByBranch')
                ->name('advances.employees.byBranch');
            Route::post('advances/{advance}/approve', 'advancescontroller@approve')
                ->name('advances.approve');
            Route::post('advances/{advance}/approve-with-expense', 'advancescontroller@approveWithExpense')
                ->name('advances.approveWithExpense');
            Route::post('advances/{advance}/reject', 'advancescontroller@reject')
                ->name('advances.reject');
            Route::resource('advances', 'advancescontroller');

            // ==================== Deductions ====================
            Route::get('deductions/employees/by-branch', 'deductionscontroller@employeesByBranch')
                ->name('deductions.employees.byBranch');
            Route::post('deductions/{deduction}/approve', 'deductionscontroller@approve')
                ->name('deductions.approve');
            Route::resource('deductions', 'deductionscontroller');

            // ==================== Overtime ====================
            Route::get('overtime/employees/by-branch', 'overtimecontroller@employeesByBranch')
                ->name('overtime.employees.byBranch');
            Route::post('overtime/generate/from-attendance', 'overtimecontroller@generateFromAttendance')
                ->name('overtime.generateFromAttendance');
            Route::post('overtime/{overtime}/approve', 'overtimecontroller@approve')
                ->name('overtime.approve');
            Route::resource('overtime', 'overtimecontroller');

            // ==================== Allowances ====================
            Route::get('allowances/employees/by-branch', 'allowancescontroller@employeesByBranch')
                ->name('allowances.employees.byBranch');
            Route::post('allowances/{allowance}/approve', 'allowancescontroller@approve')
                ->name('allowances.approve');
            Route::resource('allowances', 'allowancescontroller');

            // ==================== Payrolls ====================
            Route::get('payrolls/employees/by-branch', [payrollscontroller::class, 'employeesByBranch'])
                ->name('payrolls.employees.byBranch');
            Route::post('payrolls/generate', [payrollscontroller::class, 'generate'])
                ->name('payrolls.generate');
            Route::post('payrolls/approve', [payrollscontroller::class, 'approveMonth'])
                ->name('payrolls.approve');
            Route::post('payrolls/pay', [payrollscontroller::class, 'payMonth'])
                ->name('payrolls.pay');
            Route::post('payrolls/cancel-drafts', [payrollscontroller::class, 'cancelDrafts'])
                ->name('payrolls.cancelDrafts');
            Route::get('payrolls/breakdown', [payrollscontroller::class, 'breakdown'])
                ->name('payrolls.breakdown');
            Route::get('payrolls', [payrollscontroller::class, 'index'])
                ->name('payrolls.index');
            Route::resource('payrolls', 'payrollscontroller');

            // ==================== Devices ====================
            Route::resource('devices', 'devicescontroller');
        });
    },
);

Auth::routes();
