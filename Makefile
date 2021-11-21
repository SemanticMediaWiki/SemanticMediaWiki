include makeutil/baseConfig.mk

SHELL=bash

git:
	type $@ > /dev/null 2>&1 || ( echo $@ is not installed; exit 10 )

#
makeutil/baseConfig.mk: git
	test -f $@																					||	\
		git clone "https://phabricator.nichework.com/source/makefile-skeleton" makeutil

include $(shell echo makeutil/composer.m*k | grep \\.mk$$)

# Name of the extension under test
mwExtensionUnderTest ?= Set-mwExtensionUnderTest
# Name of the branch under test
mwExtGitBranchUnderTest ?= $(shell git branch --show-current)
# PHPUnit will look for this string and filter by it
mwTestFilter ?=
# PHPUnit will only excute tests in this group
mwTestGroup ?= ${mwExtensionUnderTest}

#
packagistUnderTest ?= $(shell test ! -f composer.json || ( jq -Mr .name < composer.json ) )
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

#
MW_DB_TYPE ?= sqlite
MW_DB_NAME ?= my_wiki
MW_DATA_DIR ?= /var/www/data
MW_PASSWORD ?= ugly123456

# Name of the wiki
MW_SITE_NAME ?= ${mwExtensionUnderTest}

#
MW_WIKI_USER ?= WikiSysop
MW_SCRIPTPATH ?= ""

#
phpIni ?= ${mwCiPath}/php-settings.ini
mwCiPath ?= ${PWD}/conf
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
installExtensions ?= ParserFunctions

lsPath=${mwCiPath}/LocalSettings.php
mwCompLocal=${mwCiPath}/composer.local.json

updateComposerLocal=(																				\
	require="$$(jq -c .require < ${mwCiPath}/$(1) )"											&&	\
	requireDev="$$(jq -c .[\"require-dev\"] < ${mwCiPath}/$(1) )"								&&	\
	test -s ${mwCompLocal} || echo '{}' > ${mwCompLocal}										&&	\
	test ! -f ${mwCiPath}/$(1) -o "$${arg}" = "null"									||	(		\
		echo updating composer.local.json 													&&		\
		jq -Mr --argjson require "$${require}" --argjson requireDev "$${requireDev}"				\
			'. += { "require": $$require, "require-dev": $$requireDev }' ${mwCompLocal}				\
		| $(call sponge,${mwCompLocal})			)	)

getRepoUrl=https://gerrit.wikimedia.org/r/mediawiki/extensions/$(1)
getContainerID=${dockerCli} ps -f name=$(1) -f status=running -q
rmContainer=${dockerCli} rm -f $(1)
mountExists=$(if $(shell test -e $(1) -a -n "$(2)" && echo yes),-v $(1):$(2))
runMemcContainer=test -n "$(shell $(call getContainerID,$(1)))"									&&	\
			$(call getContainerID,$(1))															||	\
			${dockerCli} run -d -p "11211:11211" --name=$(1) ${memcImgVersion}
runWebContainer=test -n "$(shell $(call getContainerID,$(mwContainer)))"						&&	\
			$(call getContainerID,$(1))															||	\
			${dockerCli} run -d -p "${mwWebPort}:80" --name=$(1)									\
				$(call mountExists,${mwDbPath},${MW_DATA_DIR})										\
				$(call mountExists,${phpIni},/usr/local/etc/php/conf.d/mediawiki.ini)				\
				$(call mountExists,${mwVendor},${mwContPath}/vendor)								\
				$(call mountExists,${logDir},${mwContPath}/logs)									\
				$(call mountExists,${mwExtensions},${mwContPath}/extensions)						\
				$(call mountExists,${mwSkins},${mwContPath}/skins)									\
				$(call mountExists,${lsPath},${mwContPath}/LocalSettings.php)						\
				$(call mountExists,${PWD}/composer,${compPath})										\
				$(call mountExists,${mwAptPath},/var/cache/apt)										\
				$(call mountExists,${mwDotComposer},/root/.cache/composer)							\
				$(call mountExists,${mwCompLocal},${mwContPath}/composer.local.json)				\
				$(call mountExists,${PWD}/favicon.ico,${contPath}/favicon.ico)						\
				$(call mountExists,/tmp/temp,/temp)													\
				-e MW_WEB_PORT="${mwWebPort}"														\
				${mwImgVersion}

