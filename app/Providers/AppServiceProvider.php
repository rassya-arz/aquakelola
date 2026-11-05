<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        Blade::directive('rp', fn ($expr) => "<?php echo \\App\\Support\\Fmt::rp($expr); ?>");
        Blade::directive('num', fn ($expr) => "<?php echo \\App\\Support\\Fmt::num($expr); ?>");
    }
}
