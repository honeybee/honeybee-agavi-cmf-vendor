<?php

use Honeygavi\Agavi\App\Base\ErrorView;

/**
 * Handles HTTP status 501 NOT IMPLEMENTED errors for all supported output
 * types by usually logging the matched routes and returning an appropriate
 * response.
 */
class Honeybee_Core_System_Error501_Error501SuccessView extends ErrorView
{
    const DEFAULT_ERROR_TITLE = '501 Not Implemented';

    protected function getTitle()
    {
        return '501 Not Implemented';
    }

    protected function getMessage()
    {
        return 'The HTTP method of the request is not supported on this application.';
    }

    protected function getHttpStatusCode()
    {
        return 501;
    }

    protected function getLogref()
    {
        return 'error501';
    }
}
