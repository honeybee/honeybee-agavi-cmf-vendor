# Logging

- [Logging](#logging)
  - [Usage examples](#usage-examples)
  - [Logging to specific loggers](#logging-to-specific-loggers)
  - [Logging via Monolog](#logging-via-monolog)
  - [Additional debugging information](#additional-debugging-information)
  - [Logging in development environments](#logging-in-development-environments)
    - [Pitfalls of logging via HTTP headers](#pitfalls-of-logging-via-http-headers)
  - [Text representation of well known types](#text-representation-of-well-known-types)
  - [PSR-3 compatible logging](#psr-3-compatible-logging)
  - [Support for other logging libraries](#support-for-other-logging-libraries)
  - [Logging of PHP errors on shutdown](#logging-of-php-errors-on-shutdown)
  - [Custom shutdown handlers](#custom-shutdown-handlers)
  - [Suggestions](#suggestions)
  - [TBD / Ideas / Misc](#tbd--ideas--misc)

Logging is configured via `app/config/logging.xml`. This file includes the file
`app/project/config/logging.xml` if it exists. In that file you may specify your
own loggers, logger appenders and layouts. If the provided loggers and appenders
are not sufficient you can use the `Monolog` logging library with all handlers,
processors and formatters.

The available log levels of `Honeybee` are `TRACE`, `DEBUG`, `INFO`, `NOTICE`,
`WARNING`, `ERROR`, `ALERT`, `CRITICAL`, `EMERGENCY`. To make use of these you
should create an `\AgaviLoggerMessage` instance and give that to the wanted
`Logger` instance via the `LoggerManager`.

As the creation of those message instances is a lot of typing there are some
conveniences available in _actions_ and _views_. This includes the following
predefined methods for all available log levels:

- `logTrace()`
- `logDebug()`
- `logInfo()`
- `logNotice()`
- `logWarning()`
- `logError()`
- `logAlert()`
- `logCritical()`
- `logEmergency()`

and a `getLoggerName()` method in the base classes. The `getLoggerName()` method
returns the name of the logger to use for the builtin `log<Level>()` mathod
calls. The methods use that logger with a default scope of the current class
name and the log level from their name. That is, if you have actions that should
not log to the default log, but use their own topic logging you can just
override the `getLoggerName()` method with an existing logger name from the
`logging.xml` file to make your action use that instead of the default logger
for all logging calls via the `$this->log<Level>()` methods.

## Usage examples

The following are a few ways to log messages in _actions_ and _views_:

```php
$this->logDebug('Trying to import entries into', $this->getModule(), "for the specified consumer '$consumer_name'.");

$this->logError(
    'Import for {module} and consumer {consumer} failed. Exception: {cause}',
    array(
        'module' => $this->getModule(),
        'consumer' => $consumer_name,
        'cause' => $exception,
        'scope' => 'Import'
    )
);

$this->logError('Import for', $this->getModule(), 'and consumer', $consumer_name, 'failed. Exception was:', $e->getMessage());
$this->logTrace('Details from Validation:', $this->getContainer()->getValidationManager(), $exception, PHP_EOL . "\nwoohooo\n\n");
$this->logDebug($this->getModule(), 'is invalid');

$this->logTrace('Everybody get down, this {beep}', array('beep' => 'is a robbery!!!11', 'scope' => 'YOLO'));

$this->logCritical('{fail}', array('fail' => $e));
$this->logCritical($e);
```

All of the above method calls are convenience shortcuts of the default Agavi
way of logging which usually is: get the logger manager from the context and
then log to a specific logger or log a message to all loggers.

```php
$lm = $this->getContext()->getLoggerManager();
$lm->log(
    'This debug message goes to all loggers and its appenders',
    \AgaviLogger::DEBUG
);
$lm->getLogger('special')->log(
    'This error message goes to the special logger and its appenders',
    \AgaviLogger::ERROR
);
```

The method signature of the `\AgaviLoggerManager` log method is as follows:

```php
public function log($message, $loggerOrSeverity = null)
```

When you specify just a message it will be logged to all loggers as there is no
log level or severity given. When you specify a message and an Agavi log level a
message with that log level is created and logged to all loggers. If you supply
a logger instance or logger name that logger is used for logging.

## Logging to specific loggers

Honeybee adds some more convenience methods to log to specific loggers with the
addition of a log scope that is an additional string apart from the log level to
distinguish log messages. That scope is used by the default logger message
layout.

Log an `ERROR` message to the default logger with the default scope of
`Honeybee` (see `Honeybee\Agavi\Logging\LoggerManager`):

```php
$logger_manager->logError($log_message);
```

Log an `ERROR` message to the `error` logger with scope `SCOPE`:

```php
$logger_manager->logTo('error', \AgaviLogger::ERROR, 'SCOPE', $log_message);
```

Log an `ERROR` message to all loggers that are interested in `ERROR` messages
with a scope set to the current classname and a log message content that
consists of a simple string, an exception string representation (including a
stacktrace and some system information) and a `Honeybee\Dat0r\Document` string
representation (that includes it's identifier):

```php
$logger_manager->logTo(null, \AgaviLogger::ERROR, get_class($this), array(
    "some hints", $exception, $honeybee_document)
);
```

Log an `ERROR` message with scope set to the current classname to all loggers
that are responsible for `ERROR` messages.

```php
$logger_manager->logToAll(\AgaviLogger::ERROR, get_class($this), $log_message);
```

As you can see there is a multitude of ways to log to a specific logger or all
loggers. Please note, that appenders may decide to not handle specific log
messages depending on their log level even though that logger may feel
responsible for it. Whenever you're missing log messages you think should be
there it's good advice to check the `logging.xml` files for the appender
configuration of the used loggers.

## Logging via Monolog

There's a builtin [`Monolog`](https://github.com/Seldaek/monolog) logger
appender that can be utilized to use all of Monolog's handlers, processors and
formatters for logging. As the setup of a Monolog logger needs some code and
would not have been a good fit for some arbitrary parameters of the logger
appender in the `logging.xml` a common `setup` parameter was introduced that
takes a classname to instantiate. The interface to adhere to is
`Honeybee\Agavi\Logging\Monolog\IMonologSetup` which defines only one method:
`getMonologInstance(\AgaviLoggerAppender)`. Via such a class you're free to
create a `Monolog\Logger` instance with a configuration that suits your
requirements. To make the setup a bit more flexible you can use the parameters
of the appender instance given to the method. The appender parameters are
directly from the appropriate part of the `logging.xml` file.

There are some example setups in the `Honeybee/Agavi/Logging/Monolog` folder.
The `DefaultSetup` class creates a logger with a `FingersCrossedHandler` that
logs all messages of all severities to an application log file and the `syslog`
only when a message of a certain configurable threshold level appears (log level
`CRITICAL` by default). This means that the `syslog` and `critical.log` file are
empty as long as there are no log messages with a log level of `CRITICAL` within
a request. When the logger logs it logs all messages from that request.

## Additional debugging information

The default Monolog setup uses the `DefaultProcessor` that adds several system,
Agavi and application specific information to the `$extra` array of Monolog to
ease debugging in case of critical errors. This may look something like this:

```json
{
  "app_name":"Honeybee CMF",
  "agavi_context":"web",
  "agavi_environment":"development-vagrant",
  "agavi_version":"1.0.7",
  "php_version":"5.4.6-1ubuntu1.2",
  "system":"Linux honeybee-showcase.dev 3.5.0-17-generic #28-Ubuntu SMP Tue Oct 9 19:31:23 UTC 2012 x86_64",
  "pid":1254,
  "memory_usage":"13.75 MB",
  "memory_peak_usage":"14 MB",
  "remote_addr":"33.33.33.1",
  "x_forwarded_for":"",
  "request_uri":"/de/user/list",
  "request_method":"read",
  "matched_module_and_action":"/",
  "matched_routes":"locale, ot_xml, ot_html, ot_xhtml, user, user.list",
  "raw_user_agent":"Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0",
  "raw_referer":"http://honeybee-showcase.dev/de/"
}
```

Similar additional infos are added for authentication related log messages via
the `VerboseLoggerAppender` that is used for the `auth` logger. You can reuse
that verbose appender for other loggers as well.

## Logging in development environments

Other example Monolog setups are `FirePhpSetup` and `ChromePhpSetup` that allow
developers to see log messages within their browser (or other application) that
supports `FirePHP` or `ChromePHP` capabilities. This includes `Firebug` as an
extension of `Firefox` and the `ChromeLogger` extension in `Chrome`.

As an example create a `logging.xml` in `app/project/config/` and extend the
configuration to add `FirePHP` and `ChromePHP` logging to the
default logger in all environments whose names start with `development` for the
`web` context (while the environment name must not have a `-testing` suffix):

```xml
<ae:configurations xmlns:ae="http://agavi.org/agavi/config/global/envelope/1.0"
    xmlns="http://agavi.org/agavi/config/parts/logging/1.0"
    xmlns:xi="http://www.w3.org/2001/XInclude">
    <ae:configuration environment="^development.+(?!-testing)$" context="web">
        <loggers default="default">
            <logger name="default">
                <appenders>
                    <appender>firephp</appender>
                    <appender>chromephp</appender>
                </appenders>
            </logger>
        </loggers>
    </ae:configuration>
</ae:configurations>
```

Please note, that the `firephp` and `chromephp` appenders are
already available for you in `app/config/logging.xml` and thus don't need to be
defined in the `app/project/config/logging.xml` file. Beware that it's currently
not possible to remove appenders from already defined loggers or add appenders
with names that already exist.

### Pitfalls of logging via HTTP headers

One pitfall of `FirePHP` and `ChromePHP` is, that they work via HTTP headers.
Usually server software does have limitations for the size of headers that it
can handle. Fortunately the maximum supported header or buffer sizes are
configurable in most cases (as many large cookies blow up headers a lot).

For `nginx` and `PHP-FPM` via `FastCGI` you can increase the used buffer sizes
in ```/etc/nginx/fastcgi_params``` like this:

```
fastcgi_buffers         256 16k;
fastcgi_buffer_size     16k;
```

The above sets the buffer size to `16k + 256 * 16k` which is just above `4 MB`
and should be plenty to get an exception including stacktrace to `FirePHP` and
`ChromePHP` within the same request via HTTP headers.

## Text representation of well known types

By default the log methods accept various types as arguments. Usually strings or
objects with a ```__toString()``` method should be fine. Other types are
supported as well. At least the following special objects get a simple string
representation:

- `Honeybee\Dat0r\Module`: ```Module (Name=<Name>)```
- `Honeybee\Dat0r\Document`: ```Document (Identifier=<UUID of the document>)```
- `\DateTime`: ISO-8601 representation like ```2013-06-06T09:47:04+00:00```
- `\AgaviValidationManager`: ```Validation Errors (<error messages>)```
- `array`: ```print_r``` of the content

Other types are converted via ```json_encode``` or just casted to `string` which
may result in empty strings or brackets `[]`.

The PSR-3 compatible logger replaces occurances of known types in the same way
the default `Honeybee` logger does. This means, that exceptions will get their
stacktraces logged, `\DateTime` instances get an ISO-8601 representation etc.

There is a builtin convenience for the default log methods to use the logging
conventions of the PSR-3 `LoggerInterface`. You can use a template string as a
message and supply the second method argument as an associative array with keys
as placeholder names for the templated string and their values as replacements.

The following will log an error to the default logger with scope `FOO` and the
message text `This will be replaced.`:

```php
$this->getContext()->getLoggerManager->logError(
    "This will be {foo}",
    array(
        'foo' => 'replaced.',
        'scope' => 'FOO'
    )
);
```

## PSR-3 compatible logging

As the PSR-3 standard seems to get some traction there is support for this as
well. As the log levels Honeybee uses via Agavi differ a bit from PSR-3 and e.g.
`Monolog` log levels there are some ways to use PSR-3 compatible logging with
the default setup.

There is a `Honeybee\Agavi\Logging\Psr3Logger` class available that wraps an
`\AgaviLogger` instance. The `Honeybee\Agavi\Logging\Logger` has a convenience
method `getPsr3Logger()` that you can call to get the Agavi logger as a PSR-3
compatible logger instance:

```php
$this->getContext()->getLoggerManager()->getLogger('default')
    ->getPsr3Logger()
    ->log(
        \Psr\Log\LogLevel::CRITICAL,
        'Everybody get down, this {beep}',
        array(
            'beep' => 'is a robbery!!!11'
        )
    );
```

You get the logger you wish from the logger manager, ask it for a PSR-3
compatible instance of itself and log a message with the appropriate PSR-3 log
level and context needed.

## Support for other logging libraries

If you want to use other logging libraries you can create loggers, logger
appenders and logger layouts. If your goal is just to use another library
without redefining a lot of things in the `logging.xml` file, you should create
a logger appender for your library that converts the given Agavi logger message
to the appropriate format you want to use for your custom loggers.

To include e.g. an `Analog` handler for FirePHP logging you could do:

```php
<?php
namespace Your\Namespace\Logging;

use Analog\Logger;
use Analog\Handler\FirePHP;

/**
 * Sends AgaviLoggerMessages to an \Analog\Logger instance for FirePHP logging.
 */
class AnalogLoggerAppender extends \AgaviLoggerAppender
{
    /**
     * @var logger \Analog\Logger instance
     */
    protected $logger = array();

    /**
     * Retrieve the Analog instance to write to.
     *
     * @return \Analog\Logger instance to use for logging
     */
    protected function getAnalogInstance()
    {
        if (!$this->logger)
        {
            $this->logger = new Logger();
            $this->logger->handler(FirePHP::init());
        }

        return $this->logger;
    }

    /**
     * Write log data to this appender.
     *
     * @param \AgaviLoggerMessage $message log data to be written
     *
     * @throws \AgaviLoggingException if no layout is set or the stream can't be written
     */
    public function write(\AgaviLoggerMessage $message)
    {
        if(($layout = $this->getLayout()) === null)
        {
            throw new \AgaviLoggingException('No Layout set for logging.');
        }

        $analog_level = $this->convertAgaviLevelToAnalogLevel($message->getLevel());
        $analog_message = (string) $this->getLayout()->format($message);

        $this->getAnalogInstance()->log($analog_message, $analog_level);
    }

    /**
     * @param int $log_level_or_severity One of \AgaviLogger::DEBUG etc.
     *
     * @return int one of \Analog\Logger log levels
     */
    public function convertAgaviLevelToMonologLevel($log_level_or_severity)
    {
        if (!is_int($log_level_or_severity))
        {
            throw new \InvalidArgumentException("The given log level '$log_level_or_severity' is not an integer. Please use AgaviLogger::DEBUG or similar.");
        }

        $log_level_or_severity = abs($log_level_or_severity);

        // ...here be conversion magic...

        return $level;
    }

    /**
     * Execute the shutdown procedure.
     */
    public function shutdown()
    {
        // nothing to do here for Analog handler shutdown?
    }
}
```

You are not tied to only use custom logger appenders. Via `logging.xml` file
it's possible to use custom logger classes and custom layouts as well. You may
as well format and create the given messages directly in your appender or even
logger when you override those used by default. Further on it's possible to
change the default `AgaviLoggerMessage` class via `factories.xml` file.

## Logging of PHP errors on shutdown

The default shutdown handler of Honeybee has a map of PHP errors to Honeybee
log levels to log errors according to their severity. Errors that are of type
```E_ERROR``` or similar are `CRITICAL` errors. Others are `WARNING` or
`NOTICE` (in case of PHP strict errors) level. See `Honeybee\Agavi\Context` for
details.

## Custom shutdown handlers

The Honeybee context implementation enables all classes of the project to get a
Notification when the application is being shut down. To enable the notification
just implement the `Honeybee\Agavi\IShutdownListener` interface in your class.

This may be of use e.g. to free resources or shutdown services gracefully when
fatal errors occur in other parts of the application:

```php
// register yourself as a listener
$this->getContext()->addShutdownListener($this);

// implement shutdown handler in $this
public function onShutdown($error)
{
    // handle shutdown here by freeing resources here or whatever
}
```

## Suggestions

The following are all smart things to do while not all of them apply to all
situations or applications. They are listed here as a starting point before
you leave this file searching the web for "logging best practices":

- separate logs only where needed (sensitive information, different topics)
- prefer tools like `logrotate` over rolling file appenders
- use monitoring and alerting for your logs (certain conditions, file sizes)
- put logs somewhere to visualize and query them (use e.g. logstash + kibana)
- use different settings in production and development environments
- ...insert more here...

## TBD / Ideas / Misc

- `getDefaultLogger` vs. `scope` parameter
- PSR-3 `LoggerInterface` vs. variable number of method arguments
- `\AgaviLogger` bit fields explanation and usage
