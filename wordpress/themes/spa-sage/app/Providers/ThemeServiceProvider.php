<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Roots\Acorn\Sage\SageServiceProvider;
use function App\helpers;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Blade::directive('svgIcon', function ($icon) {
            $args = explode('|', $icon);
            $count = count($args);
            $icon_args['icon'] = str_replace("'", "", $args[0]);
            if ($count > 1) $icon_args['width'] = (int) $args[1];
            if ($count > 2) $icon_args['height'] = (int) $args[2];
            if ($count > 3) $icon_args['title'] = str_replace("'", "", $args[3]);

            return helpers()->getSvg($icon_args);
        });
    }
}
