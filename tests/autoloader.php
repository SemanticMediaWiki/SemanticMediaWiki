<?php

/**
 * Convenience autoloader to pre-register test classes
 *
 * Third-party users that require SMW as integration platform should
 * add the following to the bootstrap.php
 *
 * require __DIR__ . '/../../SemanticMediaWiki/tests/autoloader.php'
 */

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'MediaWiki is not available.' );
}

if ( !defined( 'SMW_VERSION' ) ) {
	die( 'SemanticMediaWiki is not available.' );
}

if ( is_readable( $path = __DIR__ . '/../vendor/autoload.php' ) ) {
	print( "\nUsing the local vendor autoloader ...\n\n" );
} elseif ( is_readable( $path = __DIR__ . '/../../../vendor/autoload.php' ) ) {
	print( "\nUsing the MediaWiki vendor autoloader ...\n\n" );
} else {
	die( 'To run tests it is required that packages are installed using Composer.' );
}

$autoloader = require $path;

/**
 * Available to third-party extensions therefore any change should be made with
 * care
 *
 * @since  2.0
 */
$autoloader->addPsr4( 'SMW\\Tests\\Util\\', __DIR__ . '/phpunit/Util' );

$autoloader->addClassMap( array(
	'SMW\Tests\MwDBaseUnitTestCase'         => __DIR__ . '/phpunit/MwDBaseUnitTestCase.php',
	'SMW\Test\QueryPrinterTestCase'         => __DIR__ . '/phpunit/QueryPrinterTestCase.php',
	'SMW\Test\QueryPrinterRegistryTestCase' => __DIR__ . '/phpunit/QueryPrinterRegistryTestCase.php',
) );

return $autoloader;
