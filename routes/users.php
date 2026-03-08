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
        Route::group(['namespace' => 'users'], function () {
            //users
            Route::resource('users', 'userscontroller');
            Route::post('users/{user}/password', 'userscontroller@updatePassword')->name('users.updatePassword');
        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
