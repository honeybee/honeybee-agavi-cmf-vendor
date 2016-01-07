# Monitoring

- [Monitoring](#monitoring)
  - [Access](#access)
  - [Custom status](#custom-status)

Honeybee applications have a `/status` page, that may be used for monitoring and alerting. The status page compiles all status reports of defined connections (`connections.xml`) and when one is `FAILING` the status of the application is `FAILING`. Otherwise the application's status is `WORKING`. More detailed information is output as well.

## Access

The status page is accessible via commandline and via URL. The report can be made more verbose by specifying the `v` or `verbose` parameter.

- CLI:
    - ```bin/cli status```
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

The returned formats usually include a `status` property or element that has one of the values `WORKING` or `FAILING`.

The application is working correctly when calling e.g. ```/status.json```, getting a HTTP 200 status code and a `status` property with the string value `WORKING`. In all other cases there are most probably problems. An HTTP status code of 401 or 500 may be returned depending on failure to auth or retrieving the application's status.

The status page is secured by default. This may be changed via `settings.xml` by changing the ```app_status.is_secure``` setting to `false`. Beware though, that the status page returns sensitive information and should be secured via other means in that case (blocking the access to the URL via webserver or loadbalancer).

## Custom status

The status page checks the status of all defined connections by asking their connectors for a status. The returned status may be `WORKING`, `UNKNOWN` or `FAILING` and may contain sensitive information and a lot of details. Every `Connector` can implement the ```getStatus()``` method to do its own checks. A filesystem connector for example could not only check the availability of a directory, but also try to write a test file to that folder. By default the connectors that extend the abstract base `Connector` return an `UNKNOWN` as status.

By default the status page is defined in the `app/config/routing.xml` and via ```system_action``` in the `settings.xml` file to enable using a different action from another module as the status checking page. The action and view can be found in [```app/modules/Honeybee_Core/impl/System/Status```](../app/modules/Honeybee_Core/impl/System/Status).

When writing your own connectors it is advisable to extend the existing abstract [Connector](https://github.com/honeybee/honeybee/blob/master/src/Infrastructure/DataAccess/Connector/Connector.php) and implementing the necessary `connect()` method. To provide a custom status check implement the ```getStatus()``` method. See the [ElasticsearchConnector](https://github.com/honeybee/honeybee/blob/master/src/Infrastructure/DataAccess/Connector/ElasticsearchConnector.php#L26) for an example. Status checks should be somewhat fast to not slow down the `/status` page generation.
