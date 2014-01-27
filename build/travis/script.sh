#! /bin/bash

set -x

originalDirectory=$(pwd)

cd ../phase3/tests/phpunit

if [ "$PHPUNIT" == "single" ]
then
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist ../../extensions/SemanticMediaWiki/tests/phpunit/regression/DatePropertyRegressionTest.php
else
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist
fi