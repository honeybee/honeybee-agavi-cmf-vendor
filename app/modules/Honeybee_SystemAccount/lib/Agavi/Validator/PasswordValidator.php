<?php

namespace Honeybee\SystemAccount\Agavi\Validator;

use Honeybee\Infrastructure\Security\Auth\CryptedPasswordHandler;
use AgaviValidator;
use SplFileObject;

/**
 * Simple validator that checks minimum complexity rules of a (password) string
 * by counting the number of occurrences of certain character classes and
 * optionally checking for similarity to other request parameters and even
 * optionally matching the string against entries from a blacklist text file.
 *
 * Parameters:
 *
 * - min_decimal_numbers: minimum number of decimal numbers required, defaults to 0
 * - min_uppercase_chars: minimum number of uppercase characters required, defaults to 0
 * - min_lowercase_numbers: minimum number of lowercase numbers required, defaults to 0
 * - min_string_length: minimum number of characters required, defaults to 6
 * - max_string_length: maximum number of characters required, defaults to 255
 *
 * - argument_names_for_similarity_check: single argument name or an array of
 *     argument names (like the username etc.) to enable a similarity comparison
 *     within the constraints given in the next two parameters.
 *     If this parameter is not defined, the similarity thresholds below are not
 *     taken into account as no comparisons are done.
 * - similarity_percentage_threshold: maximum allowed similarity of given
 *     similarity check arguments and the password, defaults to 80 percent
 *     (above that threshold the strings are deemed too similar)
 * - minimum_levenshtein_distance: minimum number of characters that must be
 *     different between similarity check arguments and the password
 *
 * - common_passwords_blacklist_file: optional parameter to specify a text file
 *     with common or blacklisted passwords (line by line). If the given
 *     password candidate matches (case-sensitive) one of the strings in the
 *     file, the validator fails. Please note, that this features is not speed
 *     optimized. Simple test takes about a second for about 50000 entries.
 *
 *
 * Usage example:
 *
 * <pre><code>
 *
 * <validator class="PasswordComplexityValidator" name="minimum_password_complexity">
 *     <argument>login_pass</argument>
 *     <error>  Your password does not meet the minimum complexity rules.&lt;br/&gt;&lt;br/&gt;
 *It must contain at least 6 characters with at least 1 uppercase character, 1 lowercase character and 1 decimal number.
 *Please note, that it should also not be too similar to your login name, email or company name.</error>
 *     <ae:parameters>
 *         <ae:parameter name="min_decimal_numbers">1</ae:parameter>
 *         <ae:parameter name="min_uppercase_chars">1</ae:parameter>
 *         <ae:parameter name="min_lowercase_chars">1</ae:parameter>
 *         <ae:parameter name="min_string_length">6</ae:parameter>
 *         <ae:parameter name="max_string_length">32</ae:parameter>
 *
 *         <ae:parameter name="argument_names_for_similarity_check">
 *             <ae:parameter>login_name</ae:parameter>
 *             <ae:parameter>email</ae:parameter>
 *             <ae:parameter>company_name</ae:parameter>
 *         </ae:parameter>
 *         <ae:parameter name="similarity_percentage_threshold">80</ae:parameter>
 *         <ae:parameter name="minimum_levenshtein_distance">4</ae:parameter>
 *
 *         <ae:parameter name="common_passwords_blacklist_file">%core.app_dir%/path/to/common_or_blacklisted_passwords.txt</ae:parameter>
 *     </ae:parameters>
 * </validator>
 *
 * </code></pre>
 *
 * @author Steffen Gransow <steffen.gransow@mivesto.de>
 */
class PasswordValidator extends AgaviValidator
{
    const ERR_MISMATCH = 'repeat_password_mismatch';

    const ERR_SIMILARITY = 'password_too_similar';

    const ERR_UPPERCASE_TOKENS = 'not_enough_uppercase_tokens';

    const ERR_LOWERCASE_TOKENS = 'not_enough_lowercase_tokens';

    const ERR_NUMERIC_TOKENS = 'not_enough_numeric_tokens';

