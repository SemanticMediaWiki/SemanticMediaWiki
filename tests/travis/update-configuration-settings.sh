#!/bin/bash
set -ex

BASE_PATH=$(pwd)
MW_INSTALL_PATH=$BASE_PATH/../mw

cd $MW_INSTALL_PATH

# Namespace related settings
echo 'define("SMW_PHPUNIT_PULL_VERSION_FROM_GITHUB", true);' >> LocalSettings.php
echo 'define("NS_TRAVIS", 998);' >> LocalSettings.php
echo 'define("NS_TRAVIS_TALK", 999);' >> LocalSettings.php
echo '$wgExtraNamespaces[NS_TRAVIS] = "Travis";' >> LocalSettings.php
echo '$wgExtraNamespaces[NS_TRAVIS_TALK] = "Travis_talk";' >> LocalSettings.php
echo '$wgNamespacesWithSubpages[NS_TRAVIS] = true;' >> LocalSettings.php

# echo 'require_once( __DIR__ . "/extensions/SemanticMediaWiki/SemanticMediaWiki.php" );' >> LocalSettings.php
echo 'wfLoadExtension( "SemanticMediaWiki" );' >> LocalSettings.php

echo '$smwgNamespacesWithSemanticLinks = array( NS_MAIN => true, NS_FILE => true, NS_TRAVIS => true );' >> LocalSettings.php
echo '$smwgNamespace = "http://example.org/id/";' >> LocalSettings.php

if [ "$FOURSTORE" != "" ]
then
	echo '$smwgDefaultStore = "SMWSparqlStore";' >> LocalSettings.php
	echo '$smwgSparqlRepositoryConnector = "4Store";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["query"] = "http://localhost:8088/sparql/";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["update"] = "http://localhost:8088/update/";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["data"] = "";' >> LocalSettings.php
	echo '$smwgSparqlDefaultGraph = "http://example.org/mydefaultgraphname";' >> LocalSettings.php
elif [ "$FUSEKI" != "" ]
then
	echo '$smwgDefaultStore = "SMWSparqlStore";' >> LocalSettings.php
	echo '$smwgSparqlRepositoryConnector = "Fuseki";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["query"] = "http://localhost:3030/db/query";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["update"] = "http://localhost:3030/db/update";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["data"] = "";' >> LocalSettings.php
elif [ "$SESAME" != "" ]
then
	echo '$smwgDefaultStore = "SMWSparqlStore";' >> LocalSettings.php
	echo '$smwgSparqlRepositoryConnector = "Sesame";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["query"] = "http://localhost:8080/openrdf-sesame/repositories/test-smw";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["update"] = "http://localhost:8080/openrdf-sesame/repositories/test-smw/statements";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["data"] = "";' >> LocalSettings.php
elif [ "$BLAZEGRAPH" != "" ]
then
	echo '$smwgDefaultStore = "SMWSparqlStore";' >> LocalSettings.php
	echo '$smwgSparqlRepositoryConnector = "Blazegraph";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["query"] = "http://localhost:9999/bigdata/namespace/kb/sparql";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["update"] = "http://localhost:9999/bigdata/namespace/kb/sparql";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["data"] = "";' >> LocalSettings.php
	echo '$smwgSparqlDefaultGraph = "";' >> LocalSettings.php
elif [ "$VIRTUOSO" != "" ]
then
	echo '$smwgDefaultStore = "SMWSparqlStore";' >> LocalSettings.php
	echo '$smwgSparqlRepositoryConnector = "Virtuoso";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["query"] = "http://localhost:8890/sparql";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["update"] = "http://localhost:8890/sparql";' >> LocalSettings.php
	echo '$smwgSparqlEndpoint["data"] = "";' >> LocalSettings.php
	echo '$smwgSparqlDefaultGraph = "http://example.org/travisGraph";' >> LocalSettings.php
elif [ "$ES" != "" ]
then
	echo '$smwgDefaultStore = "SMWElasticStore";' >> LocalSettings.php
	echo '$smwgElasticsearchEndpoints = [ "http://127.0.0.1:9200" ];' >> LocalSettings.php
else
	echo '$smwgDefaultStore = "SMWSQLStore3";' >> LocalSettings.php
fi

# Site language
if [ "$SITELANG" != "" ]
then
	echo '$wgLanguageCode = "'$SITELANG'";' >> LocalSettings.php
fi

# Error reporting
echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
echo 'ini_set("display_errors", 1);' >> LocalSettings.php
echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
echo '$wgShowSQLErrors = true;' >> LocalSettings.php
echo '$wgDebugDumpSql = false;' >> LocalSettings.php
echo '$wgShowDBErrorBacktrace = true;' >> LocalSettings.php

if [ "$TYPE" == "debug" ]
then
	echo '$wgDebugLogFile = "/tmp/mediawiki-debug.log";' >> LocalSettings.php
fi

echo "putenv( 'MW_INSTALL_PATH=$(pwd)' );" >> LocalSettings.php

php maintenance/update.php --skip-external-dependencies --quick
