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

Create XInclude files to enable the modules, download and compile assets and run status test:

* ```composer init-standalone```

From here on you may run migrations to setup the write- and readside databases/indices:

* ```bin/cli honeybee.core.migrate.run```

Create an administrative user:

* ```bin/cli honeybee.system_account.user.create```
