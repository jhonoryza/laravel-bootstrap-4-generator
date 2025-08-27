<?php

namespace Jhonoryza\Bootstrap\Generator;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Jhonoryza\Bootstrap\Generator\Console\Commands\MakeCmsControllerAndService;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }
        $this->publishes([
            __DIR__ . '/Console/Commands/Stubs' => base_path('stubs/rgb_basecode_gen'),
        ], 'rgb-stubs');

        $this->commands([
            MakeCmsControllerAndService::class,
        ]);
    }

    public function provides(): array
    {
        return [
            MakeCmsControllerAndService::class,
        ];
    }
}
