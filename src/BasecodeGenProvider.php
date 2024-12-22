<?php

namespace Jhonoryza\Rgb\BasecodeGen;

use Illuminate\Support\ServiceProvider;
use Jhonoryza\Rgb\BasecodeGen\Console\Commands\MakeCmsControllerAndService;

class BasecodeGenProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
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
