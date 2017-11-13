<?php

namespace Honeygavi\App\ActionPack\Resource\Task\Proceed;

use Honeygavi\App\Base\View;
use AgaviRequestDataHolder;

class ProceedSuccessView extends View
{
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        if ($this->hasAttribute('command') && $this->getContainer()->getRequestMethod() === 'write') {
            $this->redirectAfterWrite($request_data);
            return;
        }

        $this->setupHtml($request_data);
    }

    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $available_gates = $this->getAttribute('gates', []);

        $this->getResponse()->setContent(
            json_encode(
                array(
                    'state' => 'ok',
                    'data' => array(
                        'workflow_state' => $this->getAttribute('workflow_state'),
                        'gate_taken' => $this->getAttribute('gate_taken'),
                        'upcoming_state' => $this->getAttribute('upcoming_state', 'not-yet-provided')
                    )
                )
            )
        );
    }

    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        echo PHP_EOL . "Proceeded workflow from state: " . $this->getAttribute('workflow_state')
            . " via gate: " . $this->getAttribute('gate_taken')
            . ", upcoming state is: " . $this->getAttribute('upcoming_state', 'not-yet-provided') . PHP_EOL;
    }

    protected function redirectAfterWrite(AgaviRequestDataHolder $request_data)
    {
        $resource_type = $this->getAttribute('resource_type');
        $url = $this->user->getAttribute($resource_type->getPrefix(), 'collection_views');
        if (!empty($url)) {
            $this->getResponse()->setRedirect($url);
        } else {
            $this->getResponse()->setRedirect(
                $this->getContext()->getRouting()->gen(
                    'module.collection',
                    [ 'module' => $resource_type, 'sort' => 'modified_at:desc' ],
                    [ 'relative' => false ]
                )
            );
        }
    }
}
