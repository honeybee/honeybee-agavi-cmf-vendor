<?php

use Honeygavi\Agavi\App\ActionPack\Create\CreateAction;

class Honeybee_SystemAccount_User_CreateAction extends CreateAction
{
    public function executeWrite(AgaviRequestDataHolder $request_data)
    {
        if ($request_data->hasParameter('command')) {
            $create_command = $request_data->getParameter('command');
            $this->dispatchCommand($create_command);
            $this->setAttribute('command', $create_command);
        } else {
            $this->setAttribute('resource_type', $this->getProjectionType());
        }

        return 'Success';
    }
}
