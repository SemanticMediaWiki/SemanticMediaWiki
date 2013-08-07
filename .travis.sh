#! /bin/bash

set -x

originalDirectory=$(pwd)

cd ..

git clone https://gerrit.wikimedia.org/r/p/mediawiki/core.git phase3 --depth 1

cd phase3

mysql -e 'create database its_a_mw;'
php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin

cd extensions

git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/DataValues.git --depth 1
git clone https://gerrit.wikimedia.org/r/p/mediawiki/extensions/Validator.git --depth 1

cp -r $originalDirectory SemanticMediaWiki

cd ..

echo 'require_once( __DIR__ . "/extensions/SemanticMediaWiki/SemanticMediaWiki.php" );' >> LocalSettings.php

echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
echo 'ini_set("display_errors", 1);' >> LocalSettings.php
echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
echo "putenv( 'MW_INSTALL_PATH=$(pwd)' );" >> LocalSettings.php

php maintenance/update.php --quick
