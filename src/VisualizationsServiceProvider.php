<?php

namespace Yarm\Visualizations;

use Illuminate\Support\ServiceProvider;

class VisualizationsServiceProvider extends ServiceProvider{

    public function boot()
    {

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views','visualizations');
        $this->mergeConfigFrom(__DIR__ . '/config/visualizations.php','visualizations');
        $this->publishes([
            ////__DIR__ . '/config/visualizations.php' => config_path('visualizations.php'),
            __DIR__.'/views' => resource_path('views/vendor/visualizations'),
            // Assets
            __DIR__.'/js' => resource_path('js/vendor'),
        ],'visualizations');


        //after every update
        //run   php artisan vendor:publish [--provider="Yarm\Visualizations\VisualizationsServiceProvider"][--tag="visualizations"]  --force
    }

    public function register()
    {

    }
}
