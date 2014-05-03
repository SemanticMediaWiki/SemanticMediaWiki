#! /bin/bash

set -x

originalDirectory=$(pwd)

cd ../phase3/tests/phpunit

if [ "$TYPE" == "coverage" ]
then
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist --coverage-clover $originalDirectory/build/coverage.clover
elif [ "$TRAVIS_PHP_VERSION" = "hhvm" ]
then
	hhvm --php \
	-d include_path=".$(printf ':%s' vendor/phpunit/*)" \
	-d date.timezone="Etc/UTC" \
	phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist -v --debug
else
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist
fi