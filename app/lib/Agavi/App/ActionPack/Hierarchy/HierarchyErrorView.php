<?php

namespace Honeybee\FrameworkBinding\Agavi\App\ActionPack\Hierarchy;

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\FrameworkBinding\Agavi\Validator\DisplayModeValidator;
use AgaviRequestDataHolder;

class HierarchyErrorView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        //parent::executeHtml($request_data);
        //$display_mode = $this->getDisplayMode($request_data);
        //$this->getLayer('content')->setTemplate('Collection/Collection' . ucfirst($display_mode) . 'Success');
        $this->setupHtml($request_data);
    }

    protected function getDisplayMode(AgaviRequestDataHolder $request_data)
    {
        return $request_data->getParameter('as', DisplayModeValidator::DISPLAY_MODE_TABLE);
    }
}
