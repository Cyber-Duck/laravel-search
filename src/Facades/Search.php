<?php

namespace CyberDuck\Search\Facades;

use CyberDuck\Search\AllBuilder;
use Illuminate\Support\Facades\Facade;
/**
* @method static AllBuilder all($query = '')
 *
 * @see \CyberDuck\Search\Search
 *
 * */
class Search extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'search';
    }
}
