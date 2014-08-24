#! /bin/bash
set -ex

BASE_PATH=$(pwd)
MW_INSTALL_PATH=$BASE_PATH/../mw

if [ "$TYPE" == "coverage" ]
then
	php $MW_INSTALL_PATH/tests/phpunit/phpunit.php --group SMWExtension -c $MW_INSTALL_PATH/extensions/SemanticMediaWiki/phpunit.xml.dist --coverage-clover $BASE_PATH/build/coverage.clover
elif [ "$TYPE" == "benchmark" ]
then
	php $MW_INSTALL_PATH/tests/phpunit/phpunit.php --group semantic-mediawiki-benchmark -c $MW_INSTALL_PATH/extensions/SemanticMediaWiki/phpunit.xml.dist
else
	php $MW_INSTALL_PATH/tests/phpunit/phpunit.php --group SMWExtension -c $MW_INSTALL_PATH/extensions/SemanticMediaWiki/phpunit.xml.dist
fi