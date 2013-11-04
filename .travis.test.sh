#! /bin/bash

cd ../phase3/tests/phpunit

if [ "$MW-$DBTYPE" == "master-mysql" ]
then
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist --coverage-clover ../../extensions/SemanticMediaWiki/build/logs/clover.xml
else
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist
fi
