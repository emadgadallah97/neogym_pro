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
        Route::group(['namespace' => 'subscriptions'], function () {
            //subscriptions_types settings
            Route::resource('subscriptions_types', 'subscriptions_typescontroller');
            //subscriptions program
            Route::resource('subscriptions_plans', 'subscriptions_planscontroller');

        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
