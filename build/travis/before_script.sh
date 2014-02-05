#! /bin/bash

## Fetching MediaWiki installation base
function installMediaWiki {

	wget https://github.com/wikimedia/mediawiki-core/archive/$MW.tar.gz
	tar -zxf $MW.tar.gz
	mv mediawiki-core-$MW phase3

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
function installSMW {
	if [ "$TYPE" == "composer" ]
	then
		installSmwIntoMwWithComposer
	elif [ "$TYPE" == "relbuild" ] && [ "$TRAVIS_BRANCH" == "master" ]
	then
		installSmwAsTarballLikeBuild
	else
		installSmwByRunningComposerInstallInIt
	fi
}

# Run Composer installation from the MW root directory
function installSmwIntoMwWithComposer {
	echo -e "Running MW root composer install build on $TRAVIS_BRANCH \n"

	composer require mediawiki/semantic-media-wiki "dev-master" --prefer-source

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

	cd ../..

	# Rebuild the class map for added classes during git fetch
	composer dump-autoload
}

# Running tarball build only on the master branch to detect other issues before it is merged
# because the tarball build will not contain the latests submitted version.
# We do however want to ensure noticing any breakage of this process before we prepare a release.
function installSmwAsTarballLikeBuild {
	echo -e "Running tarball build on $TRAVIS_BRANCH \n"
	
	cd extensions
	composer create-project mediawiki/semantic-media-wiki SemanticMediaWiki dev-master -s dev --prefer-dist --no-dev
	cd ..
}

function installSmwByRunningComposerInstallInIt {
	echo -e "Running composer install build on $TRAVIS_BRANCH \n"

	cd extensions
	cp -r $originalDirectory SemanticMediaWiki
	cd SemanticMediaWiki
	composer install --prefer-source
	cd ../..
}

## Generate LocalSettings
function configureLocalSettings {

	# Namespace related settings
	echo 'define("NS_TRAVIS", 998);' >> LocalSettings.php
	echo 'define("NS_TRAVIS_TALK", 999);' >> LocalSettings.php
	echo '$wgExtraNamespaces[NS_TRAVIS] = "Travis";' >> LocalSettings.php
	echo '$wgExtraNamespaces[NS_TRAVIS_TALK] = "Travis_talk";' >> LocalSettings.php
	echo '$wgNamespacesWithSubpages[NS_TRAVIS] = true;' >> LocalSettings.php

	echo 'require_once( __DIR__ . "/extensions/SemanticMediaWiki/SemanticMediaWiki.php" );' >> LocalSettings.php

	echo '$smwgNamespacesWithSemanticLinks = array( NS_MAIN => true, NS_IMAGE => true, NS_TRAVIS => true );' >> LocalSettings.php
	echo '$smwgNamespace = "http://example.org/id/";' >> LocalSettings.php

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

installMediaWiki
installSMW
configureLocalSettings

php maintenance/update.php --quick