    const ERR_SPECIAL_CHARS = 'not_enough_special_chars';

    const ERR_TOO_SHORT = 'too_short';

    const ERR_TOO_LONG = 'too_long';

    const ERR_EASY_GUESSABLE = 'too_easy_to_guess';

    protected function validate()
    {
        $success = false;
        $password_candidate = $this->getData($this->getArgument());

        $this->checkPasswordComplexity($password_candidate);

        if (($repeat_password_argumentName = $this->getParameter('repeat_password_argument_name'))) {
            $repeat_password_argument = $this->getData($repeat_password_argumentName);

            if ($repeat_password_argument !== $password_candidate) {
                $this->throwError(self::ERR_MISMATCH);
            }
        }

        if (true === $this->getParameter('clear_password', true)) {
            $this->export(null, $this->getArgument());
            $this->export(null, $this->getParameter('repeat_password_argument_name'));
        }

        $errors = $this->incident ? $this->incident->getErrors() : array();
        $success = (count($errors) === 0);

        if ($success) {
            $password_handler = new CryptedPasswordHandler();
            $this->export($password_handler->hash($password_candidate), $this->getArgument());
        }

        return $success;
    }

    /**
     * @see http://www.php.net/manual/en/regexp.reference.unicode.php for
     *      a reference of the available character properties in PHP regex
     *
     * @param string $candidate string or password to check against configured complexity rules
     *
     * @return boolean True|false whether string is complex enough according to given complexity rules.
     */
    protected function checkPasswordComplexity($candidate)
    {
        $uppercase_rule = '/\p{Lu}/u'; // Unicode character with property "Upper case letter" -> simplified alternative: '/[A-Z]/'
        $min_uppercase_no = (int)$this->getParameter('min_uppercase_chars', 0);
        if (preg_match_all($uppercase_rule, $candidate, $matches) < $min_uppercase_no) {
            $this->throwError(self::ERR_UPPERCASE_TOKENS);
        }

        $lowercase_rule = '/\p{Ll}/u'; // Unicode character with property "Lower case letter" -> simplified alternative: '/[a-z]/'
        $min_lowercase_no = (int)$this->getParameter('min_lowercase_chars', 0);
        if (preg_match_all($lowercase_rule, $candidate, $matches) < $min_lowercase_no) {
            $this->throwError(self::ERR_LOWERCASE_TOKENS);
        }

        $numbers_rule = '/\p{Nd}/u'; // Unicode character with property "Decimal number" -> simplified alternative: '/[0-9]/' or '/\d/';
        $min_numbers_no = (int)$this->getParameter('min_decimal_numbers', 0);
        if (preg_match_all($numbers_rule, $candidate, $matches) < $min_numbers_no) {
            $this->throwError(self::ERR_NUMERIC_TOKENS);
        }

        $min_length = (int)$this->getParameter('min_string_length', 6);
        if (mb_strlen($candidate) < $min_length) {
            $this->throwError(self::ERR_TOO_SHORT);
        }

        $max_length = (int)$this->getParameter('max_string_length', 255);
        if (mb_strlen($candidate) > $max_length) {
            $this->throwError(self::ERR_TOO_LONG);
        }

        if ($this->isCommonPassword($candidate)) {
            $this->throwError(self::ERR_EASY_GUESSABLE);
        }
    }

    /**
     * Checks whether the given string is included in a configured text file.
     *
     * @param string $pwd password or username or similar candidate to check
     *
     * @return boolean True, if the given string was found in the configured blacklist file. False otherwise
     *
     * @throws FileNotFoundException If configured file is not readable.
     */
    protected function isCommonPassword($pwd)
    {
        $file_name = $this->getParameter('common_passwords_blacklist_file', false);
        if ($file_name === false) {
            return false;
        }

        if (!is_readable($file_name)) {
            throw new FileNotFoundException('File "' . $file_name . '" is not readable.');
        }

        $file = new SplFileObject($file_name);
        foreach ($file as $line) {
            // TODO: case insensitive comparison switch as parameter?
            if (false !== mb_strpos($line, $pwd)) {
                return true;
            }
        }

        return false;
    }
}
