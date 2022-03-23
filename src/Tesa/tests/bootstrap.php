<?php

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */

error_reporting( E_ALL | E_STRICT );
date_default_timezone_set( 'UTC' );

if ( PHP_SAPI !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( is_readable( $path = __DIR__ . '/../vendor/autoload.php' ) ) {
	print( "\nUsing the tesa vendor autoloader ...\n\n" );
} elseif ( is_readable( $path = __DIR__ . '/../../../autoload.php' ) ) {
	print( "\nUsing another local vendor autoloader ...\n\n" );
} else {
	die( 'The test suite requires a Composer based deployement.' );
}

print sprintf( "%-25s%s\n\n", "ICU (intl) extension:", ( extension_loaded( 'intl' ) ? INTL_ICU_VERSION : '(disabled)' ) );

$autoLoader = require $path;
$autoLoader->addPsr4( 'Onoi\\Tesa\\Tests\\', __DIR__ . '/phpunit/Unit' );
$autoLoader->addPsr4( 'Onoi\\Tesa\\Tests\\Integration\\', __DIR__ . '/phpunit/Integration' );
unset( $autoLoader );
