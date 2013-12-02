#! /bin/bash

set -x

originalDirectory=$(pwd)

cd ..

if [ "$MW" == "master" ]
then
	git clone https://gerrit.wikimedia.org/r/p/mediawiki/core.git phase3 --depth 1
else
	wget https://github.com/wikimedia/mediawiki-core/archive/$MW.tar.gz
	tar -zxf $MW.tar.gz
	mv mediawiki-core-$MW phase3
fi

cd phase3

git checkout $MW

if [ "$DBTYPE" == "postgres" ]
then
  psql -c 'create database its_a_mw;' -U postgres
  php maintenance/install.php --dbtype $DBTYPE --dbuser postgres --dbname its_a_mw --pass nyan TravisWiki admin --scriptpath /TravisWiki
else
  mysql -e 'create database its_a_mw;'
  php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin --scriptpath /TravisWiki
fi

if [ "$TYPE" == "composer" ]
then
	composer require mediawiki/semantic-media-wiki "dev-master"

	cd extensions
	cd SemanticMediaWiki

	# Pull request number, "false" if it's not a pull request
	if [ "$TRAVIS_PULL_REQUEST" != "false" ]
	then
		git fetch origin +refs/pull/"$TRAVIS_PULL_REQUEST"/merge:
	else
		git fetch origin "$TRAVIS_BRANCH"
	fi

	git checkout -qf FETCH_HEAD
	cd ../..

else
	cd extensions
	cp -r $originalDirectory SemanticMediaWiki
	cd SemanticMediaWiki
	composer install
	cd ../..
fi

echo 'require_once( __DIR__ . "/extensions/SemanticMediaWiki/SemanticMediaWiki.php" );' >> LocalSettings.php

echo '$wgScriptPath = "/TravisWiki";' >> LocalSettings.php
echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
echo 'ini_set("display_errors", 1);' >> LocalSettings.php
echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
echo "putenv( 'MW_INSTALL_PATH=$(pwd)' );" >> LocalSettings.php

php maintenance/update.php --quick
