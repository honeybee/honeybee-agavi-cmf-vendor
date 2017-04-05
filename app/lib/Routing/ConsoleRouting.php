<?php

namespace Honeygavi\Routing;

use AgaviConfig;
use AgaviConsoleRouting;
use DateTime;
use Honeybee\Common\Util\StringToolkit;

class ConsoleRouting extends AgaviConsoleRouting
{
    public function gen($route, array $params = array(), $options = array())
    {
        list($uri, $parameters, $options, $extras, $is_null_route) = parent::gen($route, $params, $options);

        $cli_params = '';
        foreach ($parameters as $name => $value) {
            $cli_params .= sprintf(
                ' %s %s',
                escapeshellarg('-' . $name),
                escapeshellarg(self::getAsString($value))
            );
        }

        $extra_params = '';
        foreach ($extras as $name => $value) {
            $extra_params .= sprintf(
                ' %s %s',
                escapeshellarg('-' . $name),
                escapeshellarg(self::getAsString($value))
            );
        }

        $cmd = AgaviConfig::get('config.cmd.honeybee', 'bin/cli');
        if (array_key_exists('APPLICATION_DIR', $_SERVER)) {
            /*
             * on ubuntu/nginx the '_' was set to the relative "bin/cli" command that was used in CLI
             * on opensuse/nginx the '_' was set to the absolute path of the php executable
             * on windows the '_' is never available afaik
             */
            if (array_key_exists('_', $_SERVER) && StringToolkit::endsWith($_SERVER['_'], 'cli')) {
                $cmd = $_SERVER['_'];
            }
        }

        // TODO parameters that are part of the route pattern should not be appended here;
        //      anyone with a bit of time may have a look at the parent::gen() to fix this

        return $cmd . ' ' . $uri . $cli_params . $extra_params;
    }

    /**
     * Returns a string representation for the given argument. Specifically
     * handles known scalars or types like exceptions and Identifiable.
     *
     * @param mixed $var object, array or string to create textual representation for
     *
     * @return string for the given argument
     */
    public static function getAsString($var)
    {
        if (is_object($var)) {
            return self::getObjectAsString($var);
        } elseif (is_array($var)) {
            return print_r($var, true);
        } elseif (is_resource($var)) {
            return (string) sprintf('resource=%s', get_resource_type($var));
        } elseif (true === $var) {
            return 'true';
        } elseif (false === $var) {
            return 'false';
        } elseif (null === $var) {
            return 'null';
        }

        return (string) $var;
    }

    /**
     * Returns a string for the given object enhanced by various information if
     * the object is of a known type. The given object should implement a
     * `__toString()` method as otherwise the representation might be empty.
     *
     * @param mixed $obj object to create a string for
     *
     * @return string with simple object representation
     */
    public static function getObjectAsString($obj)
    {
        if ($obj instanceof Identifiable) {
            return $obj->getIdentifier();
        } elseif ($obj instanceof DateTime) {
            return $obj->format('c');
        } elseif (is_callable(array($obj, '__toString'))) {
            return $obj->__toString();
        } else {
            return json_encode($obj);
        }
    }
}
