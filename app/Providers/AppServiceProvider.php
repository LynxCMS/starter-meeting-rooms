<?php

namespace App\Providers;

use App\Forms\ConsultationForm;
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
     * Bootstrap any application services: the site's forms.
     */
    public function boot(): void
    {
        lynx()->forms()->register(ConsultationForm::class);
    }
}
