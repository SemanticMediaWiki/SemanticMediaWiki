#! /bin/bash
set -ex

BASE_PATH=$(pwd)

if [ "$TYPE" == "coverage" ]
then
	# Use for coveralls.io but currently no working
	# composer require satooshi/php-coveralls:dev-master
	# php vendor/bin/coveralls -v

	wget https://scrutinizer-ci.com/ocular.phar
	php ocular.phar code-coverage:upload --format=php-clover $BASE_PATH/tests/coverage.clover
fi