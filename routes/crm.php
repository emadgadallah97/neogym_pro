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
       Route::group([
    'prefix'    => 'crm',
    'as'        => 'crm.',
    'namespace' => 'crm',
], function () {

    // ── Dashboard ──────────────────────────────────────────────────
    Route::get('/dashboard', 'CrmDashboardController@index')
        ->name('dashboard');

    // ── CRM Members ────────────────────────────────────────────────
    Route::get('/members/search-ajax', 'CrmMembersController@searchAjax')
        ->name('members.search-ajax');

    Route::resource('/members', 'CrmMembersController')
        ->only(['index', 'show']);

    // ── Prospects (الأعضاء المحتملون) ──────────────────────────────

    // ✅ Routes الثابتة أولاً — قبل أي {id} أو resource
    Route::get('/prospects/import',            'CrmProspectsController@importForm')
        ->name('prospects.import');

    Route::post('/prospects/import',           'CrmProspectsController@importStore')
        ->name('prospects.import.store');

    Route::get('/prospects/download-template', 'CrmProspectsController@downloadTemplate')
        ->name('prospects.download-template');

    // ✅ Routes الـ {id} بعد الثابتة
    Route::post('/prospects/{id}/convert',    'CrmProspectsController@convert')
        ->name('prospects.convert');

    Route::post('/prospects/{id}/disqualify', 'CrmProspectsController@disqualify')
        ->name('prospects.disqualify');

    // ✅ Resource في الأخير
    Route::resource('/prospects', 'CrmProspectsController')
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    // ── Follow-ups ─────────────────────────────────────────────────
    Route::post('/followups/{id}/done', 'CrmFollowupsController@markDone')
        ->name('followups.done');

    Route::resource('/followups', 'CrmFollowupsController');

    // ── Interactions ───────────────────────────────────────────────
    Route::resource('/interactions', 'CrmInteractionsController')
        ->only(['store', 'destroy']);
});

    },
);

Auth::routes();
//Auth::routes(['register' => false]);
