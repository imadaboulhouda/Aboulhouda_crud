<?php

namespace Aboulhouda\Crud;

use Illuminate\Support\ServiceProvider;
use Aboulhouda\Crud\Commands\CreateCrud;

class ImadServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateCrud::class,

            ]);
        }
    }
}
