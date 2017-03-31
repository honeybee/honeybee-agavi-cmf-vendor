<?php

namespace Honeygavi\Agavi\App\ActionPack\Suggestions;

use AgaviRequestDataHolder;
use Honeybee\Common\Error\RuntimeError;
use Honeygavi\Agavi\App\Base\View;
use Trellis\Runtime\Attribute\AttributeValuePath;

class SuggestionsSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $parameters)
    {
        var_dump(json_encode(array_values($this->getSuggestions())));
        exit;
    }

    public function executeJson(AgaviRequestDataHolder $parameters)
    {
        $this->getResponse()->setContent(
            json_encode(
                [
                    'state' => 'ok',
                    'data' => array_values($this->getSuggestions())
                ]
            )
        );
    }

    protected function getSuggestions()
    {
        $display_attribute_names = $this->getAttribute('display_fields', []);
        $resource_type = $this->getAttribute('resource_type');
        $query_result = $this->getAttribute('query_result');

        foreach ($display_attribute_names as $display_attribute_name) {
            if (!$resource_type->hasAttribute($display_attribute_name)) {
                throw new RuntimeError(
                    sprintf(
                        'Non existant display_field "%s" given for type %s',
                        $display_attribute_name,
                        $resource_type->getName()
                    )
                );
            }
        }

        $suggestions = [];
        foreach ($query_result->getResults() as $resource) {
            $suggestion = [ 'identifier' => $resource->getIdentifier() ];
            foreach ($display_attribute_names as $display_attribute_name) {
                $suggestion[$display_attribute_name] = AttributeValuePath::getAttributeValueByPath($resource, $display_attribute_name);
            }
            $suggestions[$resource->getIdentifier()] = $suggestion;
        }

        return $suggestions;
    }
}
