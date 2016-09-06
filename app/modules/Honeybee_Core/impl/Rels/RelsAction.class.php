<?php

use Honeybee\Common\Error\RuntimeError;
use Honeybee\FrameworkBinding\Agavi\App\Base\Action;

class Honeybee_Core_RelsAction extends Action
{
    public function executeRead(AgaviRequestDataHolder $request_data)
    {
        $activity = null;

        try {
            $activity = $this->getServiceLocator()->getActivityService()->getActivity(
                $request_data->getParameter('activity_scope', ''),
                $request_data->getParameter('activity_name', '')
            );
        } catch (RuntimeError $e) {
            return $this->getNotFoundView('', 'Rel not found.');
        }

        $this->setAttribute('activity', $activity);

        return 'Success';
    }

    public function getDefaultViewName()
    {
        return 'Success';
    }

    /**
     * @return boolean
     */
    public function isSecure()
    {
        return true;
    }

}
