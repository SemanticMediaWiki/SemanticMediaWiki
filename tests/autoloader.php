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

if ( !class_exists( 'SemanticMediaWiki' ) || SemanticMediaWiki::getVersion() === null ) {
	die( "\nSemantic MediaWiki is not available, please check your LocalSettings or Composer settings.\n" );
}

if ( is_readable( $path = __DIR__ . '/../vendor/autoload.php' ) ) {
	$autoloadType = "Extension vendor autoloader";
} elseif ( is_readable( $path = __DIR__ . '/../../../vendor/autoload.php' ) ) {
	$autoloadType = "MediaWiki vendor autoloader";
} else {
	die( 'To run the test suite it is required that packages are installed using Composer.' );
}

require __DIR__ . '/phpUnitEnvironment.php';
$phpUnitEnvironment = new PHPUnitEnvironment();

if ( $phpUnitEnvironment->hasDebugRequest( $GLOBALS['argv'] ) === false ) {
	$phpUnitEnvironment->emptyDebugVars();
}

$phpUnitEnvironment->writeLn( "\n", "Semantic MediaWiki:", $phpUnitEnvironment->getVersion( 'smw' ), "\n" );
$phpUnitEnvironment->writeLn( "", "MediaWiki:", $phpUnitEnvironment->getVersion( 'mw' ) + [ 'type' => $autoloadType ], "\n" );
$phpUnitEnvironment->writeLn( "", "Site language:", $phpUnitEnvironment->getSiteLanguageCode(), "\n" );
$phpUnitEnvironment->writeLn( "\n", "Execution time:", $phpUnitEnvironment->executionTime(),"\n" );
$phpUnitEnvironment->writeLn( "", "Debug logs:", $phpUnitEnvironment->enabledDebugLogs() ? 'Enabled' : 'Disabled', "\n" );
$phpUnitEnvironment->writeLn( "", "Xdebug:", ( $version = $phpUnitEnvironment->getXdebugInfo() ) ? $version : 'Disabled (or not installed)' , "\n\n" );

unset( $phpUnitEnvironment );

/**
 * Available to aid third-party extensions therefore any change should be made with
 * care
 *
 * @since  2.0
 */
$autoloader = require $path;

$autoloader->addPsr4( 'SMW\\Tests\\Utils\\', __DIR__ . '/phpunit/Utils' );

$autoloader->addClassMap( array(
	'SMW\Tests\TestEnvironment'             => __DIR__ . '/phpunit/TestEnvironment.php',
	'SMW\Tests\TestConfig'                  => __DIR__ . '/phpunit/TestConfig.php',
	'SMW\Tests\MwDBaseUnitTestCase'         => __DIR__ . '/phpunit/MwDBaseUnitTestCase.php',
	'SMW\Tests\JsonTestCaseScriptRunner'    => __DIR__ . '/phpunit/JsonTestCaseScriptRunner.php',
	'SMW\Tests\JsonTestCaseFileHandler'     => __DIR__ . '/phpunit/JsonTestCaseFileHandler.php',
	'SMW\Tests\JsonTestCaseContentHandler'  => __DIR__ . '/phpunit/JsonTestCaseContentHandler.php',
	'SMW\Test\QueryPrinterTestCase'         => __DIR__ . '/phpunit/QueryPrinterTestCase.php',
	'SMW\Test\QueryPrinterRegistryTestCase' => __DIR__ . '/phpunit/QueryPrinterRegistryTestCase.php',
	'SMW\Tests\SPARQLStore\RepositoryConnectors\ElementaryRepositoryConnectorTest' => __DIR__ . '/phpunit/Unit/SPARQLStore/RepositoryConnectors/ElementaryRepositoryConnectorTest.php',
) );

return $autoloader;