# Restart the wiki container
.PHONY: restartWiki
restartWiki: stopWiki startWiki

# Start the wiki
.PHONY: startWiki
startWiki: devVendor ${lsPath}
	cid=$(shell $(call getContainerID,${mwContainer}))											&&	\
	test -n "$${cid}"																	||	(		\
		echo -n "${indent}Starting up wiki: ${MW_SITE_NAME} ... "							)	&&	\
	test -n "$${cid}"																			||	\
		cid=`$(call runWebContainer,${mwContainer})`											&&	\
	echo $${cid}

# Run phpunit against the wiki
testWiki: ${lsPath}
	cid=`$(call runWebContainer,${mwContainer})`												&&	\
	${dockerCli} exec $${cid} sh -c 'test -e ${extPath}/vendor									||	\
		 ln -s ${contPath}/vendor ${extPath}/vendor'											&&	\
	${dockerCli} exec $${cid} ${php} ${compPath} -d${extPath} lint || true						&&	\
	${dockerCli} exec $${cid} ${php} ${compPath} -d${extPath} test || true						&&	\
	${dockerCli} exec $${cid} ${php} tests/phpunit/phpunit.php										\
		--group ${mwTestGroup} ${phpunitOptions}

# Run composer fix against the wiki
fixWiki: devVendor startWiki
	cid=$(shell $(call getContainerID,${mwContainer}))											&&	\
	${dockerCli} exec $${cid} ${php} ${compPath} -d${extPath} fix								&&	\
	${dockerCli} exec $${cid} ${php} ${compPath} -d${extPath} weave								&&	\
	$(call rmContainer,$${cid})

.PHONY: startMemcached
startMemcached:
	cid=$(shell $(call getContainerID,memcached))												&&	\
	test -n "$${cid}"																		||	(	\
		echo -n "${indent}Starting up memcached ... "											&&	\
		cid=`$(call runMemcContainer,memcached)`												&&	\
		echo $${cid}																			)

.PHONY: stopMemcached
stopMemcached:
	cid=$(shell $(call getContainerID,memcached))												&&	\
	test -z "$${cid}"																		||	(	\
		echo -n "${indent}Stopping memcached ($${cid})... "										&&	\
		$(call rmContainer,$${cid})	> /dev/null													&&	\
		echo done.																				)

# Stop the wiki
.PHONY: stopWiki
stopWiki:
	cid=$(shell $(call getContainerID,${mwContainer}))											&&	\
	test -z "$${cid}"																		||	(	\
		echo -n "${indent}Stopping wiki: ${MW_SITE_NAME} ($${cid})... "							&&	\
		$(call rmContainer,$${cid})	> /dev/null													&&	\
		echo done.																				)

# Remove the wiki
rmWiki: stopWiki rmDB
	test ! -d ${mwCiPath}																	||	(	\
		echo -n "${indent}Removing wiki: ${MW_SITE_NAME} ... "									&&	\
		${miniSudo} rm -rf ${mwCiPath}															&&	\
		echo done.																				)

# Run update.php
updatePhp: ${lsPath}
	echo "${indent}Running update.php ..."
	cid=`$(call runWebContainer,${mwContainer})`												&&	\
	${dockerCli} exec $${cid} sh -c "php maintenance/update.php --quick"						&&	\
	$(call rmContainer,$${cid})

