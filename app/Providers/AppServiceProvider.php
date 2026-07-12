<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Instagram\InstagramExtendSocialite;
use SocialiteProviders\Line\LineExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

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
        Carbon::macro('inDisplayTimezone', function () {
            return $this->timezone(config('app.display_timezone'));
        });

        Event::listen(SocialiteWasCalled::class, LineExtendSocialite::class.'@handle');
        Event::listen(SocialiteWasCalled::class, InstagramExtendSocialite::class.'@handle');
    }
}
