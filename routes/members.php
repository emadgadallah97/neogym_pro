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
        Route::group(['namespace' => 'members'], function () {
            //members
             // إضافات الأعضاء (لازم قبل resource) [page:1]
    Route::get('members/{member}/card', 'memberscontroller@card')->name('members.card');
    Route::get('members/{member}/qr.png', 'memberscontroller@qrPng')->name('members.qr_png');

    // resource
    Route::resource('members', 'memberscontroller');

        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
