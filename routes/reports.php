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
        Route::group(['namespace' => 'reports'], function () {
            //all reports
            Route::resource('reports', 'reportscontroller');

            //attendances_report
            Route::resource('attendances_report', 'attendances_report\attendances_reportcontroller');

            //employees_report
            Route::resource('employees_report', 'employees_report\employees_reportcontroller');

            //members_report
            Route::resource('members_report', 'members_report\members_reportcontroller');

            //subscriptions_report
            Route::resource('subscriptions_report', 'subscriptions_report\subscriptions_reportcontroller');

            //التقارير الي محتاجه مراجعه--------------------------
            //subscriptions_report
            Route::resource('sales_report', 'sales_report\sales_reportcontroller');
//  payments_report
Route::resource('payments_report', 'payments_report\payments_reportcontroller');

//  commissions_report
Route::resource('commissions_report', 'commissions_report\commissions_reportcontroller');

// pt_addons_report
Route::resource('pt_addons_report', 'pt_addons_report\pt_addons_reportcontroller');

        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
