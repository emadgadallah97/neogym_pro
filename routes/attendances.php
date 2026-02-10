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
       Route::group(['namespace' => 'attendances'], function () {

    // Simple kiosk page (optional)
    Route::get('attendances/kiosk', 'attendancescontroller@kiosk')
        ->name('attendances.kiosk');

    // Barcode / global scan endpoint (fast JSON)
    Route::post('attendances/actions/scan', 'attendancescontroller@scan')
        ->name('attendances.actions.scan');

    // Actions (cancel / cancel pt / guests)
    Route::post('attendances/{attendance}/actions/cancel', 'attendancescontroller@cancel')
        ->name('attendances.actions.cancel');

    Route::post('attendances/{attendance}/actions/cancel-pt', 'attendancescontroller@cancelPt')
        ->name('attendances.actions.cancel_pt');

    Route::post('attendances/{attendance}/actions/guests', 'attendancescontroller@storeGuest')
        ->name('attendances.actions.guests.store');

    // Main screen (admin)
    Route::resource('attendances', 'attendancescontroller');
});

    },
);

Auth::routes();
//Auth::routes(['register' => false]);
