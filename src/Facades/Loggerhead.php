<?php

namespace Clumsy\Loggerhead\Facades;

class Loggerhead extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'clumsy.loggerhead';
    }
}
