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


        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