# Run update.php
runJobs: ${lsPath}
	echo "${indent}Running jobs.php ..."
	cid=`$(call runWebContainer,${mwContainer})`												&&	\
	${dockerCli} exec $${cid} sh -c "php maintenance/runJobs.php ${args}"						&&	\
	$(call rmContainer,$${cid})

# Run composer update
composerUpdate: devVendor

LocalSettings.php: ${lsPath}

#
.PHONY: ${lsPath}
${lsPath}: ${phpIni} MW_VENDOR MW_EXTENSIONS MW_DB_PATH
	test -s "$@"																				||	\
		rm -f "$@"
	test -z "$(shell $(call getContainerID,${mwContainer}))"									||	\
		$(call rmContainer,${mwContainer})
	test -s "$@"																			||	(	\
		${make} composerUpdate																	&&	\
		echo -n ${indent}Creating LocalSettings.php for ${MW_SITE_NAME} "... "					&&	\
		cid=`$(call runWebContainer,${mwContainer})`											&&	\
		test -n "$${cid}" 															||	(			\
			echo "Could not start container!"											&&			\
			exit 10																		)	&&		\
																						(			\
			${dockerCli} exec $${cid} sh -c "php maintenance/install.php --dbtype=${MW_DB_TYPE}		\
					--dbname=${MW_DB_NAME} --pass=${MW_PASSWORD} --scriptpath=${MW_SCRIPTPATH}		\
					--dbpath=${MW_DATA_DIR} --server='http://${mwDomain}:${mwWebPort}'				\
					--extensions=${mwExtensionUnderTest},${installExtensions}						\
					 ${MW_SITE_NAME} ${MW_WIKI_USER}"									&&			\
			${dockerCli} cp $${cid}:LocalSettings.php $@								&&			\
			$(call rmContainer,$${cid})													)		)
	${miniSudo} chown -R ${mwWebUser} ${mwDbPath}

importData: ${lsPath}
	cid=`$(call runWebContainer,${mwContainer})`												&&	\
	baseName=`basename ${importData}`															&&	\
	${dockerCli} cp ${importData} $$cid:/$${baseName}											&&	\
	${dockerCli} exec -it $${cid} php maintenance/importDump.php /$${baseName}					&&	\
	${dockerCli} exec -it $${cid} php maintenance/rebuildrecentchanges.php

# Update vendor with dev dependencies
.PHONY: devVendor
devVendor: MW_DB_PATH MW_VENDOR MW_APT_CACHE MW_COMPOSER composer ${phpIni}
	export cid=`$(call runWebContainer,${mwContainer})`										&&		\
	test -f "${autoloadClassmap}"															-a		\
		-z "`find ${autoloadClassmap} -mmin +1440 -print 2>/dev/null`"				||	(			\
		echo ${indent}Updating vendor for today with dev deps for ${MW_SITE_NAME} ...	&&			\
		echo -n ${indent}Ensuring unzip is installed for composer "... "				&&			\
		${dockerCli} exec $${cid} sh -c 'test -x /usr/bin/unzip	 ||	(								\
			apt update && apt-get install -y unzip					) > /dev/null 2>&1'	&&			\
		echo ok																			&&			\
		echo ${indent}Running composer update 											&&			\
		${dockerCli} exec $${cid} php ${compPath} require 											\
			wikimedia/composer-merge-plugin ~2 --prefer-source							&&			\
		${dockerCli} exec $${cid} php ${compPath} update								&&			\
		rm -rf ${mwVendor}																&&			\
		${dockerCli} cp "$${cid}:vendor" ${mwVendor}								)	&&	(		\
		${dockerCli} exec $${cid} php ${compPath} dump-autoload								)	&&	\
	echo ${indent}Removing temp web container													&&	\
	$(call rmContainer,$${cid})

# Remove the DB
.PHONY: rmDB
rmDB:
	test "${MW_DB_TYPE}" = "sqlite"															||	(	\
		echo "We do not know how to remove DB of type '${MW_DB_TYPE}'"							&&	\
		exit 2																					)
	${miniSudo} rm -rf ${mwDbPath}

