<?php

namespace App\Providers;

use App\Models\accounting\income as Income;
use App\Models\accounting\Expense;
use App\Observers\IncomeObserver;
use App\Observers\ExpenseObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Treasury observers — automatically sync income/expense to treasury
        Income::observe(IncomeObserver::class);
        Expense::observe(ExpenseObserver::class);
    }
}
