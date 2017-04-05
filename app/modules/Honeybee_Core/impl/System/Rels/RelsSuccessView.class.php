<?php

use Honeygavi\App\Base\View;

class Honeybee_Core_System_Rels_RelsSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setAttribute(
            self::ATTRIBUTE_PAGE_TITLE,
            $this->getAttribute('activity')->getName() . ' :: ' . $this->getPageTitle()
        );

        $this->setupHtml($request_data);
    }
}
