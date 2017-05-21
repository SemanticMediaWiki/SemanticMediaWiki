#! /bin/bash
set -ex

BASE_PATH=$(pwd)
MW_INSTALL_PATH=$BASE_PATH/../mw

cd $MW_INSTALL_PATH/extensions/SemanticMediaWiki

if [ "$TYPE" == "coverage" ]
then
	composer phpunit -- --coverage-clover $BASE_PATH/tests/coverage.clover
elif [ "$TYPE" == "benchmark" ]
then
	composer phpunit -- --group semantic-mediawiki-benchmark
elif [ "$TYPE" == "debug" ]
then
	composer phpunit -- --debug-tests
else
	composer phpunit
fi
