.PHONY: qa lint cs csf phpstan tests coverage

all:
	@$(MAKE) -pRrq -f $(lastword $(MAKEFILE_LIST)) : 2>/dev/null | awk -v RS= -F: '/^# File/,/^# Finished Make data base/ {if ($$1 !~ "^[#.]") {print $$1}}' | sort | egrep -v -e '^[^[:alnum:]]' -e '^$@$$' | xargs

vendor: composer.json composer.lock
	composer install

qa: lint phpstan cs

lint: vendor
	vendor/bin/parallel-lint --exclude .git --exclude vendor packages

cs: vendor
	vendor/bin/phpcs --standard=phpcs.xml packages

csf: vendor
	vendor/bin/phpcbf --standard=phpcs.xml packages

phpstan: vendor
	vendor/bin/phpstan analyse -c phpstan.neon packages

tests: vendor
	vendor/bin/tester -s -p php --colors 1 -C packages/**/tests/cases

coverage: vendor
	vendor/bin/tester -s -p php --colors 1 -C --coverage ./coverage.xml --coverage-src ./packages tests/cases
