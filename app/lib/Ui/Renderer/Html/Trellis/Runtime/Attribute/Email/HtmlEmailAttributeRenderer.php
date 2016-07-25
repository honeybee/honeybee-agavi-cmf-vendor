<?php

namespace Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\Email;

use Honeybee\Common\Util\StringToolkit;
use Honeybee\Ui\Renderer\Html\Trellis\Runtime\Attribute\HtmlAttributeRenderer;

class HtmlEmailAttributeRenderer extends HtmlAttributeRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        $view_scope = $this->getOption('view_scope', 'missing_view_scope.collection');
        if (StringToolkit::endsWith($view_scope, 'collection')) {
            return $this->output_format->getName() . '/attribute/email/as_itemlist_item_cell.twig';
        }

        return $this->output_format->getName() . '/attribute/email/as_input.twig';
    }
}
