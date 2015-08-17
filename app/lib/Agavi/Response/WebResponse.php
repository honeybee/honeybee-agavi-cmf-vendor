<?php

namespace Honeybee\FrameworkBinding\Agavi\Response;

use AgaviWebResponse;
use Closure;

/**
 * WebResponse handles HTTP responses.
 */
class WebResponse extends AgaviWebResponse
{
    /**
     * It is now possible to return closures in views for streaming content etc.
     *
     * @example: return function() {
     *     $writer = new XmlWriter();
     *     $writer->openURI('php://output');
     *     ...
     *     $writer->flush();
     * };
     *
     * @return void
     */
    public function sendContent()
    {
        if ($this->content instanceof Closure) {
            $callable = $this->content;
            $callable();
            return;
        }

        parent::sendContent();
    }
}
