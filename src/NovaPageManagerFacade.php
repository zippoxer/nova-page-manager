<?php

namespace OptimistDigital\NovaPageManager;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Traits\Macroable;
use OptimistDigital\NovaPageManager\Models\Page;

class NovaPageManagerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return CurrentPage::class;
    }
}
