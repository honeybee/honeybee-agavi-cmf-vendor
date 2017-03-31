<?php

use Honeygavi\Agavi\App\Base\Action;

class Honeybee_SystemAccount_User_LogoutAction extends Action
{
    public function executeRead(AgaviParameterHolder $request_data)
    {
        return $this->executeWrite($request_data);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function executeWrite(AgaviParameterHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->getContext()->getUser()->clearAttributes();
        $this->getContext()->getUser()->setAuthenticated(false);

        return 'Success';
    }

    public function isSecure()
    {
        return false;
    }
}
