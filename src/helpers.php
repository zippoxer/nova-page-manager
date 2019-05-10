<?php

use OptimistDigital\NovaPageManager\Interfaces\NovaResponseResolverInterface;
use OptimistDigital\NovaPageManager\Models\Page;
use OptimistDigital\NovaPageManager\Models\Region;
use Illuminate\Support\Collection;
use OptimistDigital\NovaPageManager\Models\TemplateModel;
use OptimistDigital\NovaPageManager\NovaPageManager;
use OptimistDigital\NovaPageManager\Template;

if (!function_exists('nova_get_pages_structure')) {
    function nova_get_pages_structure()
    {
        $formatPages = function (Collection $pages) use (&$formatPages) {
            $data = [];
            $pages->each(function ($page) use (&$data, &$formatPages) {
                $localeChildren = Page::where('locale_parent_id', $page->id)->get();
                $_pages = collect([$page, $localeChildren])->flatten();
                $_data = [
                    'locales' => $_pages->pluck('locale'),
                    'id' => $_pages->pluck('id', 'locale'),
                    'name' => $_pages->pluck('name', 'locale'),
                    'slug' => $_pages->pluck('slug', 'locale'),
                    'template' => $page->template,
                ];

                $children = Page::where('parent_id', $page->id)->get();
                if ($children->count() > 0) {
                    $_data['children'] = $formatPages($children);
                }

                $data[] = $_data;
            });
            return $data;
        };

        $parentPages = Page::whereNull('parent_id')->whereNull('locale_parent_id')->get();
        return $formatPages($parentPages);
    }
}

if (!function_exists('nova_get_regions')) {
    function nova_get_regions()
    {
        $formatRegions = function (Collection $regions) {
            $data = [];
            $regions->each(function ($region) use (&$data) {
                $localeChildren = Region::where('locale_parent_id', $region->id)->get();
                $_regions = collect([$region, $localeChildren])->flatten();
                $data[] = [
                    'locales' => $_regions->pluck('locale'),
                    'id' => $_regions->pluck('id', 'locale'),
                    'name' => $_regions->pluck('name', 'locale'),
                    'template' => $region->template,
                    'data' => $_regions->pluck('data', 'locale'),
                ];
            });
            return $data;
        };

        $parentRegions = Region::whereNull('locale_parent_id')->get();
        return $formatRegions($parentRegions);
    }
}

if (!function_exists('nova_get_page')) {

    function nova_get_page($pageId)
    {
        if (empty($pageId)) return null;
        $page = Page::find($pageId);
        if (empty($page)) return null;

        return [
            'locale' => $page->locale,
            'id' => $page->id,
            'name' => $page->name,
            'slug' => $page->slug,
            'data' => nova_resolve_page_data($page),
            'template' => $page->template,
        ];
    }
}

class FlexibleLoose extends \Whitecube\NovaFlexibleContent\Flexible {

    public function getLayouts() {
        return $this->layouts;
    }

}


if (!function_exists('access_protected_prop')) {
    function access_protected_prop($obj, $prop) {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }
}


if (!function_exists('nova_resolve_flexible_content_response')) {
    function nova_resolve_flexible_content_response($field, $layoutValues)
    {

        $data = [];

        foreach ($layoutValues as $layoutValue) {

            foreach (access_protected_prop($field, 'layouts') as $item) {

                $layoutName = access_protected_prop($item, 'name');

                if ($layoutName != $layoutValue['layout']) {
                    continue;
                }

                $row = [];
                $flexFields = access_protected_prop($item, 'fields');

                foreach ($layoutValue['attributes'] as $fieldName => $fieldValue) {

                    $subField = $flexFields->where('name', $fieldName)->first();

                    if ($subField) {
                        if (method_exists($subField, 'resolveResponseValue')) {
                            $data[$fieldName] = $subField->resolveResponseValue($fieldValue);
                        } else {
                            $row[$fieldName] = $fieldValue;
                        }
                    }
                }

                $data[] = $row;
            }
        }

        return $data;
    }
}

if (!function_exists('nova_resolve_page_fields')) {
    /**
     * @param TemplateModel $page
     * @return array
     */
    function nova_resolve_page_data(TemplateModel $page) {

        $findTemplateClass = function($tmpl) use($page) {
            return $tmpl::$name === $page->template;
        };

        $templateClass = collect(NovaPageManager::getPageTemplates())->first($findTemplateClass);

        /** @var Template $instance */
        $instance = new $templateClass();

        $fields = collect($instance->fields(request()));

        // $page->data is object, can't iterate that like a normal person
        $data = json_decode(json_encode($page->data), true);

        foreach ($data as $fieldName => $fieldValue) {

            $field = $fields->where('name', $fieldName)->first();

            if ($field->component == 'nova-flexible-content') {
                $data[$fieldName] = nova_resolve_flexible_content_response($field, $fieldValue);
                continue;
            }

            if ($field && $field->name == $fieldName) {

                if (method_exists($field, 'resolveResponseValue')) {
                    $data[$fieldName] = $field->resolveResponseValue($fieldValue);
                }
            }
        }

        return $data;
    }
}
