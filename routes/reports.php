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
        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
