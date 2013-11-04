#! /bin/bash

if [ "$MW-$DBTYPE" == "master-mysql" ]
then
	cd ../phase3/tests/phpunit
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist --coverage-clover ../../extensions/SemanticMediaWiki/build/logs/clover.xml

	cd ../../extensions/SemanticMediaWiki
	composer require satooshi/php-coveralls:dev-master
	php vendor/bin/coveralls -v
fi
