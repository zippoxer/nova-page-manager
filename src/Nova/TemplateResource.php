<?php

namespace OptimistDigital\NovaPageManager\Nova;

use Laravel\Nova\Resource;
use Illuminate\Http\Request;
use OptimistDigital\NovaPageManager\NovaPageManager;
use OptimistDigital\NovaLocaleField\Filters\LocaleFilter;
use Illuminate\Support\Str;

abstract class TemplateResource extends Resource
{
    use \Eminiarts\Tabs\TabsOnEdit;

    protected $templateClass;

    protected function getTemplateClass()
    {
        if (isset($this->templateClass)) return $this->templateClass;

        $templates = $this->type === 'page'
            ? NovaPageManager::getPageTemplates()
            : NovaPageManager::getRegionTemplates();

        if (isset($this->template)) {
            foreach ($templates as $template) {
                if ($template::$name == $this->template) {
                    $this->templateClass = new $template($this->resource);
                    $this->templateClass->page = $this;
                    break;
                }
            }
        }

        return $this->templateClass;
    }

    /**
     * Gets the template fields and separates them into an
     * array of two keys: 'fields' and 'panels'.
     *
     * @return array
     **/
    protected function getTemplateFieldsAndPanels(): array
    {
        $templateClass = $this->getTemplateClass();
        $templateFields = [];
        $templatePanels = [];

        $handleField = function (&$field) {
            if (!empty($field->attribute) && ($field->attribute !== 'ComputedField')) {
                if (empty($field->panel)) {
                    $field->attribute = 'data->' . $field->attribute;
                } else {
                    if ($field->panel === 'flat') {
                        $field->attribute = $field->attribute;
                    } else if ($field->panel === 'tabs' && isset($field->meta['tab'])) {
                        $field->attribute = Str::snake($field->meta['tab']) . '->' . $field->attribute;
                    } else {
                        $field->attribute = nova_page_manager_sanitize_panel_name($field->panel) . '->' . $field->attribute;
                    }

                    $field->attribute = 'data->' . $field->attribute;
                }
            } else {
                if ($field instanceof \Laravel\Nova\Fields\Heading) {
                    return $field->hideFromDetail();
                }
            }

            if (method_exists($field, 'hideFromIndex')) {
                return $field->hideFromIndex();
            }

            return $field;
        };

        if (isset($templateClass)) {
            $rawFields = $templateClass->fields(request());

            foreach ($rawFields as $field) {
                // Handle Panel
                if ($field instanceof \Laravel\Nova\Panel) {
                    $field->data = array_map(function ($_field) use (&$handleField) {
                        return $handleField($_field);
                    }, $field->data);

                    $templatePanels[] = $field;
                    continue;
                }

                // Handle Field
                $templateFields[] = $handleField($field);
            }
        }

        return [
            'fields' => $templateFields,
            'panels' => $templatePanels,
        ];
    }

    public function filters(Request $request)
    {
        if (NovaPageManager::hasNovaLang()) return [];

        return [
            (new LocaleFilter($this->resource->getTable() . '.locale'))->locales(NovaPageManager::getLocales()),
        ];
    }
}
