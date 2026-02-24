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
            Route::resource('attendance', 'attendancecontroller');
            //hr advances
            Route::resource('advances', 'advancescontroller');
            //hr deductions
            Route::resource('deductions', 'deductionscontroller');
            //hr overtime
            Route::resource('overtime', 'overtimecontroller');
            //hr payrolls
            Route::resource('payrolls', 'payrollscontroller');
            //hr devices
            Route::resource('devices', 'devicescontroller');
//  Route::get('/', [HrController::class, 'index'])->name('index');
//     Route::resource('attendances',  HrAttendanceController::class);
//     Route::resource('advances',     HrAdvanceController::class);
//     Route::resource('deductions',   HrDeductionController::class);
//     Route::resource('overtime',     HrOvertimeController::class);
//     Route::resource('payrolls',     HrPayrollController::class);
//     Route::resource('devices',      HrDeviceController::class);
//     Route::get('reports',           [HrReportController::class, 'index'])->name('reports.index');
        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
