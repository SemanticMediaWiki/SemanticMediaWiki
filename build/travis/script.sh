#! /bin/bash

## Run headless testing using PhantomJS
function runPhantomJS {

	# Make sure the installed site responds
	curl http://localhost/w/index.php/Main_Page
	curl http://localhost/TravisWiki/index.php/Main_Page

	# Responds on the 2nd request
	time curl http://localhost/TravisWiki/index.php/Main_Page -s -o /dev/null

	echo "Running QUnit tests..."

	phantomjs ../../extensions/SemanticMediaWiki/tests/qunit/phantomjs-qunit-runner.js "http://localhost/TravisWiki/index.php/Special:JavaScriptTest/qunit?module=ext.smw"

}

set -x

originalDirectory=$(pwd)

cd ../phase3/tests/phpunit

if [ "$TYPE" == "phantomjs" ]
then
	runPhantomJS
elif [ "$TYPE" == "coverage" ]
then
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist --coverage-clover $originalDirectory/build/coverage.clover
else
	php phpunit.php --group SMWExtension -c ../../extensions/SemanticMediaWiki/phpunit.xml.dist
fi