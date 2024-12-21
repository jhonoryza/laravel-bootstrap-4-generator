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
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCmsControllerAndService::class,
            ]);
        }
    }

    public function provides(): array
    {
        return [
            MakeCmsControllerAndService::class,
        ];
    }
}
