<?php

/**
 * Convenience autoloader to pre-register test classes
 *
 * Third-party users that require SMW as integration platform should
 * add the following to the bootstrap.php
 *
 * $autoLoader = require SMW_PHPUNIT_AUTOLOADER_FILE;
 * $autoloader->addPsr4( ... );
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

// Extensions are able to define this in case the output requires an extended
// width due to a long extension name.
if ( !defined( 'SMW_PHPUNIT_FIRST_COLUMN_WIDTH' ) ) {
	define( 'SMW_PHPUNIT_FIRST_COLUMN_WIDTH', 20 );
}

require __DIR__ . '/phpUnitEnvironment.php';
$phpUnitEnvironment = new PHPUnitEnvironment();

if ( $phpUnitEnvironment->hasDebugRequest( $GLOBALS['argv'] ) === false ) {
	$phpUnitEnvironment->emptyDebugVars();
}

$phpUnitEnvironment->writeNewLn( "Semantic MediaWiki:", $phpUnitEnvironment->getVersion( 'smw' ) );
$phpUnitEnvironment->writeLn( "", $phpUnitEnvironment->getVersion( 'store' ) );

$phpUnitEnvironment->writeNewLn( "MediaWiki:", $phpUnitEnvironment->getVersion( 'mw' ) );
$phpUnitEnvironment->writeLn( "", $autoloadType );

$phpUnitEnvironment->writeNewLn( "Site language:", $phpUnitEnvironment->getSiteLanguageCode() );
$phpUnitEnvironment->writeLn( "Execution time:", $phpUnitEnvironment->executionTime() );
$phpUnitEnvironment->writeNewLn( "Debug logs:", ( $phpUnitEnvironment->enabledDebugLogs() ? 'Enabled' : 'Disabled' ) );
$phpUnitEnvironment->writeLn( "Xdebug:", ( ( $version = $phpUnitEnvironment->getXdebugInfo() ) ? $version : 'Disabled (or not installed)' ) );
$phpUnitEnvironment->writeNewLn( "Intl/ICU:", ( ( $intl = $phpUnitEnvironment->getIntlInfo() ) ? $intl : 'Disabled (or not installed)' ) );
$phpUnitEnvironment->writeLn( "PCRE:", ( ( $pcre = $phpUnitEnvironment->getPcreInfo() ) ? $pcre : 'Disabled (or not installed)' ) );
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
$autoloader->addPsr4( 'SMW\\Tests\\Fixtures\\', __DIR__ . '/phpunit/Fixtures' );

$autoloader->addClassMap( [
	'SMW\Tests\TestEnvironment'                     => __DIR__ . '/phpunit/TestEnvironment.php',
	'SMW\Tests\TestConfig'                          => __DIR__ . '/phpunit/TestConfig.php',
	'SMW\Tests\PHPUnitCompat'                       => __DIR__ . '/phpunit/PHPUnitCompat.php',
	'SMW\Tests\DatabaseTestCase'                    => __DIR__ . '/phpunit/DatabaseTestCase.php',
	'SMW\Tests\JSONScriptTestCaseRunner'            => __DIR__ . '/phpunit/JSONScriptTestCaseRunner.php',
	'SMW\Tests\JSONScriptServicesTestCaseRunner'    => __DIR__ . '/phpunit/JSONScriptServicesTestCaseRunner.php',
	'SMW\Test\QueryPrinterTestCase'                 => __DIR__ . '/phpunit/QueryPrinterTestCase.php',
	'SMW\Test\QueryPrinterRegistryTestCase'         => __DIR__ . '/phpunit/QueryPrinterRegistryTestCase.php',
	'SMW\Tests\SPARQLStore\RepositoryConnectors\ElementaryRepositoryConnectorTest' => __DIR__ . '/phpunit/Unit/SPARQLStore/RepositoryConnectors/ElementaryRepositoryConnectorTest.php',

	// Reference needed for SRF as it inherits from this class (or better its alias)!!
	// TODO: make sure to use `JSONScriptServicesTestCaseRunner`
	'SMW\Tests\Integration\JSONScript\JSONScriptTestCaseRunnerTest' => __DIR__ . '/phpunit/Integration/JSONScript/JSONScriptTestCaseRunnerTest.php',
	'SMW\Tests\Integration\JSONScript\JsonTestCaseScriptRunnerTest' => __DIR__ . '/phpunit/Integration/JSONScript/JSONScriptTestCaseRunnerTest.php'
] );

// 3.2
class_alias( '\SMW\Tests\JSONScriptTestCaseRunner', 'SMW\Tests\JsonTestCaseScriptRunner' );
class_alias( '\SMW\Tests\JSONScriptServicesTestCaseRunner', 'SMW\Tests\LightweightJsonTestCaseScriptRunner' );
class_alias( '\SMW\Tests\JSONScriptServicesTestCaseRunner', 'SMW\Tests\ExtendedJsonTestCaseScriptRunner' );

// 3.1
class_alias( '\SMW\Tests\Utils\JSONScript\JsonTestCaseFileHandler', 'SMW\Tests\JsonTestCaseFileHandler' );
class_alias( '\SMW\Tests\Utils\JSONScript\JsonTestCaseContentHandler', 'SMW\Tests\JsonTestCaseContentHandler' );

// 3.0
class_alias( '\SMW\Tests\DatabaseTestCase', '\SMW\Tests\MwDBaseUnitTestCase' );

return $autoloader;
