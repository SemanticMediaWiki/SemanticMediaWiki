#!/bin/bash
set -ex

BASE_PATH=$(pwd)
MW_INSTALL_PATH=$BASE_PATH/../mw

## PHPUnit sources need to be present otherwise the MW testrunner will complain
## about it
function installExtrasUsingComposer {
	if [ "$PHPUNIT" != "" ]
	then
		composer require 'phpunit/phpunit='$PHPUNIT --update-with-dependencies
	else
		composer require 'phpunit/phpunit=3.7.*' --update-with-dependencies
	fi

	if [ "$ES" != "" ]
	then
		composer require 'elasticsearch/elasticsearch=6.0.*' --update-with-dependencies
	fi
}

# Run Composer installation from the MW root directory
function installSmwIntoMwWithComposer {
	echo -e "Running MW root composer install build on $TRAVIS_BRANCH \n"

	cd $MW_INSTALL_PATH

	installExtrasUsingComposer
	composer require mediawiki/semantic-media-wiki "dev-master" --dev

	cd extensions
	cd SemanticMediaWiki

	# Pull request number, "false" if it's not a pull request
	# After the install via composer an additional get fetch is carried out to
	# update th repository to make sure that the latests code changes are
	# deployed for testing
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

	cd $MW_INSTALL_PATH/extensions
	composer create-project mediawiki/semantic-media-wiki SemanticMediaWiki dev-master -s dev --prefer-dist --no-dev --no-interaction

	cd SemanticMediaWiki
	installExtrasUsingComposer
}

function installSmwByRunningComposerInstallInIt {
	echo -e "Running composer install build on $TRAVIS_BRANCH \n"

	cp -r $BASE_PATH $MW_INSTALL_PATH/extensions/SemanticMediaWiki
	cd $MW_INSTALL_PATH/extensions/SemanticMediaWiki

	installExtrasUsingComposer
	composer install
}

if [ "$TYPE" == "composer" ]
then
	installSmwIntoMwWithComposer
elif [ "$TYPE" == "relbuild" ] && [ "$TRAVIS_BRANCH" == "master" ]
then
	installSmwAsTarballLikeBuild
else
	installSmwByRunningComposerInstallInIt
fi
