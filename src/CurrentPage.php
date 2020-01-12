<?php

namespace OptimistDigital\NovaPageManager;

use Illuminate\Support\Traits\Macroable;
use OptimistDigital\NovaPageManager\Models\Page;

class CurrentPage
{
    use Macroable;

    public function get($key, $default = null)
    {
        $page = app(Page::class);

        if (!$page) {
            return null;
        }

        $result = data_get($page->data, $key, $default);

        if ($result === $default) {
            return data_get($page, $key, $default);
        }

        return $result;
    }
}
