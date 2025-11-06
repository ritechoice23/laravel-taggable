<?php

namespace Ritechoice23\Taggable\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Ritechoice23\Taggable\TaggableServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            TaggableServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
