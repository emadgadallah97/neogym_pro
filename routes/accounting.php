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
        Route::group(['namespace' => 'accounting'], function () {
            //accounting programs
            Route::resource('accounting', 'accountingcontroller');
            //expenses programs
            Route::resource('expenses', 'expensescontroller');

Route::match(['GET','POST'], 'expenses/actions/employees-by-branch', 'expensescontroller@ajaxEmployeesByBranch')
    ->name('expenses.actions.employees_by_branch');
            //expenses settings
            Route::resource('expenses_types', 'expenses_typescontroller');

        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
