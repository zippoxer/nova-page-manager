<?php

namespace OptimistDigital\NovaPageManager;

use Laravel\Nova\Nova;
use Laravel\Nova\Tool;
use OptimistDigital\NovaPageManager\Models\Page;

class NovaPageManager extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
        Nova::script('nova-page-manager-script', __DIR__ . '/../dist/js/nova-page-manager-resources.js');
    }

    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return \Illuminate\View\View
     */
    public function renderNavigation()
    {
        return view('nova-page-manager::navigation');
    }

    public static function draftsEnabled(): bool
    {
        return config('nova-page-manager.drafts_enabled', false);
    }

    public static function getTemplates(): array
    {
        $templates = config('nova-page-manager.templates', []);
        return array_filter($templates, function ($template) {
            return class_exists($template);
        });
    }

    public static function getPageTemplates(): array
    {
        return array_filter(static::getTemplates(), function ($template) {
            return $template::$type === 'page';
        });
    }

    public static function getRegionTemplates(): array
    {
        return array_filter(static::getTemplates(), function ($template) {
            return $template::$type === 'region';
        });
    }

    public static function getLocales(): array
    {
        $localesConfig = config('nova-page-manager.locales', ['en' => 'English']);
        if (is_callable($localesConfig)) return call_user_func($localesConfig);
        if (is_array($localesConfig)) return $localesConfig;
        return ['en' => 'English'];
    }

    public static function getPagesTableName(): string
    {
        return config('nova-page-manager.table', 'nova_page_manager') . '_pages';
    }

    public static function getRegionsTableName(): string
    {
        return config('nova-page-manager.table', 'nova_page_manager') . '_regions';
    }

    public static function getPageUrl(Page $page)
    {
        $getPageUrl = config('nova-page-manager.page_url');
        return isset($getPageUrl) ? call_user_func($getPageUrl, $page) : null;
    }

    public static function hasNovaLang(): bool
    {
        return class_exists('\OptimistDigital\NovaLang\NovaLang');
    }
}
