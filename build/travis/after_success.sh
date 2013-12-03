#! /bin/bash

set -x

if [ "$TYPE" == "coverage" ]
then
	cd ../../extensions/SemanticMediaWiki
	composer require satooshi/php-coveralls:dev-master
	php vendor/bin/coveralls -v
fi