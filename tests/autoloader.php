<?php

/**
 * Convenience autoloader to pre-register test classes
 *
 * Third-party users that require SMW as integration platform should
 * add the following to the bootstrap.php
 *
 * require __DIR__ . '/../../SemanticMediaWiki/tests/autoloader.php'
 */

if ( PHP_SAPI !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'MediaWiki is not available.' );
}

if ( !defined( 'SMW_VERSION' ) ) {
	die( 'SemanticMediaWiki is not available.' );
}

// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
print sprintf( "\n%-20s%s\n", "Semantic MediaWiki:", SMW_VERSION . " ({$GLOBALS['smwgDefaultStore']}, {$GLOBALS['wgDBtype']}" . ( strpos( $GLOBALS['smwgDefaultStore'], 'SQL' ) ? '' : ', ' . $GLOBALS['smwgSparqlDatabaseConnector'] ) . ")" );
// @codingStandardsIgnoreEnd

if ( is_readable( $path = __DIR__ . '/../vendor/autoload.php' ) ) {
	print sprintf( "%-20s%s\n\n", "MediaWiki:", $GLOBALS['wgVersion'] . " (Extension vendor autoloader)" );
} elseif ( is_readable( $path = __DIR__ . '/../../../vendor/autoload.php' ) ) {
	print sprintf( "%-20s%s\n\n", "MediaWiki:", $GLOBALS['wgVersion'] . " (MediaWiki vendor autoloader)" );
} else {
	die( 'To run tests it is required that packages are installed using Composer.' );
}

/**
 * Available to aid third-party extensions therefore any change should be made with
 * care
 *
 * @since  2.0
 */
$autoloader = require $path;

$autoloader->addPsr4( 'SMW\\Tests\\Utils\\', __DIR__ . '/phpunit/Utils' );

$autoloader->addClassMap( array(
	'SMW\Tests\MwDBaseUnitTestCase'         => __DIR__ . '/phpunit/MwDBaseUnitTestCase.php',
	'SMW\Tests\ByJsonTestCaseProvider'      => __DIR__ . '/phpunit/ByJsonTestCaseProvider.php',
	'SMW\Tests\JsonTestCaseFileHandler'     => __DIR__ . '/phpunit/JsonTestCaseFileHandler.php',
	'SMW\Test\QueryPrinterTestCase'         => __DIR__ . '/phpunit/QueryPrinterTestCase.php',
	'SMW\Test\QueryPrinterRegistryTestCase' => __DIR__ . '/phpunit/QueryPrinterRegistryTestCase.php',
) );

return $autoloader;
