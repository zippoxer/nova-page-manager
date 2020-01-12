<?php

namespace OptimistDigital\NovaPageManager;

use Laravel\Nova\Nova;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OptimistDigital\NovaPageManager\Http\Middleware\Authorize;
use OptimistDigital\NovaPageManager\Nova\Page;
use OptimistDigital\NovaPageManager\Nova\Region;
use OptimistDigital\NovaPageManager\Commands\CreateTemplate;
use Illuminate\Support\Facades\Validator;
use OptimistDigital\NovaPageManager\Models\Page as PageModel;
use Illuminate\Support\Facades\Blade;

class ToolServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PageModel::class, function ($app) {
            return PageModel::current();
        });

        $this->app->singleton(Template::class, function ($app) {
            $page = PageModel::current();

            if (!$page) {
                return null;
            }

            foreach (config('nova-page-manager.templates') as $template) {
                if ($template::$name === $page->template) {
                    return $template::fromModel($page);
                }
            }

            return null;
        });

        foreach (config('nova-page-manager.templates') as $template) {
            if ($template::$type !== 'page') {
                continue;
            }

            $this->app->singleton($template, function ($app) use ($template) {
                $page = PageModel::where('template', $template::$name)
                    ->where('locale', app()->getLocale())
                    ->firstOrFail();

                return $template::fromModel($page);
            });
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'nova-page-manager');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'nova-page-manager-migrations');

        $this->publishes([
            __DIR__ . '/../config/nova-page-manager.php' => config_path('nova-page-manager.php'),
        ], 'config');

        $this->app->booted(function () {
            $this->routes();
        });

        // Register resources
        $pageResource = config('nova-page-manager.page_resource') ?: Page::class;
        $regionResource = config('nova-page-manager.region_resource') ?: Region::class;

        Nova::resources([
            $pageResource,
            $regionResource,
        ]);

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateTemplate::class
            ]);
        }

        // Custom validation
        Validator::extend('alpha_dash_or_slash', function ($attribute, $value, $parameters, $validator) {
            if (!is_string($value) && !is_numeric($value)) return false;
            if ($value === '/') return true;
            return preg_match('/^[\pL\pM\pN_-]+$/u', $value) > 0;
        }, 'Field must be alphanumeric with dashes or underscores or a single slash.');

        // Register template macro for routes.
        IlluminateRoute::macro('template', function ($template = null) {
            if (is_null($template)) {
                return $this->action['nova-pagemanager-template'] ?? null;
            }

            $this->action['nova-pagemanager-template'] = $template;

            return $this;
        });

        // Register blade directives.
        Blade::directive('page', function ($key) {
            return '<?= Page::get(\'' . trim($key, "'\"") . '\'); ?>';
        });
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) return;

        Route::middleware(['nova', Authorize::class])
            ->prefix('nova-vendor/nova-page-manager')
            ->group(__DIR__ . '/../routes/api.php');
    }
}
