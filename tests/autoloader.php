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

if ( !class_exists( 'SemanticMediaWiki' ) || ( $version = SemanticMediaWiki::getVersion() ) === null ) {
	die( "\Semantic MediaWiki is not available, please check your LocalSettings or Composer settings.\n" );
}

// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
print sprintf( "\n%-20s%s\n", "Semantic MediaWiki:", $version . ' ('. implode( ', ', SemanticMediaWiki::getStoreVersion() ) . ')' );
// @codingStandardsIgnoreEnd

if ( is_readable( $path = __DIR__ . '/../vendor/autoload.php' ) ) {
	print sprintf( "%-20s%s\n", "MediaWiki:", $GLOBALS['wgVersion'] . " (Extension vendor autoloader)" );
} elseif ( is_readable( $path = __DIR__ . '/../../../vendor/autoload.php' ) ) {
	print sprintf( "%-20s%s\n", "MediaWiki:", $GLOBALS['wgVersion'] . " (MediaWiki vendor autoloader)" );
} else {
	die( 'To run tests it is required that packages are installed using Composer.' );
}

print sprintf( "%-20s%s\n", "Site language:", $GLOBALS['wgLanguageCode'] );

$dateTimeUtc = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
print sprintf( "\n%-20s%s\n", "Execution time:", $dateTimeUtc->format( 'Y-m-d h:i' ) );

if ( extension_loaded('xdebug') && xdebug_is_enabled() ) {
	print sprintf( "%-20s%s\n\n", "Xdebug:", phpversion('xdebug') . ' (enabled)' );
} else {
	print sprintf( "%-20s%s\n\n", "Xdebug:", 'Disabled (or not installed)' );
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
	'SMW\Tests\TestEnvironment'             => __DIR__ . '/phpunit/TestEnvironment.php',
	'SMW\Tests\MwDBaseUnitTestCase'         => __DIR__ . '/phpunit/MwDBaseUnitTestCase.php',
	'SMW\Tests\ByJsonTestCaseProvider'      => __DIR__ . '/phpunit/ByJsonTestCaseProvider.php',
	'SMW\Tests\JsonTestCaseFileHandler'     => __DIR__ . '/phpunit/JsonTestCaseFileHandler.php',
	'SMW\Test\QueryPrinterTestCase'         => __DIR__ . '/phpunit/QueryPrinterTestCase.php',
	'SMW\Test\QueryPrinterRegistryTestCase' => __DIR__ . '/phpunit/QueryPrinterRegistryTestCase.php',
) );

return $autoloader;
