<?php

namespace Migratoro;

use Illuminate\Support\ServiceProvider;

class MigratoroServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            MigratoroCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadMigrationsFrom(realpath(__DIR__.'/../migrations'));
    }
}
