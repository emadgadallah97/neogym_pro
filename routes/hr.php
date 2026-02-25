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
