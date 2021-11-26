include makeutil/baseConfig.mk

SHELL=bash

git:
	type $@ > /dev/null 2>&1 || ( echo $@ is not installed; exit 10 )

#
makeutil/baseConfig.mk: git
	test -f $@																					||	\
		git clone "https://phabricator.nichework.com/source/makefile-skeleton" makeutil

# Name of the extension under test
mwExtensionUnderTest ?= Set-mwExtensionUnderTest
# Name of the branch under test
mwExtGitBranchUnderTest ?= $(shell git branch --show-current)
# PHPUnit will look for this string and filter by it
mwTestFilter ?=
# PHPUnit will run tests in this group
mwTestGroup ?=
# PHPUnit will run tests in this path (relative to MW_INSTALL_PATH)
mwTestPath ?=

#
getPackagistUnderTest=test ! -f composer.json || ( jq -Mr .name < composer.json )
packagistVersion ?= dev-${mwExtGitBranchUnderTest}

# Image name
mwImage ?= mediawiki
# Version to test
mwVer ?= 1.35

# Image based on image name + version
#
containerID ?= ${mwImage}:${mwVer}

# These are based on the image
#
MW_INSTALL_PATH ?= /var/www/html
WEB_GROUP ?= www-data
WEB_USER ?= www-data
WEB_ROOT ?= /var/www

# Setting up the wiki
#
MW_DB_TYPE ?= sqlite
MW_DB_NAME ?= my_wiki
MW_DATA_DIR ?= /var/www/data
MW_PASSWORD ?= ugly123456
MW_WIKI_USER ?= WikiSysop
MW_SCRIPTPATH ?= ""

# Name of the wiki
MW_SITE_NAME ?= ${mwExtensionUnderTest}

#
mwCiPath ?= ${PWD}/conf
composerPhar ?= ${mwCiPath}/composer.phar
phpIni ?= ${mwCiPath}/php-settings.ini
mwBranch ?= $(shell echo ${mwVer} | (echo -n REL; tr . _))
dockerCli ?= podman
miniSudo ?= podman unshare
mwImgVersion ?= mediawiki:${mwVer}
memcImgVersion ?= docker.io/library/memcached:latest
mwDomain ?= localhost
logDir ?= ${PWD}/logs
mwWebPort ?= 8000
mwContainer ?= mediawiki-${mwExtensionUnderTest}
mwWebUser ?= www-data:www-data
mwDbPath ?= ${mwCiPath}/sqlite-data
mwVendor ?= ${mwCiPath}/vendor
mwAptPath ?= ${mwCiPath}/apt
mwDotComposer ?= ${mwCiPath}/dot-composer
mwExtensions ?= ${mwCiPath}/extensions
mwSkins ?= ${mwCiPath}/skins
thisRepoCopy ?= ${mwExtensions}/${mwExtensionUnderTest}
contPath ?= /var/www/html
mwContPath ?= ${contPath}
compPath ?= ${contPath}/composer
extPath ?= ${mwContPath}/extensions/${mwExtensionUnderTest}
importData ?= test-data/import.xml
phpunitOptions ?= --testdox
autoloadClassmap ?= ${mwVendor}/composer/autoload_classmap.php

# Comma seperated list of extensions to install
installExtensions ?=

lsPath=${mwCiPath}/LocalSettings.php
mwCompLocal=${mwCiPath}/composer.local.json

# Run phpunit tests for this extension
test: build.tar.gz pullContainer
	${make} inContainer target=testInContainer

# Build test environment for this extension
build: pullContainer
	test -f ${mwCiPath}/build.tar.gz															&&	\
		echo "build.tar.gz already exists, not re-creating."									||	\
		${make} inContainer target=buildInContainer

