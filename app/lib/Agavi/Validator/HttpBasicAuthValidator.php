<?php

namespace Honeygavi\Agavi\Validator;

use AgaviValidator;

/**
 * Validates the given authorization header to be a valid basic auth attempt.
 *
 * Exports 'username' and 'password' as an array under the key 'AUTH_REQUEST'
 * if no other export name was specified in the validator definition.
 */
class HttpBasicAuthValidator extends AgaviValidator
{
    /**
     * Validates the given argument.
     *
     * @return boolean result of the validation
     */
    protected function validate()
    {
        $auth = $this->getData($this->getArgument());
        if (null !== $auth) {
            if (0 === stripos($auth, 'basic ')) {
                $credentials = explode(':', base64_decode(substr($auth, 6)), 2);
                if (count($credentials) === 2) {
                    list($username, $password) = $credentials;
                    $this->export(
                        [
                            'username' => $username,
                            'password' => $password
                        ],
                        $this->getParameter('export', 'AUTH_REQUEST')
                    );
                    return true;
                }
            }
        }

        return false;
    }
}
