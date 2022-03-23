#! /bin/bash
set -ex

BASE_PATH=$(pwd)

composer install
composer dump-autoload
composer validate --no-interaction

if [ "$TYPE" == "coverage" ]
then
	composer phpunit -- --coverage-clover $BASE_PATH/build/coverage.clover
else
	composer phpunit
fi
