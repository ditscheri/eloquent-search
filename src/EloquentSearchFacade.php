<?php

namespace Ditscheri\EloquentSearch;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ditscheri\EloquentSearch\EloquentSearch
 */
class EloquentSearchFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'eloquent-search';
    }
}
