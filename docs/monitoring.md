# Monitoring

- [Monitoring](#monitoring)
  - [Application Health](#application-health)
  - [Application Status](#application-status)
  - [Custom status](#custom-status)

Honeybee applications have a `/status` and a `/status/health` page that may be used for monitoring and alerting.

## Application Health

The health check is accessible via commandline or URL. It returns `WORKING` or `FAILING` with a respective HTTP status code of 200 or 500 (or console exitcode 0 or 1). It does similiar checks as the status page.

- CLI: ```bin/cli health``` (exitcode 0 for `WORKING` and 1 for `FAILING`)
- URL: ```/status/health``` (HTTP status code 200 for `WORKING` or 500 for `FAILING`)

The health check is accessible without being authenticated by default. Changing the ```app_health.is_secure``` setting to `true` in `settings.xml` enables authentication.

## Application Status

The status page is accessible via commandline and via URL. The report can provide more details by specifying the `v` or `verbose` parameter.

The status page compiles all status reports of defined connections (`connections.xml`) and when one is `FAILING` the status of the application is `FAILING`. Otherwise the application's status is `WORKING`. More detailed information is output as well.

- CLI:
    - ```bin/cli status``` (exitcode 0 for `WORKING` and 1 for `FAILING`, exitcode 127 for unexpected errors)
    - ```bin/cli status -v``` or ```bin/cli status -verbose```
- URL as HTML:
    - ```/status```
    - ```/status?v=1``` or ```/status?verbose=true```
- URL as XML:
    - ```/status.xml```
    - ```/status.xml?v=1``` or ```/status.xml?verbose=true```
- URL as JSON:
    - ```/status.json```
    - ```/status.json?v=1``` or ```/status.json?verbose=true```
- URL as TEXT:
    - ```/status.txt```
    - ```/status.txt?v=1``` or ```/status.txt?verbose=true```
    - ideal for monitoring tools

The returned formats usually include a `status` property or element that has one of the values `WORKING` or `FAILING`.

The application is working correctly when calling e.g. ```/status.json```, getting a HTTP 200 status code and a `status` property with the string value `WORKING`. In all other cases there are most probably problems. An HTTP status code of 401 or 500 may be returned depending on failure to auth or retrieving the application's status. HTTP status 405 is returned for non-GET requests.

The TEXT URL always returns either HTTP status 200 or 500 depending on application's status and thus is suitable for tools like [icinga](https://www.icinga.org/) to implement a check based on the returned status without parsing the body content and then use the returned output as content for emails etc. when the status is `FAILING`.

The status page is secured by default. This may be changed via `settings.xml` by changing the ```app_status.is_secure``` setting to `false`. Beware though, that the status page returns sensitive information and should be secured via other means in that case (blocking the access to the URL via webserver or loadbalancer).

## Custom status

The status page checks the status of all defined connections by asking their connectors for a status. The returned status may be `WORKING`, `UNKNOWN` or `FAILING` and may contain sensitive information and a lot of details. Every `Connector` can implement the ```getStatus()``` method to do its own checks. A filesystem connector for example could not only check the availability of a directory, but also try to write a test file to that folder. Without changes the connectors that extend the abstract base `Connector` return an `UNKNOWN` status.

By default the status page is defined in the `app/config/routing.xml` and via ```system_action``` in the `settings.xml` file to enable using a different action from another module as the status checking page. The action and view can be found in [```app/modules/Honeybee_Core/impl/System/Status```](../app/modules/Honeybee_Core/impl/System/Status).

When writing your own connectors it is advisable to extend the existing abstract [Connector](https://github.com/honeybee/honeybee/blob/master/src/Infrastructure/DataAccess/Connector/Connector.php) and implement the `connect()` method. To provide a custom status check implement the ```getStatus()``` method as well. See the [ElasticsearchConnector](https://github.com/honeybee/honeybee/blob/master/src/Infrastructure/DataAccess/Connector/ElasticsearchConnector.php#L26) as an example. Status checks should be somewhat fast to not slow down the `/status` page generation.
