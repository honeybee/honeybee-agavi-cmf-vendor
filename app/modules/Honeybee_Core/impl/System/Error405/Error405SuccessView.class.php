<?php

use Honeygavi\Agavi\App\Base\ErrorView;

/**
 * Handles HTTP status 405 METHOD NOT ALLOWED errors for all supported output
 * types by usually logging the matched routes and returning an appropriate
 * response.
 */
class Honeybee_Core_System_Error405_Error405SuccessView extends ErrorView
{
    const DEFAULT_ERROR_TITLE = '405 Method Not Allowed';

    protected function getTitle()
    {
        return '405 Method Not Allowed';
    }

    protected function getMessage()
    {
        return 'The HTTP method of the request is not supported on this resource.';
    }

    protected function getHttpStatusCode()
    {
        return 405;
    }

    protected function getLogref()
    {
        return 'error405';
    }
}
