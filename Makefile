# vim: ts=4:sw=4:noexpandtab!:

HONEYBEE_ROOT=`pwd`
BUILD_DIR=${HONEYBEE_ROOT}/etc/integration/build/
PHP_ERROR_LOG=`php -i | grep -P '^error_log' | cut -f '3' -d " "`
ENVIRONAUT_CACHE_LOCATION=${HONEYBEE_ROOT}/.environaut.cache
MAKEFLAGS += --no-print-directory

help:
	@echo ""
	@echo "Honeybee makefile, environment: `cat etc/local/environment`"
	@echo ""
	@echo "--------------"
	@echo "COMMON TARGETS"
	@echo "--------------"
	@echo ""
	@echo "  autoloads                 - Generate and optimize autoloads."
	@echo "  build-resources           - Builds css and javascript production packages for the project."
	@echo "  cc                        - Purges/clears the application caches."
	@echo "  config                    - Generates the configuration includes for all (Agavi) modules."
	@echo "  configure                 - Run environaut and configure environment."
	@echo "  info                      - Displays information on the current environment."
	@echo "  install                   - Installs vendor (development) libraries and runs environaut."
	@echo "  install-production        - Installs vendor (production) libraries and runs environaut."
	@echo "  migrate-all               - Executes all pending migrations."
	@echo "  migrate-list              - Displays the status of all migration targets."
	@echo "  reconfigure               - Runs environaut and overrides any exisitng environment configs."
	@echo "  tail-logs                 - Starts to tail all application logs and the php error-log."
	@echo "  update                    - Updates all vendor libraries (dev+prod) and the composer.lock file."
	@echo "  user                      - Opens the create user dialog."
	@echo ""
	@echo "-------------------"
	@echo "DEVELOPMENT TARGETS"
	@echo "-------------------"
	@echo ""
	@echo "Scaffolding:"
	@echo ""
	@echo "  action                    - Creates a new action within an existing module."
	@echo "  fixture                   - Generates a data fixture template."
	@echo "  migration                 - Generates a migration template."
	@echo "  module                    - Creates and links a new honeybee-module."
	@echo "  trellis                   - Generates entity classes using Trellis."
	@echo "  trellis-all               - Generates entity classes for all Trellis targets."
	@echo "  type                      - Generates code for an existing honeybee-module."
	@echo ""
	@echo "Integration and reporting:"
	@echo ""
	@echo "  php-code-sniffer          - Runs the php code-sniffer."
	@echo "  php-copy-paste-detection  - Runs the php copy&paste detector."
	@echo "  php-docs                  - Generates the php api doc for the project."
	@echo "  php-mess-detection        - Runs the php mess detector."
	@echo "  php-tests                 - Runs php test suites."
	@echo ""
	@exit 0

##################
# COMMON TARGETS #
##################

autoloads:
	@php bin/composer.phar dump-autoload --optimize --quiet
	@echo "-> regenerated and optimized autoload files"

css:
	@./bin/cli honeybee.core.util.compile_scss -verbose

build-resources:
	@make css
	@./bin/cli honeybee.core.util.compile_js -verbose
	@echo "-> binary, css and javascript resource packages where successfully built"

