<?php

use Honeygavi\Agavi\App\Base\View;

/**
 * Handles presentation logic required for the %system_actions.module_disabled%
 * action.
 */
class Honeybee_Core_System_ModuleDisabled_ModuleDisabledSuccessView extends View
{
    const DEFAULT_MESSAGE = 'This module is disabled.';

    /**
     * Presentation logic for output type html. Returns http status code 503.
     *
     * @param \AgaviRequestDataHolder $request_data
     */
    public function executeHtml(AgaviRequestDataHolder $request_data)
    {
        $this->setupHtml($request_data);
        $message = $this->translation_manager->_(self::DEFAULT_MESSAGE);
        $this->setAttribute('_title', $message);
        $this->getResponse()->setHttpStatusCode('503');
        return $message;
    }

    /**
     * Presentation logic for output type json. Returns http status code 503.
     *
     * @param \AgaviRequestDataHolder $request_data
     */
    public function executeJson(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('503');
        return json_encode(array('message' => $this->translation_manager->_(self::DEFAULT_MESSAGE)));
    }

    /**
     * Presentation logic for output type xml. Returns http status code 503.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @return string XML content with 503 information
     */
    public function executeXml(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('503');
        return '<?xml version="1.0" encoding="UTF-8"?><message>' . $this->translation_manager->_(self::DEFAULT_MESSAGE) . '</message>';
    }

    /**
     * Presentation logic for output type atomxml. Returns http status code 503.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @return string XML content with 503 information
     */
    public function executeAtomxml(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('503');
        return '<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom"><title>' . $this->translation_manager->_(self::DEFAULT_MESSAGE) . '</title></feed>';
    }

    /**
     * Presentation logic for output type binary. Returns http status code 503
     * and a plaint text message.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @return string response with information message
     */
    public function executeBinary(AgaviRequestDataHolder $request_data) // @codingStandardsIgnoreEnd
    {
        $this->getResponse()->setHttpStatusCode('503');
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
        $this->getResponse()->setContent($this->translation_manager->_(self::DEFAULT_MESSAGE));
    }

    /**
     * Presentation logic for output type pdf. Returns http status code 503 and
     * a plain text message.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @return string response with information message
     */
    public function executePdf(AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('503');
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
        $this->getResponse()->setContent($this->translation_manager->_(self::DEFAULT_MESSAGE));
    }

    /**
     * Presentation logic for output type console. Returns error message with
     * shell exit code 126.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @return string response on STDERR with information message
     */
    public function executeConsole(AgaviRequestDataHolder $request_data)
    {
        $message = $this->translation_manager->_(self::DEFAULT_MESSAGE);

        if (!$this->getResponse()->getParameter('append_eol', true)) {
            $message .= PHP_EOL;
        }

        $this->getResponse()->setExitCode(126);

        /*
         * we just send stuff to STDERR as AgaviResponse::sendContent() uses fpassthru which
         * does not allow us to give the handle to Agavi via $rp->setContent() or return $handle
         * notice though, that the shell exit code will still be set correctly
         */
        if (php_sapi_name() === 'cli' && defined('STDERR')) {
            fwrite(STDERR, $message);
            fclose(STDERR);
        } else {
            return $message;
        }
    }
}
