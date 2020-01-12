<?php

namespace OptimistDigital\NovaPageManager;

use Illuminate\Http\Request;
use OptimistDigital\NovaPageManager\Models\Page;
use Illuminate\Database\Eloquent\Model;

class Template
{
    public static $type = 'page';
    public static $name = '';
    public static $seo = false;
    public static $view = null;

    protected $resource = null;

    public $model = null;

    public function __construct($resource = null)
    {
        $this->resource = $resource;
    }

    function fields(Request $request): array
    {
        return [];
    }

    public static function fromModel(Model $model)
    {
        $instance = new static;

        $instance->model = $model;

        return $instance;
    }

    public function __get($name)
    {
        if (!isset($this->model)) {
            return null;
        }

        if (isset($this->model->data)) {
            return data_get($this->model->data, $name, null);
        }

        return data_get($this->model, $name, null);
    }
}
