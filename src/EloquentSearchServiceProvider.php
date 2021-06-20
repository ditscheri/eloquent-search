<?php

namespace Ditscheri\EloquentSearch;

use Ditscheri\EloquentSearch\Commands\EloquentSearchCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class EloquentSearchServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('eloquent-search')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_eloquent-search_table')
            ->hasCommand(EloquentSearchCommand::class);
    }
}
