<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use AgaviValidator;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

/**
 * Validates a given URL (e.g. REFERER header) whether it is somewhat safe to
 * use as a redirect value (e.g. after login to return to the previous page).
 */
class RedirectValidator extends AgaviValidator
{
    /**
     * Validates the given argument.
     *
     * @return boolean result of the validation
     */
    protected function validate()
    {
        $value = $this->getData($this->getArgument());

        if ($this->isValidRedirectUrl($value)) {
            $this->export($value);
            return true;
        }

        return false;
    }

    /**
     * Checks whether the given value is a valid URL that may probably be safely used as a redirect url.
     *
     * @param string $value URL to validate
     *
     * @return boolean true if the url is correct
     */
    protected function isValidRedirectUrl($value)
    {
        $validator = Validation::createValidator();

        $constraints = array(
            new Constraints\Type([ 'type' => 'string' ]),
            new Constraints\Length([
                'min' => $this->getParameter('min_length', 10),
                'max' => $this->getParameter('max_length', 4000)
            ]),
            new Constraints\Url([
                'protocols' => $this->getParameter('allowed_protocols', [ 'http', 'https' ])
            ]),
        );

        if ($this->getParameter('check_base_href', true)) {
            $constraints[] = new Constraints\Callback([ 'callback' => [ $this, 'hasCorrectBaseHref' ] ]);
        }

        $violations = new ConstraintViolationList();
        foreach ($constraints as $constraint) {
            $violations->addAll($validator->validate($value, $constraint));
        }

        if ($violations->count() === 0) {
            return true;
        } else {
            $this->getContext()->getLoggerManager()->logTo(
                'default',
                \AgaviLogger::WARNING,
                __METHOD__,
                $violations->__toString()
            );

            return false;
        }
    }

    /**
     * @param string $url referrer url to check against base href of this application
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function hasCorrectBaseHref($url, ExecutionContextInterface $context)
    {
        if (!is_string($url)) {
            $context->addViolation('URL is not a string.');
        } else {
            $base_href = $this->getContext()->getRouting()->getBaseHref();
            if (strpos($url, "$base_href", 0) !== 0) {
               $context->addViolation('URL does not start with base href of this application. Same origin violation.');
            }
        }
    }
}
