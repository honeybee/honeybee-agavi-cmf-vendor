<?php

use Honeybee\FrameworkBinding\Agavi\App\Base\View;
use \AgaviRequestDataHolder;

class Honeybee_Core_ThemePreview_ThemePreviewSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
         $this->setupHtml($request_data);
         $tm = $this->getContext()->getTranslationManager();
         $this->setAttribute('_bodyclass', 'view theme-preview module-core');
         $this->setAttribute(
            'translation_domain', 
            AgaviConfig::get('core.theme_preview_translation_domain', $tm->getDefaultDomain())
        );
    }
}
