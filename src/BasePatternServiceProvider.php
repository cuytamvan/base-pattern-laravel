<?php

namespace Cuytamvan\BasePattern;

use Illuminate\Support\ServiceProvider;

use Cuytamvan\BasePattern\Console\Commands\RepositoryGenerator;

class BasePatternServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/cuypattern.php' => config_path('cuypattern.php'),
        ]);

        $this->commands([
            RepositoryGenerator::class,
        ]);
    }

    public function register()
    {
    }
}