#
.PHONY: MW_DB_PATH
MW_DB_PATH:
	test -z "${mwDbPath}" -o -d "${mwDbPath}"													||	\
		mkdir -p ${mwDbPath}

.PHONY: MW_CI_PATH
MW_CI_PATH:
	test -d "${mwCiPath}"																		||	\
		mkdir -p ${mwCiPath}

.PHONY: MW_VENDOR
MW_VENDOR:
	test -d "${mwVendor}"																	||	(	\
		echo -n "${indent}Copying vendor from image ... "										&&	\
		mkdir -p $(shell dirname ${mwVendor})													&&	\
		cid=`${dockerCli} run --rm=true -d ${mwImgVersion}`										&&	\
		rm -rf ${mwVendor}																		&&	\
		${dockerCli} cp "$${cid}:vendor" ${mwVendor}											&&	\
		rm -f ${mwVendor}/composer/autoload_static.php											&&	\
		$(call rmContainer,$${cid})																)

.PHONY: copyCoreExtensions
copyCoreExtensions:
	test -d "${mwExtensions}"															||	(		\
		export cid=`${dockerCli} run --rm=true -d ${mwImgVersion}`								&&	\
		echo -n ${indent}Copying extensions from image "... "									&&	\
		${dockerCli} cp "$${cid}:extensions" ${mwExtensions}									&&	\
		$(call rmContainer,$${cid})																)

.PHONY: checkoutOtherExtensions
checkoutOtherExtensions:
	for ext in `echo ${installExtensions} | tr -s ', \n\t' ' '`; do								(	\
		echo ${indent}Verifying installation of the $$ext extension ...							&&	\
		export ext																				&&	\
		test -d ${mwExtensions}/$$ext													||	(		\
			git clone -b ${mwBranch} --recurse-submodules											\
				$(call getRepoUrl,$$ext) ${mwExtensions}/$$ext								)	&&	\
		test ! -f ${mwExtensions}/$$ext/composer.json											||	\
			$(call updateComposerLocal,extensions/$$ext/composer.json)							)	\
	done

.PHONY: copyThisExtension
copyThisExtension: composer
	test ! -d "${thisRepoCopy}"																||	(	\
		echo ${indent}Updating checkout for ${thisRepoCopy}										&&	\
		git diff origin/${mwExtGitBranchUnderTest}										|	(		\
			cd ${thisRepoCopy}																&&		\
			git reset --hard																&&		\
			git pull origin ${mwExtGitBranchUnderTest}										&&		\
			patch -p1																		)	)
	test -d "${thisRepoCopy}"																||	(	\
		echo ${indent}Fresh checkout for ${thisRepoCopy}										&&	\
		git clone . ${thisRepoCopy}																&&	\
		export origURL=`git remote get-url origin`												&&	\
		cd ${thisRepoCopy}																		&&	\
		git remote set-url origin $$origURL														&&	\
		git pull origin																			&&	\
		test "${mwExtGitBranchUnderTest}" = "master"											||	\
			git checkout -b ${mwExtGitBranchUnderTest} origin/${mwExtGitBranchUnderTest}			\
	)
	test ! -f ${thisRepoCopy}/composer.json														||	\
			$(call updateComposerLocal,extensions/${mwExtensionUnderTest}/composer.json)
	test -z "${packagistUnderTest}"															||	(	\
		COMPOSER=${mwCompLocal} ${php}																\
			composer require --no-update ${packagistUnderTest}:${packagistVersion}				)

PHONY: MW_EXTENSIONS
MW_EXTENSIONS:
	${make} copyCoreExtensions
	${make} checkoutOtherExtensions
	${make} copyThisExtension

.PHONY: MW_APT_CACHE
MW_APT_CACHE:
	test -d "${mwAptPath}"																		||	\
		mkdir -p ${mwAptPath}

