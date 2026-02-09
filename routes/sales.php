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
        Route::group(['namespace' => 'sales'], function () {

            Route::resource('sales', 'salescontroller');

            // AJAX endpoints
            Route::match(['GET', 'POST'], 'ajax/members-by-branch', 'salescontroller@ajaxMembersByBranch')
                ->name('sales.ajax.members_by_branch');

            Route::match(['GET', 'POST'], 'ajax/plans-by-branch', 'salescontroller@ajaxPlansByBranch')
                ->name('sales.ajax.plans_by_branch');

            Route::match(['GET', 'POST'], 'ajax/pricing-preview', 'salescontroller@ajaxPricingPreview')
                ->name('sales.ajax.pricing_preview');

            Route::match(['GET', 'POST'], 'ajax/trainer-session-price', 'salescontroller@ajaxTrainerSessionPrice')
                ->name('sales.ajax.trainer_session_price');

            Route::match(['GET', 'POST'], 'ajax/offers-list', 'salescontroller@ajaxOffersList')
                ->name('sales.ajax.offers_list');

            Route::match(['GET', 'POST'], 'ajax/plan-trainer-context', 'salescontroller@ajaxPlanTrainerContext')
                ->name('sales.ajax.plan_trainer_context');

            Route::match(['GET', 'POST'], 'ajax/plan-base-price', 'salescontroller@ajaxPlanBasePrice')
                ->name('sales.ajax.plan_base_price');

            Route::match(['GET', 'POST'], 'ajax/coaches-by-branch', 'salescontroller@ajaxCoachesByBranch')
                ->name('sales.ajax.coaches_by_branch');

            Route::match(['GET', 'POST'], 'ajax/validate-coupon', 'salescontroller@ajaxValidateCoupon')
                ->name('sales.ajax.validate_coupon');

            Route::get('ajax/subscriptions/{id}/modal', 'salescontroller@ajaxSubscriptionShowModal')
                ->name('sales.ajax.subscriptions.modal');
            //-------

            Route::match(['GET', 'POST'], 'ajax/current-subscriptions/table', 'salescontroller@ajaxCurrentSubscriptionsTable')
                ->name('sales.ajax.current_subscriptions.table');

            Route::get('subscriptions/{id}/pt-addons/create', [\App\Http\Controllers\sales\subscriptionptaddonsalecontroller::class, 'create'])
                ->name('sales.subscriptions.pt_addons.create');

            Route::post('subscriptions/{id}/pt-addons', [\App\Http\Controllers\sales\subscriptionptaddonsalecontroller::class, 'store'])
                ->name('sales.subscriptions.pt_addons.store');



            Route::post('sales/ajaxtrainer-session-price', [\App\Http\Controllers\sales\SubscriptionPtAddonSaleController::class, 'ajaxTrainerSessionPrice'])
                ->name('sales.ajax.trainersessionprice');



        });
    },
);

Auth::routes();
//Auth::routes(['register' => false]);
