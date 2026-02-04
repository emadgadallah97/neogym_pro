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
        Route::group(['namespace' => 'coupons_offers'], function () {
        //     //coupons
        //    Route::resource('coupons', 'couponscontroller');
        //     //offers
        //    Route::resource('offers', 'offerscontroller');
// coupons
Route::resource('coupons', 'couponscontroller');
Route::post('coupons/validate', 'couponscontroller@validateCoupon')->name('coupons.validate');

// offers
Route::resource('offers', 'offerscontroller');
Route::get('offers/best', 'offerscontroller@best')->name('offers.best');
        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
