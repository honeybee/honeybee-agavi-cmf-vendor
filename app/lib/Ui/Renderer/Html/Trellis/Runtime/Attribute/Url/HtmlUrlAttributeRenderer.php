<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\Url;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;
use Trellis\Runtime\Attribute\Url\UrlAttribute;

class HtmlUrlAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        if (StringToolkit::endsWith($view_scope, 'collection')) {
            return $this->output_format->getName() . '/attribute/url/as_itemlist_item_cell.twig';
        }

        return $this->output_format->getName() . '/attribute/url/as_input.twig';
    }

    protected function getTemplateParameters()
    {
        $params = parent::getTemplateParameters();

        $open_in_blank = $this->getOption('open_in_blank', true);
        $params['open_in_blank'] = $open_in_blank;

        $params['maxlength'] = $this->getOption(
            'maxlength',
            $this->attribute->getOption(UrlAttribute::OPTION_MAX_LENGTH)
        );

        return $params;
    }

    protected function getDefaultTranslationKeys()
    {
        return array_replace(parent::getDefaultTranslationKeys(), [ 'pattern' ]);
    }
}