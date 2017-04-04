<?php

namespace Honeygavi\Ui\Renderer\Console\Honeygavi\Ui\Activity;

use Honeygavi\Ui\Renderer\Text\Honeygavi\Ui\Activity\TextActivityMapRenderer;

class ConsoleActivityMapRenderer extends TextActivityMapRenderer
{
    protected function getDefaultTemplateIdentifier()
    {
        return 'console/activity_map/as_list.twig';
    }
}
