<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use Honeybee\Ui\Activity\Activity;
use Honeybee\Ui\Activity\ActivityMap;
use Honeybee\Ui\Activity\Url;

class Honeybee_Core_Rels_RelsSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);

        $this->setAttribute(
            'activity_data',
            json_encode(
                (array)$this->getAttribute('activity'),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }
}