cc:
	-@rm -rf app/cache/*
	@make autoloads
	@echo "-> cleared caches"

config:
	-@rm app/config/includes/*
	@./bin/cli honeybee.core.util.build_config --recovery -quiet
	@echo "-> built and included configuration files"
	@make cc

configure:
	@if [ ! -d etc/local/ ]; then mkdir etc/local; fi
	@./vendor/bin/environaut check
	@echo "-> environaut was successfully executed"

info:
	@echo ""
	@echo "Environment"
	@echo "  `cat etc/local/environment`"
	@echo ""
	@echo "Branches/Tags"
	@echo "  honeybee : `git rev-parse --abbrev-ref HEAD`"
	@echo ""
	@echo "Directories"
	@echo "  honeybee root : ${HONEYBEE_ROOT}"
	@echo "  local config  : ${HONEYBEE_ROOT}/etc/local/"
	@echo "  caching dir   : ${HONEYBEE_ROOT}/app/cache/"
	@echo "  logging dir   : ${HONEYBEE_ROOT}/app/log/"
	@echo ""
	@echo "Files"
	@echo "  environaut cache : ${ENVIRONAUT_CACHE_LOCATION}"
	@echo "  php error log    : ${PHP_ERROR_LOG}"
	@echo "  project git-file : ${HONEYBEE_ROOT}/${GIT_FILE}"
	@echo ""

install:
	@if [ ! -d app/cache ]; then mkdir -p app/cache; fi
	@if [ ! -d app/log ]; then mkdir -p app/log; fi
	@if [ ! -d data/assets ]; then mkdir -p data/assets; fi

	@if [ ! -f bin/composer.phar ]; \
	then \
		curl -s http://getcomposer.org/installer | php -d allow_url_fopen=1 -d date.timezone="Europe/Berlin" -- --install-dir=./bin; \
	else \
		bin/composer.phar self-update; \
	fi

	@php -d allow_url_fopen=1 bin/composer.phar install --optimize-autoloader
	@npm install
	@./node_modules/.bin/bower install --config.analytics=false
	@./bin/wget_packages
	@make configure
	@make config
	@make build-resources
	@./bin/cli honeybee.core.migrate.run

install-production:
	@if [ ! -f bin/composer.phar ]; \
	then \
		curl -s http://getcomposer.org/installer | php -d allow_url_fopen=1 -d date.timezone="Europe/Berlin" -- --install-dir=./bin; \
	else \
		bin/composer.phar self-update; \
	fi

	@php -d allow_url_fopen=1 bin/composer.phar install --no-dev --optimize-autoloader
	@npm install
	@./node_modules/.bin/bower install --config.analytics=false
	@./bin/wget_packages
	@make configure
	@make config
	@make build-resources

reconfigure:
	@if [ -f "${ENVIRONAUT_CACHE_LOCATION}" ]; then rm ${ENVIRONAUT_CACHE_LOCATION} && echo "Deleted environaut cache."; fi
	@echo "-> removed environaut cache, starting reconfiguration"
	@make configure

tail-logs:
	@tail -f "${PHP_ERROR_LOG}" app/log/*.log

update:
	@if [ ! -f ./bin/composer.phar ]; then echo "pls run make install first"; fi
	@./bin/composer.phar self-update
	@php -d allow_url_fopen=1 bin/composer.phar update --optimize-autoloader
	@npm install
	@./node_modules/.bin/bower update --config.analytics=false
	@./bin/wget_packages
	@make build-resources

migrate-all:
	@./bin/cli honeybee.core.migrate.run -target all

migrate-list:
	@./bin/cli honeybee.core.migrate.list

user:
	@./bin/cli honeybee.system_account.user.create

#######################
# DEVELOPMENT TARGETS #
#######################

module:
	@make config
	@./bin/cli honeybee.core.util.generate_code -skeleton honeybee_module -quiet
	@echo ""
	@echo "    You can now quickly scaffold new entity types into this"
	@echo "    module using the helper command line utility:"
	@echo ""
	@echo "    make type"
	@echo ""

type:
	@make config
	@./bin/cli honeybee.core.util.generate_code -skeleton honeybee_type -quiet
	@make config
	@echo ""
	@echo "    When you have updated your entity attributes and reference"
	@echo "    definitions you can regenerate your classes using the"
	@echo "    helper command line utility:"
	@echo ""
	@echo "    make trellis"
	@echo ""

trellis:
	@make config
	@./bin/cli honeybee.core.trellis.generate_code -quiet
	@make config

trellis-all:
	@make config
	@./bin/cli honeybee.core.trellis.generate_code -target all -quiet
	@make config

migration:
	@./bin/cli honeybee.core.migrate.create

fixture:
	@./bin/cli honeybee.core.fixture.create
	@echo "    You can now generate JSON data for your fixture file by"
	@echo "    using the data generator utility:"
	@echo ""
	@echo "    bin/cli honeybee.core.fixture.generate"
	@echo ""

code-sniffer:
	@mkdir -p ${BUILD_DIR}/logs
	@./vendor/bin/phpcs --extensions=php \
		--report=checkstyle \
		--report-file=${BUILD_DIR}/checkstyle.xml \
		--ignore='app/cache*,*Success.php,*Input.php,*Error.php,app/templates/*,*.css,*.js' \
		--standard=psr2 ./app/lib/Honeybee ./testing/unit/Honeybee

code-sniffer-cli:
	-@./vendor/bin/phpcs --extensions=php \
		--ignore='app/cache*,*Success.php,*Input.php,*Error.php,app/templates/*,*.css,*.js' \
		--standard=psr2 ./app/lib/Honeybee ./testing/unit/Honeybee

php-copy-paste-detection:
	@/bin/mkdir -p ${BUILD_DIR}/logs
	-@./vendor/bin/phpcpd --log-pmd ${BUILD_DIR}/logs/pmd-cpd.xml app/

php-docs:
	@if [ -d ${BUILD_DIR}/docs/api/serverside/ ]; then rm -rf ${BUILD_DIR}/docs/api/serverside/; fi
	@APPLICATION_DIR=. php ./vendor/bin/sami.php update ./app/config/sami.php

php-mess-detection:
	@/bin/mkdir -p ${BUILD_DIR}/logs
	-@./vendor/bin/phpmd ./app xml codesize,design,naming,unusedcode --reportfile ${BUILD_DIR}/logs/pmd.xml

php-tests:
	@./vendor/bin/phpunit testing/unit/Honeybee

.PHONY: help build-resources module-code module reconfigure cc config install update install-production
