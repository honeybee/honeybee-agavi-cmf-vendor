# honeybee-agavi-cmf-vendor

Provides a fully functioning cmf boilerplate, that is built with [honeybee](https://github.com/honeybee/honeybee) and [agavi](https://github.com/agavi/agavi). It is used by being integrated into a [honeybee-agavi-cmf-project](https://github.com/honeybee/honeybee-agavi-cmf-project) via composer and can be customized from there on.

## Installation

This repository is NOT meant to be run standalone. The following steps make it possible though:

* ```git clone git@github.com:honeybee/honeybee-agavi-cmf-vendor.git```
* `cd honeybee-agavi-cmf-vendor`
* `composer install`
* `mkdir app/cache`
* `mkdir app/log`

Setup local configuration files (adjust values accordingly):

* `sudo mkdir /usr/local/honeybee-agavi-cmf-vendor`
* create `/usr/local/honeybee-agavi-cmf-vendor/environment` and put the environment name in there: `development`
* create `/usr/local/honeybee-agavi-cmf-vendor/config.json` with: ```{"local":{"base_href":"https://devbox.local/"}}```
* create `/usr/local/honeybee-agavi-cmf-vendor/couchdb.json` with: ```{"couchdb":{"host":"localhost","port":5984,"user":"honeybee","password":"honeybee"}}```
* create `/usr/local/honeybee-agavi-cmf-vendor/elasticsearch.json` with: ```{"elasticsearch":{"host":"localhost","port":9200}}```

Create XInclude files to enable the modules:

* ```bin/cli honeybee.core.util.build_config --recovery```

Download and compile assets and additional packages:

* `npm install`
* ```./node_modules/.bin/bower install```
* ```bin/cli honeybee.core.util.compile_scss``` to create css files
* ```bin/cli honeybee.core.util.compile_js``` to run `r.js` and create `pub/static/modules-built`
* ```bin/wget_packages```

Run status test:

* ```bin/cli status``` should display `WORKING` now

From here on you may run migrations to setup the write- and readside databases/indices:

* ```bin/cli honeybee.core.migrate.run```
