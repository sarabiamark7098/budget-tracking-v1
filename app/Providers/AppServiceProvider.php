<?php

namespace App\Providers;

use App\Models\Payment;
use App\Observers\PaymentObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Payment::observe(PaymentObserver::class);

        Gate::policy(\App\Models\Income::class,        \App\Policies\IncomePolicy::class);
        Gate::policy(\App\Models\Expense::class,       \App\Policies\ExpensePolicy::class);
        Gate::policy(\App\Models\Budget::class,        \App\Policies\BudgetPolicy::class);
        Gate::policy(\App\Models\Debt::class,          \App\Policies\DebtPolicy::class);
        Gate::policy(\App\Models\Payment::class,       \App\Policies\PaymentPolicy::class);
        Gate::policy(\App\Models\File::class,          \App\Policies\FilePolicy::class);
        Gate::policy(\App\Models\Category::class,      \App\Policies\CategoryPolicy::class);
        Gate::policy(\App\Models\Purchase::class,      \App\Policies\PurchasePolicy::class);
    }
}
