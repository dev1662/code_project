<?php

namespace App\Providers;

use App\Models\CentralOnboarding;
use Illuminate\Support\ServiceProvider;

use Laravel\Passport\Passport;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use App\Observers\UserObserver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Passport::routes(null, ['middleware' => [
            // You can make this simpler by creating a tenancy route group
            'universal',
            InitializeTenancyByRequestData::class
        ]]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Passport::loadKeysFrom(base_path(config('passport.key_path')));
        CentralOnboarding::observe(UserObserver::class);

    }
}
