<?php

namespace Cuytamvan\BasePattern;

use Illuminate\Support\ServiceProvider;

use Cuytamvan\BasePattern\Console\Commands\RepositoryGenerator;
use Cuytamvan\BasePattern\Console\Commands\GenerateApiKeyCommand;

class BasePatternServiceProvider extends ServiceProvider {
    public function boot() {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/cuypattern.php' => config_path('cuypattern.php'),
        ]);

        $this->commands([
            RepositoryGenerator::class,
            GenerateApiKeyCommand::class,
        ]);
    }

    public function register() {

    }
}
