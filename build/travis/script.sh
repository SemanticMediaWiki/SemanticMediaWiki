#! /bin/bash

set -x

cd ../phase3/tests/phpunit

if [ "$TYPE" == "coverage" ]
then
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist --coverage-clover ../../extensions/SemanticMediaWiki/build/logs/clover.xml
else
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist
fi