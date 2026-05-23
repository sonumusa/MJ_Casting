<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\GoldReceipt;
use App\Models\InvoiceReceive;
use App\Observers\InvoiceObserver;
use App\Observers\GoldReceiptObserver;
use App\Observers\InvoiceReceiveObserver;
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
        Invoice::observe(InvoiceObserver::class);
        GoldReceipt::observe(GoldReceiptObserver::class);
        InvoiceReceive::observe(InvoiceReceiveObserver::class);
    }
}
