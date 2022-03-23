#! /bin/bash
set -ex

BASE_PATH=$(pwd)

if [ "$TYPE" == "coverage" ]
then
	wget https://scrutinizer-ci.com/ocular.phar
	php ocular.phar code-coverage:upload --format=php-clover $BASE_PATH/build/coverage.clover
fi