<?php

/**
 * Convenience autoloader to pre-register test classes
 *
 * Third-party users that require SMW as integration platform should
 * add the following to the bootstrap.php
 *
 * require __DIR__ . '/../../SemanticMediaWiki/tests/autoloader.php'
 */
if ( PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ) {
	die( 'Not an entry point' );
}

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'MediaWiki is not available.' );
}

if ( !class_exists( 'SemanticMediaWiki' ) || !defined( 'SMW_VERSION' ) ) {
	die( "\nSemantic MediaWiki is not available, please check your LocalSettings or Composer settings.\n" );
}

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

if ( is_readable( $path = __DIR__ . '/../vendor/autoload.php' ) ) {
	$autoloadType = "Extension vendor autoloader";
} elseif ( is_readable( $path = $basePath . '/vendor/autoload.php' ) ) {
	$autoloadType = "MediaWiki vendor autoloader";
} else {
	die( 'To run the test suite it is required that packages are installed using Composer.' );
}

require __DIR__ . '/phpUnitEnvironment.php';
$phpUnitEnvironment = new PHPUnitEnvironment();

if ( $phpUnitEnvironment->hasDebugRequest( $GLOBALS['argv'] ) === false ) {
	$phpUnitEnvironment->emptyDebugVars();
}

$phpUnitEnvironment->writeNewLn( "Semantic MediaWiki:", $phpUnitEnvironment->getVersion( 'smw' ) );
$phpUnitEnvironment->writeLn( "MediaWiki:", $phpUnitEnvironment->getVersion( 'mw', [ 'type' => $autoloadType ] ) );
$phpUnitEnvironment->writeLn( "Site language:", $phpUnitEnvironment->getSiteLanguageCode() );
$phpUnitEnvironment->writeNewLn( "Execution time:", $phpUnitEnvironment->executionTime() );
$phpUnitEnvironment->writeLn( "Debug logs:", ( $phpUnitEnvironment->enabledDebugLogs() ? 'Enabled' : 'Disabled' ) );
$phpUnitEnvironment->writeLn( "Xdebug:", ( ( $version = $phpUnitEnvironment->getXdebugInfo() ) ? $version : 'Disabled (or not installed)' ) );
$phpUnitEnvironment->writeNewLn();

unset( $phpUnitEnvironment );

/**
 * Available to aid third-party extensions therefore any change should be made with
 * care
 *
 * @since  2.0
 */
$autoloader = require $path;

$autoloader->addPsr4( 'SMW\\Tests\\Utils\\', __DIR__ . '/phpunit/Utils' );

$autoloader->addClassMap( [
	'SMW\Tests\TestEnvironment'             => __DIR__ . '/phpunit/TestEnvironment.php',
	'SMW\Tests\TestConfig'                  => __DIR__ . '/phpunit/TestConfig.php',
	'SMW\Tests\PHPUnitCompat'               => __DIR__ . '/phpunit/PHPUnitCompat.php',
	'SMW\Tests\DatabaseTestCase'            => __DIR__ . '/phpunit/DatabaseTestCase.php',
	'SMW\Tests\JsonTestCaseScriptRunner'    => __DIR__ . '/phpunit/JsonTestCaseScriptRunner.php',
	'SMW\Tests\JsonTestCaseFileHandler'     => __DIR__ . '/phpunit/JsonTestCaseFileHandler.php',
	'SMW\Tests\JsonTestCaseContentHandler'  => __DIR__ . '/phpunit/JsonTestCaseContentHandler.php',
	'SMW\Test\QueryPrinterTestCase'         => __DIR__ . '/phpunit/QueryPrinterTestCase.php',
	'SMW\Test\QueryPrinterRegistryTestCase' => __DIR__ . '/phpunit/QueryPrinterRegistryTestCase.php',
	'SMW\Tests\SPARQLStore\RepositoryConnectors\ElementaryRepositoryConnectorTest' => __DIR__ . '/phpunit/Unit/SPARQLStore/RepositoryConnectors/ElementaryRepositoryConnectorTest.php',
] );

// 3.0
class_alias( '\SMW\Tests\DatabaseTestCase', '\SMW\Tests\MwDBaseUnitTestCase' );

return $autoloader;