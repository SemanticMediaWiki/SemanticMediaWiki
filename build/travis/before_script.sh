#! /bin/bash

## Fetching MediaWiki installation base
function runMWInstaller {

	if [ "$MW" == "master" ]
	then
		git clone https://gerrit.wikimedia.org/r/p/mediawiki/core.git phase3 --depth 1
		git checkout $MW
	else
		wget https://github.com/wikimedia/mediawiki-core/archive/$MW.tar.gz
		tar -zxf $MW.tar.gz
		mv mediawiki-core-$MW phase3
	fi

	cd phase3

	## Run Database maintenance script
	if [ "$DBTYPE" == "postgres" ]
	then
	  psql -c 'create database its_a_mw;' -U postgres
	  php maintenance/install.php --dbtype $DBTYPE --dbuser postgres --dbname its_a_mw --pass nyan TravisWiki admin --scriptpath /TravisWiki
	else
	  mysql -e 'create database its_a_mw;'
	  php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --dbpath $(pwd) --pass nyan TravisWiki admin --scriptpath /TravisWiki
	fi

}

## Run SemanticMediaWiki dependency install
function runSMWInstaller {
	if [ "$TYPE" == "composer" ]
	then
		runComposerInstallByMediaWiki
	else
		cd extensions
		cp -r $originalDirectory SemanticMediaWiki
		cd SemanticMediaWiki
		composer install
	fi

	cd ../..
}

# Run Composer installation from the MW root directory
function runComposerInstallByMediaWiki {
	composer require mediawiki/semantic-media-wiki "dev-master"

	cd extensions
	cd SemanticMediaWiki

	# Pull request number, "false" if it's not a pull request
	if [ "$TRAVIS_PULL_REQUEST" != "false" ]
	then
		git fetch origin +refs/pull/"$TRAVIS_PULL_REQUEST"/merge:
		git checkout -qf FETCH_HEAD
	else
		git fetch origin "$TRAVIS_BRANCH"
		git checkout -qf FETCH_HEAD
	fi
}

## Generate LocalSettings
function runSettingsGenerator {
	echo 'require_once( __DIR__ . "/extensions/SemanticMediaWiki/SemanticMediaWiki.php" );' >> LocalSettings.php

	# Error reporting
	echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
	echo 'ini_set("display_errors", 1);' >> LocalSettings.php
	echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
	echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
	echo "putenv( 'MW_INSTALL_PATH=$(pwd)' );" >> LocalSettings.php
}

set -x

originalDirectory=$(pwd)

cd ..

runMWInstaller
runSMWInstaller
runSettingsGenerator

php maintenance/update.php --quick
