<?php

use Honeygavi\App\Base\View;

/**
 * Handles the presentation logic for the %system_actions.secure% action.
 */
class Honeybee_Core_System_Secure_SecureSuccessView extends View
{
    const DEFAULT_MESSAGE = 'Permission denied.';

    /**
     * Returns http status code 403 and a simple message.
     *
     * @param \AgaviRequestDataHolder $request_data
     */
    public function executeHtml(\AgaviRequestDataHolder $request_data)
    {
        $message = $this->translation_manager->_(self::DEFAULT_MESSAGE);
        $this->setAttribute('_title', $message);
        $this->getResponse()->setHttpStatusCode('403');
        // TODO introduce template and show nice hint and link to login or even the login input template directly
        return "<p>$message</p>";
    }

    /**
     * Returns http status code 403 and a json message.
     *
     * @param \AgaviRequestDataHolder $request_data
     */
    public function executeJson(\AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('403');
        return json_encode(array('message' => $this->translation_manager->_(self::DEFAULT_MESSAGE)));
    }
    /**
     * Presentation logic for output type xml. Returns http status code 403.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @return string XML content with 403 information
     */
    public function executeXml(\AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('403');
        $message = $this->translation_manager->_(self::DEFAULT_MESSAGE);
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?><message>$message</message>";
    }

    /**
     * Presentation logic for output type atomxml. Returns http status code 403.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @return string XML content with 403 information
     */
    public function executeAtomxml(\AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('403');
        $message = $this->translation_manager->_(self::DEFAULT_MESSAGE);
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?><feed xmlns=\"http://www.w3.org/2005/Atom\"><title>$message</title></feed>";
    }

    /**
     * Presentation logic for output type binary. Returns http status code 403
     * and a plaint text message.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @return string response with information message
     */
    public function executeBinary(\AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('403');
        $this->getResponse()->setContentType('text/plain');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'inline');
        $this->getResponse()->setContent($this->translation_manager->_(self::DEFAULT_MESSAGE));
    }

    /**
     * Presentation logic for output type pdf. Returns http status code 403 and
     * a plain text message.
     *
     * @param \AgaviRequestDataHolder $request_data
     *
     * @return string response with information message
     */
    public function executePdf(\AgaviRequestDataHolder $request_data)
    {
        $this->getResponse()->setHttpStatusCode('403');
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
    public function executeConsole(\AgaviRequestDataHolder $request_data)
    {
        return $this->cliError($this->translation_manager->_(self::DEFAULT_MESSAGE), 126);
    }

    /**
     * Handles non-existing methods. This includes mainly the not implemented
     * handling of certain output types. This returns HTTP status code 403 by default.
     *
     * @param string $method_name
     * @param array $arguments
     */
    public function __call($method_name, $arguments)
    {
        if (preg_match('~^(execute)([A-Za-z_]+)$~', $method_name)) {
            if ($this->getResponse() instanceof AgaviWebResponse) {
                $this->getResponse()->setHttpStatusCode(403);
            } elseif ($this->getResponse() instanceof AgaviConsoleResponse) {
                $this->getResponse()->setExitCode(126); // "permission problem or command is not an executable"
            }
        }
    }
}