#
${phpIni}: MW_CI_PATH
	test -z "$@" -o -f "$@"															||	(			\
		echo '[PHP]'																			&&	\
		echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE'			)	>	$@

.PHONY: pullContainer
pullContainer:
	export hasIt=`${dockerCli} images -q ${containerID}`										&&	\
	test -n "$$hasIt"																			&&	\
		echo "The container (${containerID}) does not need to be pulled again."					||	\
		${dockerCli} pull ${containerID}

build.tar.gz: build

verifyInContainerEnvVar:
	test -n "${mwExtensionUnderTest}" 														||	(	\
		echo "You must set the mwExtensionUnderTest variable."									&&	\
		echo "See <http://hexm.de/glcivar>"; exit 10											)

inContainer:
	test -n "${target}" 																	||	(	\
		echo "You must specify a target for the container to execute"							&&	\
		echo "See <http://hexm.de/glcivar>"; exit 10											)
	mkdir -p ${mwVendor}
	${dockerCli} run --rm -w /target -v "${PWD}:/target" ${containerID}								\
		make ${target} VERBOSE=${VERBOSE} phpunitOptions="${phpunitOptions}" 						\
			mwExtensionUnderTest="${mwExtensionUnderTest}" mwTestGroup="${mwTestGroup}"				\
			mwTestFilter="${mwTestFilter}" mwTestPath="${mwTestPath}" WEB_GROUP="${WEB_GROUP}"		\
			MW_INSTALL_PATH="${MW_INSTALL_PATH}" WEB_ROOT="${WEB_ROOT}" WEB_USER="${WEB_USER}"


linkInContainer:
	test -L ${target}																		||	(	\
		echo ${indent}"Linking target (${target}) to source (${src}) in container..."	&&			\
		test ! -e ${src}																||	(		\
			echo ${indent}"Source exists, not copying"										&&		\
			rm -rf ${target}																		\
		)																				&&			\
		test ! -e ${target}																||	(		\
			echo ${indent}"Copying source initially."										&&		\
			cp -pr ${target} ${src}															&&		\
			rm -rf ${target}																		\
		)																						&&	\
		ln -s ${src} ${target}																		\
	)

composerBinaryInContainer:
	${make} pkgInContainer bin=unzip
	echo "${indent}Getting composer..."
	test -x ${composerPhar}																	||	(	\
		cd ${mwCiPath}																			&&	\
		curl -o installer "https://getcomposer.org/installer"									&&	\
		curl -o expected "https://composer.github.io/installer.sig"								&&	\
		echo `cat expected` " installer" | sha384sum -c -										&&	\
		php installer																			)

${mwCompLocal}:
	${make} pkgInContainer bin=jq
	export packagistUnderTest=`$(call getPackagistUnderTest)`									&&	\
	test -z "$$packagistUnderTest"													&&	(			\
		echo {} > $@																	)	||	(	\
		echo {}																					|	\
		jq ".require.\"$$packagistUnderTest\" = \"dev-${mwExtGitBranchUnderTest}\""				|	\
		jq '. += { repositories: [{"type":"vcs","url":"${PWD}"}]}' > $@							)

linksInContainer: ${mwCompLocal}
	echo "${indent}Setting up symlinks for container"
	${make} linkInContainer target=${MW_INSTALL_PATH}/extensions/${mwExtensionUnderTest} src=${PWD}
	${make} linkInContainer target=${MW_INSTALL_PATH}/vendor              src=${mwVendor}
	${make} linkInContainer target=${MW_INSTALL_PATH}/composer.local.json src=${mwCompLocal}
	${make} linkInContainer target=${MW_INSTALL_PATH}/composer.lock       src=${mwCiPath}/composer.lock
	${make} linkInContainer target=${MW_INSTALL_PATH}/composer.json       src=${mwCiPath}/composer.json

pkgInContainer: verifyInContainerEnvVar
	type ${bin} > /dev/null 2>&1 															||	(	\
		echo "${indent}Installing $(if ${pkg},${pkg},${bin})..."								&&	\
		apt update																				&&	\
		apt install -y $(if ${pkg},${pkg},${bin})												)

runComposerInContainer: verifyInContainerEnvVar
	${make} pkgInContainer bin=unzip
	echo "${indent}Running composer..."
	php ${composerPhar} update --working-dir ${MW_INSTALL_PATH}

installExtensionInContainer: verifyInContainerEnvVar
	echo "${indent}Installing MediaWiki for ${mwExtensionUnderTest}..."
	mkdir -p ${mwCiPath}/data
	php ${MW_INSTALL_PATH}/maintenance/install.php --dbtype=sqlite --dbname=mywiki					\
			  --pass=ugly123456 --scriptpath="" --dbpath=${mwCiPath}/data							\
			  --server="http://localhost:8000" --extensions=${mwExtensionUnderTest}					\
			  ${mwExtensionUnderTest}-test WikiSysop
	${make} linkInContainer target=${MW_INSTALL_PATH}/LocalSettings.php src=${mwCiPath}/LocalSettings.php
	echo 'error_reporting(E_ALL| E_STRICT);' >> ${mwCiPath}/LocalSettings.php
	echo 'ini_set("display_errors", 1);' >> ${mwCiPath}/LocalSettings.php
	echo '$$wgShowExceptionDetails = true;' >> ${mwCiPath}/LocalSettings.php
	echo '$$wgDevelopmentWarnings = true;' >> ${mwCiPath}/LocalSettings.php
	echo "enableSemantics( 'localhost' );" >> ${mwCiPath}/LocalSettings.php
	php ${MW_INSTALL_PATH}/maintenance/update.php --quick
	chown -R "${WEB_USER}:${WEB_GROUP}" ${mwCiPath}/data

buildInContainer: verifyInContainerEnvVar
	test -f ${mwCiPath}/build.tar.gz 														||	(	\
		${make} composerBinaryInContainer														&&	\
		${make} linksInContainer																&&	\
		${make} runComposerInContainer															&&	\
		${make} installExtensionInContainer														&&	\
		echo "${indent}Creating build.tar.gz"													&&	\
		tar -C ${mwCiPath} -czf ${mwCiPath}/build.tar.gz 											\
			LocalSettings.php composer.local.json composer.json composer.lock vendor data			\
	)

testInContainer: buildInContainer verifyInContainerEnvVar
	tar -C ${mwCiPath} -xzf ${mwCiPath}/build.tar.gz
	${make} linksInContainer
	${make} linkInContainer target=${MW_INSTALL_PATH}/LocalSettings.php 							\
							src=${mwCiPath}/LocalSettings.php
	cd ${MW_INSTALL_PATH}/extensions/${mwExtensionUnderTest}									&&	\
		php ${composerPhar} test --working-dir=${MW_INSTALL_PATH}/extensions/${mwExtensionUnderTest}
