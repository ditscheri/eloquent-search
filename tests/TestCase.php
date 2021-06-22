<?php

namespace Ditscheri\EloquentSearch\Tests;

use Ditscheri\EloquentSearch\EloquentSearchServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function getEnvironmentSetUp($app)
    {
        // We will expect the correct MySQL syntax in our tests
        // without actually firing any queries.

        config()->set('database.default', 'testing');

        config()->set('database.connections.testing', [
            'driver' => 'mysql',
            'host' => '0.0.0.0',
            'username' => 'foo',
            'password' => 'bar',
            'database' => '_testing_',
            'prefix' => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            EloquentSearchServiceProvider::class,
        ];
    }
}