.PHONY: MW_COMPOSER
MW_COMPOSER:
	test -d "${mwDotComposer}"																	||	\
		mkdir -p ${mwDotComposer}

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

build: pullContainer
	test -f ${mwCiPath}/build.tar.gz															&&	\
		echo "build.tar.gz already exists, not re-creating."									||	\
		${make} inContainer target=buildInContainer

build.tar.gz: build
test: build.tar.gz pullContainer
	${make} inContainer target=testInContainer

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
		make ${target} VERBOSE=${VERBOSE} phpunitOptions="${phpunitOptions}"						\
			mwExtensionUnderTest="${mwExtensionUnderTest}" mwTestGroup="${mwTestGroup}"				\
			mwTestFilter="${mwTestFilter}" MW_INSTALL_PATH="${MW_INSTALL_PATH}"						\
			WEB_ROOT="${WEB_ROOT}" WEB_USER="${WEB_USER}" WEB_GROUP="${WEB_GROUP}"

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
	echo "${indent}Getting composer..."
	apt update
	apt install -y unzip jq
	curl -o installer "https://getcomposer.org/installer"
	curl -o expected "https://composer.github.io/installer.sig"
	echo `cat expected` " installer" | sha384sum -c -
	php installer

${mwCompLocal}:
	echo {}																						|	\
		jq '.require."${packagistUnderTest}" = "dev-${mwExtGitBranchUnderTest}"'				|	\
		jq '. += { repositories: [{"type":"vcs","url":"${PWD}"}]}' > $@

linksInContainer: ${mwCompLocal}
	echo "${indent}Setting up symlinks for container"
	${make} linkInContainer target=${MW_INSTALL_PATH}/extensions/${mwExtensionUnderTest} src=${PWD}
	${make} linkInContainer target=${MW_INSTALL_PATH}/vendor              src=${mwVendor}
	${make} linkInContainer target=${MW_INSTALL_PATH}/composer.local.json src=${mwCompLocal}
	${make} linkInContainer target=${MW_INSTALL_PATH}/composer.lock       src=${mwCiPath}/composer.lock
	${make} linkInContainer target=${MW_INSTALL_PATH}/composer.json       src=${mwCiPath}/composer.json

runComposerInContainer:
	echo "${indent}Running composer..."
	php composer.phar update --working-dir ${MW_INSTALL_PATH}

installExtensionInContainer:
	echo "${indent}Installing MediaWiki for ${mwExtensionUnderTest}..."
	mkdir -p ${mwCiPath}/data
	php ${MW_INSTALL_PATH}/maintenance/install.php --dbtype=sqlite --dbname=mywiki					\
			  --pass=ugly123456 --scriptpath="" --dbpath=${mwCiPath}/data							\
			  --server="http://localhost:8000" --extensions=${mwExtensionUnderTest}					\
			  ${mwExtensionUnderTest}-test WikiSysop
	${make} linkInContainer target=${MW_INSTALL_PATH}/LocalSettings.php src=${mwCiPath}/LocalSettings.php
	php ${MW_INSTALL_PATH}/maintenance/update.php --quick
	chown -R "${WEB_USER}:${WEB_GROUP}" ${mwCiPath}/data

buildInContainer: verifyInContainerEnvVar
	${make} composerBinaryInContainer
	${make} linksInContainer
	${make} runComposerInContainer
	${make} installExtensionInContainer
	tar -C ${mwCiPath} -czf ${mwCiPath}/build.tar.gz 												\
		LocalSettings.php composer.local.json composer.json composer.lock vendor data

testInContainer: verifyInContainerEnvVar
	tar -C ${mwCiPath} -xzf ${mwCiPath}/build.tar.gz
	${make} linksInContainer
	${make} linkInContainer target=${MW_INSTALL_PATH}/LocalSettings.php src=${mwCiPath}/LocalSettings.php
	php ${MW_INSTALL_PATH}/tests/phpunit/phpunit.php --group ${mwTestGroup} ${phpunitOptions}
