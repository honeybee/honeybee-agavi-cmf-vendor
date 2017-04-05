<?php

namespace Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Activity;

use Honeygavi\Ui\Renderer\Html\Honeygavi\Ui\Activity\HtmlActivityMapRenderer;

class HtmlPrimaryActivityMapRenderer extends HtmlActivityMapRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        return $this->output_format->getName() . '/activity_map/primary_activities.twig';
    }
}
