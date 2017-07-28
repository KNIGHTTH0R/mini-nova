<?php

namespace Widgets\Support\Facades;

use Mini\Support\Facades\Facade;


class Widget extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'widgets'; }
}
